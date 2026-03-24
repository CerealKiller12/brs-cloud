<?php

use App\Http\Controllers\Auth\SocialAuthController;
use App\Models\Device;
use App\Models\Store;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\Rule;

$buildStoreContext = function (User $user, ?int $requestedStoreId = null) {
    abort_unless($user->tenant_id, 403, 'Esta cuenta todavia no tiene un negocio asignado.');

    $stores = Store::query()
        ->where('tenant_id', $user->tenant_id)
        ->orderBy('name')
        ->get(['id', 'name', 'code', 'catalog_version', 'timezone', 'is_active']);

    abort_unless($stores->isNotEmpty(), 403, 'Este negocio todavia no tiene sucursales configuradas.');

    $preferredStoreId = $requestedStoreId ?: session('cloud_active_store_id') ?: $user->store_id;
    $activeStore = $stores->firstWhere('id', $preferredStoreId) ?? $stores->first();

    if ((int) session('cloud_active_store_id') !== (int) $activeStore->id) {
        session(['cloud_active_store_id' => $activeStore->id]);
    }

    return [$activeStore, $stores];
};

$resolveStoreForUser = function (User $user, ?int $requestedStoreId = null) use ($buildStoreContext) {
    [$activeStore] = $buildStoreContext($user, $requestedStoreId);

    return $activeStore;
};

View::composer('layouts.app', function ($view) use ($buildStoreContext) {
    if (!Auth::check()) {
        return;
    }

    /** @var User|null $user */
    $user = Auth::user();

    if (!$user?->tenant_id) {
        return;
    }

    [$activeStore, $availableStores] = $buildStoreContext($user);

    $view->with([
        'cloudActiveStore' => $activeStore,
        'cloudAvailableStores' => $availableStores,
    ]);
});

$bumpCatalogVersion = function (int $storeId): int {
    DB::table('stores')->where('id', $storeId)->increment('catalog_version');

    return (int) DB::table('stores')->where('id', $storeId)->value('catalog_version');
};

$humanizeEventType = function (?string $eventType): string {
    return match ($eventType) {
        'product.created' => 'Producto agregado',
        'product.updated' => 'Producto actualizado',
        'product.deleted' => 'Producto eliminado',
        'product.stock-adjusted' => 'Stock ajustado',
        'sale.created' => 'Venta registrada',
        'cash-session.opened' => 'Caja abierta',
        'cash-session.closed' => 'Caja cerrada',
        default => Str::headline(str_replace(['.', '-'], ' ', (string) $eventType)),
    };
};

$humanizeAggregateType = function (?string $aggregateType): string {
    return match ($aggregateType) {
        'sale' => 'Venta',
        'product' => 'Catalogo',
        'cash-session', 'cash_session' => 'Caja',
        default => Str::headline(str_replace(['.', '-'], ' ', (string) $aggregateType)),
    };
};

$humanizeDeviceLabel = function (?string $deviceId, ?string $deviceName = null, ?string $platform = null): string {
    if (filled($deviceName)) {
        return $deviceName;
    }

    if (!$deviceId) {
        return 'Caja sin nombre';
    }

    if ($platform === 'ios' || Str::startsWith($deviceId, 'ipad-')) {
        return 'Caja iPad';
    }

    if ($platform === 'desktop' || Str::startsWith($deviceId, 'desktop-')) {
        return 'Caja de escritorio';
    }

    return 'Caja conectada';
};

$describeSyncEvent = function (?string $eventType, array $payload): string {
    $sku = trim((string) ($payload['sku'] ?? ''));
    $name = trim((string) ($payload['name'] ?? ''));
    $folio = trim((string) ($payload['folio'] ?? data_get($payload, 'sale.folio', '')));
    $items = $payload['items'] ?? data_get($payload, 'sale.items', []);
    $itemsCount = is_countable($items) ? count($items) : 0;
    $stockOnHand = $payload['stockOnHand'] ?? null;

    return match ($eventType) {
        'sale.created' => $folio !== ''
            ? "Ticket {$folio}".($itemsCount > 0 ? " · {$itemsCount} producto(s)" : '')
            : ($itemsCount > 0 ? "{$itemsCount} producto(s) cobrados" : 'Venta enviada desde caja'),
        'product.stock-adjusted' => trim(collect([
            $name !== '' ? $name : null,
            $sku !== '' ? "SKU {$sku}" : null,
            is_numeric($stockOnHand) ? "stock actual {$stockOnHand}" : null,
        ])->filter()->implode(' · ')) ?: 'Movimiento de inventario',
        'product.created', 'product.updated' => trim(collect([
            $name !== '' ? $name : null,
            $sku !== '' ? "SKU {$sku}" : null,
        ])->filter()->implode(' · ')) ?: 'Cambio en catalogo',
        'product.deleted' => trim(collect([
            $name !== '' ? $name : null,
            $sku !== '' ? "SKU {$sku}" : null,
            'eliminado del catalogo',
        ])->filter()->implode(' · ')) ?: 'Producto retirado del catalogo',
        'cash-session.opened' => 'Inicio de caja registrado',
        'cash-session.closed' => 'Cierre de caja registrado',
        default => trim(collect([
            $name !== '' ? $name : null,
            $sku !== '' ? "SKU {$sku}" : null,
            $folio !== '' ? "Ticket {$folio}" : null,
        ])->filter()->implode(' · ')) ?: 'Movimiento recibido desde una caja',
    };
};

$streamCatalogVersionEvents = function (int $storeId, string $storeCode, int $initialCatalogVersion) {
    return response()->stream(function () use ($storeId, $storeCode, $initialCatalogVersion) {
        ignore_user_abort(true);
        @set_time_limit(0);

        if (function_exists('session_write_close')) {
            @session_write_close();
        }

        $sendEvent = function (string $event, array $payload): void {
            echo "event: {$event}\n";
            echo 'data: '.json_encode($payload, JSON_UNESCAPED_SLASHES)."\n\n";

            if (function_exists('ob_flush')) {
                @ob_flush();
            }

            @flush();
        };

        $lastVersion = max(0, $initialCatalogVersion);
        $startedAt = time();
        $lastHeartbeatAt = 0;

        $sendEvent('catalog.version', [
            'storeId' => $storeId,
            'storeCode' => $storeCode,
            'catalogVersion' => $lastVersion,
            'emittedAt' => now()->toIso8601String(),
        ]);

        while (!connection_aborted() && (time() - $startedAt) < 30) {
            usleep(2000000);

            $currentVersion = (int) DB::table('stores')
                ->where('id', $storeId)
                ->value('catalog_version');

            if ($currentVersion > $lastVersion) {
                $lastVersion = $currentVersion;

                $sendEvent('catalog.version', [
                    'storeId' => $storeId,
                    'storeCode' => $storeCode,
                    'catalogVersion' => $lastVersion,
                    'emittedAt' => now()->toIso8601String(),
                ]);

                continue;
            }

            if ((time() - $lastHeartbeatAt) >= 10) {
                $lastHeartbeatAt = time();

                $sendEvent('heartbeat', [
                    'storeId' => $storeId,
                    'storeCode' => $storeCode,
                    'catalogVersion' => $lastVersion,
                    'emittedAt' => now()->toIso8601String(),
                ]);
            }
        }
    }, 200, [
        'Content-Type' => 'text/event-stream',
        'Cache-Control' => 'no-cache, no-transform',
        'Connection' => 'keep-alive',
        'X-Accel-Buffering' => 'no',
    ]);
};

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])->name('social.redirect');
Route::match(['GET', 'POST'], '/auth/{provider}/callback', [SocialAuthController::class, 'callback'])->name('social.callback');

Route::middleware('guest')->group(function () {
    Route::get('/register', function () {
        return view('auth.register');
    })->name('register');

    Route::get('/login', function () {
        return view('auth.login');
    })->name('login');

    Route::post('/login', function (Request $request) {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt(array_merge($credentials, ['is_active' => true]), $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Credenciales invalidas para BRS Cloud.']);
        }

        $request->session()->regenerate();

        $user = Auth::user();

        return redirect()->intended($user?->tenant?->onboarding_completed_at ? route('dashboard') : route('onboarding.index'));
    })->name('login.submit');
});

Route::middleware('auth')->group(function () use ($resolveStoreForUser, $bumpCatalogVersion, $streamCatalogVersionEvents, $humanizeEventType, $humanizeAggregateType, $humanizeDeviceLabel, $describeSyncEvent) {
    Route::post('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');

    Route::post('/context/store', function (Request $request) {
        /** @var User $user */
        $user = Auth::user();

        $payload = $request->validate([
            'store_id' => ['required', 'integer'],
        ]);

        $store = Store::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('id', $payload['store_id'])
            ->firstOrFail();

        session(['cloud_active_store_id' => $store->id]);

        return back()->with('status', "Sucursal activa actualizada a {$store->name}.");
    })->name('context.store');

    Route::get('/settings', function () {
        /** @var User $user */
        $user = Auth::user();
        $tenant = Tenant::query()->findOrFail($user->tenant_id);
        $store = Store::query()->where('tenant_id', $user->tenant_id)->findOrFail($user->store_id);
        $branding = is_array($store->branding_json) ? $store->branding_json : (json_decode($store->branding_json ?? '[]', true) ?: []);

        return view('settings', compact('user', 'tenant', 'store', 'branding'));
    })->name('settings.index');

    Route::post('/settings/account', function (Request $request) {
        /** @var User $user */
        $user = Auth::user();

        $payload = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'avatar_url' => ['nullable', 'url', 'max:500'],
        ]);

        $user->update([
            'name' => $payload['name'],
            'avatar_url' => $payload['avatar_url'] ?: null,
        ]);

        return redirect()->route('settings.index')->with('status', 'Cuenta actualizada.');
    })->name('settings.account');

    Route::post('/settings/tenant', function (Request $request) {
        /** @var User $user */
        $user = Auth::user();
        $tenant = Tenant::query()->findOrFail($user->tenant_id);
        $store = Store::query()->where('tenant_id', $user->tenant_id)->findOrFail($user->store_id);
        $branding = is_array($store->branding_json) ? $store->branding_json : (json_decode($store->branding_json ?? '[]', true) ?: []);

        $payload = $request->validate([
            'tenant_name' => ['required', 'string', 'max:120'],
            'business_name' => ['required', 'string', 'max:160'],
        ]);

        $tenant->update([
            'name' => $payload['tenant_name'],
        ]);

        $branding['business_name'] = $payload['business_name'];
        $store->update([
            'branding_json' => $branding,
        ]);

        return redirect()->route('settings.index')->with('status', 'Negocio actualizado.');
    })->name('settings.tenant');

    Route::post('/settings/store', function (Request $request) {
        /** @var User $user */
        $user = Auth::user();
        $store = Store::query()->where('tenant_id', $user->tenant_id)->findOrFail($user->store_id);
        $branding = is_array($store->branding_json) ? $store->branding_json : (json_decode($store->branding_json ?? '[]', true) ?: []);

        $payload = $request->validate([
            'store_name' => ['required', 'string', 'max:120'],
            'timezone' => ['required', 'string', 'max:60'],
            'terminal_name' => ['required', 'string', 'max:120'],
        ]);

        $branding['terminal_name'] = $payload['terminal_name'];

        $store->update([
            'name' => $payload['store_name'],
            'timezone' => $payload['timezone'],
            'branding_json' => $branding,
        ]);

        return redirect()->route('settings.index')->with('status', 'Sucursal principal actualizada.');
    })->name('settings.store');

    Route::get('/onboarding', function () {
        /** @var User $user */
        $user = Auth::user();
        $tenant = Tenant::query()->findOrFail($user->tenant_id);

        if ($tenant->onboarding_completed_at) {
            return redirect()->route('dashboard');
        }

        $store = Store::query()->where('tenant_id', $user->tenant_id)->findOrFail($user->store_id);
        $branding = is_array($store->branding_json) ? $store->branding_json : (json_decode($store->branding_json ?? '[]', true) ?: []);

        return view('onboarding', compact('user', 'tenant', 'store', 'branding'));
    })->name('onboarding.index');

    Route::post('/onboarding', function (Request $request) {
        /** @var User $user */
        $user = Auth::user();
        $tenant = Tenant::query()->findOrFail($user->tenant_id);
        $store = Store::query()->where('tenant_id', $user->tenant_id)->findOrFail($user->store_id);
        $branding = is_array($store->branding_json) ? $store->branding_json : (json_decode($store->branding_json ?? '[]', true) ?: []);

        $payload = $request->validate([
            'tenant_name' => ['required', 'string', 'max:120'],
            'business_name' => ['required', 'string', 'max:160'],
            'store_name' => ['required', 'string', 'max:120'],
            'terminal_name' => ['required', 'string', 'max:120'],
            'timezone' => ['required', 'string', 'max:60'],
            'owner_name' => ['required', 'string', 'max:120'],
        ]);

        $tenant->update([
            'name' => $payload['tenant_name'],
            'onboarding_completed_at' => now(),
        ]);

        $branding['business_name'] = $payload['business_name'];
        $branding['terminal_name'] = $payload['terminal_name'];

        $store->update([
            'name' => $payload['store_name'],
            'timezone' => $payload['timezone'],
            'branding_json' => $branding,
        ]);

        $user->update([
            'name' => $payload['owner_name'],
        ]);

        return redirect()->route('dashboard')->with('status', 'Configuracion inicial completada.');
    })->name('onboarding.store');

    Route::get('/dashboard', function () use ($resolveStoreForUser, $humanizeEventType, $humanizeAggregateType, $humanizeDeviceLabel, $describeSyncEvent) {
        /** @var User $user */
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        $store = $resolveStoreForUser($user);
        $storeId = $store->id;
        $now = now();
        $todayStart = now()->copy()->startOfDay();
        $yesterdayStart = $todayStart->copy()->subDay();
        $sevenDaysAgo = now()->copy()->subDays(6)->startOfDay();
        $thirtyDaysAgo = now()->copy()->subDays(29)->startOfDay();

        $tenant = $tenantId ? DB::table('tenants')->where('id', $tenantId)->first() : null;

        $deviceMetaById = Device::query()
            ->where('tenant_id', $tenantId)
            ->where('store_id', $storeId)
            ->get(['device_id', 'name', 'platform'])
            ->keyBy('device_id');

        $salesHistory = DB::table('sync_events')
            ->select(['device_id', 'payload_json', 'occurred_at', 'received_at'])
            ->where('tenant_id', $tenantId)
            ->where('store_id', $storeId)
            ->where('event_type', 'sale.created')
            ->where('received_at', '>=', $thirtyDaysAgo)
            ->orderBy('received_at')
            ->get()
            ->map(function ($event) {
                $payload = json_decode($event->payload_json, true) ?: [];

                try {
                    $occurredAt = !empty($payload['createdAt'])
                        ? \Carbon\Carbon::parse($payload['createdAt'])
                        : (!empty($event->occurred_at)
                            ? \Carbon\Carbon::parse($event->occurred_at)
                            : \Carbon\Carbon::parse($event->received_at));
                } catch (\Throwable) {
                    $occurredAt = \Carbon\Carbon::parse($event->received_at);
                }

                $items = $payload['items'] ?? data_get($payload, 'sale.items', []);

                return (object) [
                    'device_id' => $event->device_id,
                    'folio' => trim((string) ($payload['folio'] ?? data_get($payload, 'sale.folio', ''))),
                    'occurred_at' => $occurredAt,
                    'payment_method' => (string) ($payload['paymentMethod'] ?? data_get($payload, 'sale.paymentMethod', 'cash')),
                    'total_cents' => (int) ($payload['totalCents'] ?? data_get($payload, 'sale.totalCents', 0)),
                    'items' => is_array($items) ? $items : [],
                ];
            })
            ->values();

        $salesLast7Days = $salesHistory
            ->filter(fn ($sale) => $sale->occurred_at->gte($sevenDaysAgo))
            ->values();

        $salesTodayCollection = $salesHistory
            ->filter(fn ($sale) => $sale->occurred_at->gte($todayStart))
            ->values();

        $salesYesterdayCollection = $salesHistory
            ->filter(fn ($sale) => $sale->occurred_at->gte($yesterdayStart) && $sale->occurred_at->lt($todayStart))
            ->values();

        $salesTodayAmountCents = (int) $salesTodayCollection->sum('total_cents');
        $salesYesterdayAmountCents = (int) $salesYesterdayCollection->sum('total_cents');
        $salesTodayCount = (int) $salesTodayCollection->count();
        $salesLast7DaysCount = (int) $salesLast7Days->count();
        $salesLast7DaysAmountCents = (int) $salesLast7Days->sum('total_cents');
        $averageTicketTodayCents = $salesTodayCount > 0 ? (int) round($salesTodayAmountCents / $salesTodayCount) : 0;
        $averageTicket7DaysCents = $salesLast7DaysCount > 0 ? (int) round($salesLast7DaysAmountCents / $salesLast7DaysCount) : 0;

        $salesDeltaPercent = null;
        if ($salesYesterdayAmountCents > 0) {
            $salesDeltaPercent = (int) round((($salesTodayAmountCents - $salesYesterdayAmountCents) / $salesYesterdayAmountCents) * 100);
        } elseif ($salesTodayAmountCents > 0) {
            $salesDeltaPercent = 100;
        }

        $salesTimeline = collect(range(6, 0))->map(function (int $daysAgo) use ($salesLast7Days) {
            $day = now()->copy()->subDays($daysAgo);
            $rows = $salesLast7Days->filter(fn ($sale) => $sale->occurred_at->isSameDay($day));

            return [
                'label' => $day->format('d/m'),
                'tickets' => (int) $rows->count(),
                'amountCents' => (int) $rows->sum('total_cents'),
            ];
        })->values();

        $paymentLabels = [
            'cash' => 'Efectivo',
            'card' => 'Tarjeta',
            'transfer' => 'Transferencia',
            'mixed' => 'Mixto',
        ];

        $paymentMix = collect($paymentLabels)
            ->map(function (string $label, string $key) use ($salesLast7Days) {
                $rows = $salesLast7Days->filter(fn ($sale) => $sale->payment_method === $key);

                return (object) [
                    'key' => $key,
                    'label' => $label,
                    'tickets' => (int) $rows->count(),
                    'amountCents' => (int) $rows->sum('total_cents'),
                ];
            })
            ->filter(fn ($row) => $row->tickets > 0 || $row->amountCents > 0)
            ->values();

        $hourlySales = collect(range(0, 23))->map(function (int $hour) use ($salesTodayCollection) {
            $rows = $salesTodayCollection->filter(fn ($sale) => (int) $sale->occurred_at->format('G') === $hour);

            return [
                'label' => str_pad((string) $hour, 2, '0', STR_PAD_LEFT).':00',
                'tickets' => (int) $rows->count(),
                'amountCents' => (int) $rows->sum('total_cents'),
            ];
        })->values();

        $peakHour = collect($hourlySales)->sortByDesc('tickets')->first();

        $catalogProducts = DB::table('cloud_catalog_products')
            ->where('store_id', $storeId)
            ->get(['sku', 'name'])
            ->mapWithKeys(fn ($product) => [[mb_strtolower(trim((string) $product->sku)) => $product->name]]);

        $topProducts = $salesLast7Days
            ->flatMap(function ($sale) {
                return collect($sale->items)->map(function ($item) {
                    return [
                        'sku' => trim((string) ($item['productSku'] ?? '')),
                        'quantity' => (int) ($item['quantity'] ?? 0),
                    ];
                });
            })
            ->filter(fn ($item) => $item['sku'] !== '' && $item['quantity'] > 0)
            ->groupBy(fn ($item) => mb_strtolower($item['sku']))
            ->map(function ($rows, string $skuKey) use ($catalogProducts) {
                $sku = (string) ($rows->first()['sku'] ?? $skuKey);

                return (object) [
                    'sku' => $sku,
                    'name' => $catalogProducts[$skuKey] ?? $sku,
                    'quantity' => (int) $rows->sum('quantity'),
                    'tickets' => (int) $rows->count(),
                ];
            })
            ->sortByDesc('quantity')
            ->take(6)
            ->values();

        $deviceSales = $salesLast7Days
            ->groupBy('device_id')
            ->map(function ($rows, string $deviceId) use ($deviceMetaById, $humanizeDeviceLabel) {
                $meta = $deviceMetaById->get($deviceId);
                $tickets = (int) $rows->count();
                $amountCents = (int) $rows->sum('total_cents');

                return (object) [
                    'device_id' => $deviceId,
                    'label' => $humanizeDeviceLabel($deviceId, $meta->name ?? null, $meta->platform ?? null),
                    'tickets' => $tickets,
                    'amountCents' => $amountCents,
                    'averageTicketCents' => $tickets > 0 ? (int) round($amountCents / $tickets) : 0,
                ];
            })
            ->sortByDesc('amountCents')
            ->take(5)
            ->values();

        $stats = [
            'stores' => DB::table('stores')->where('tenant_id', $tenantId)->count(),
            'devices' => DB::table('devices')->where('tenant_id', $tenantId)->count(),
            'onlineDevices' => DB::table('devices')
                ->where('tenant_id', $tenantId)
                ->where('store_id', $storeId)
                ->where('last_seen_at', '>=', $now->copy()->subMinutes(10))
                ->count(),
            'catalogItems' => DB::table('cloud_catalog_products')->where('store_id', $storeId)->count(),
            'pendingEvents' => DB::table('sync_events')->where('tenant_id', $tenantId)->where('store_id', $storeId)->count(),
            'conflicts' => DB::table('sync_events')
                ->where('tenant_id', $tenantId)
                ->where('store_id', $storeId)
                ->whereNotNull('apply_error')
                ->count(),
            'lowStock' => DB::table('cloud_catalog_products')
                ->where('store_id', $storeId)
                ->where('is_active', true)
                ->where('track_inventory', true)
                ->whereColumn('stock_on_hand', '<=', 'reorder_point')
                ->count(),
            'salesToday' => $salesTodayCount,
            'salesTodayAmountCents' => $salesTodayAmountCents,
            'salesYesterdayAmountCents' => $salesYesterdayAmountCents,
            'salesDeltaPercent' => $salesDeltaPercent,
            'averageTicketTodayCents' => $averageTicketTodayCents,
            'salesLast7Days' => $salesLast7DaysCount,
            'salesLast7DaysAmountCents' => $salesLast7DaysAmountCents,
            'averageTicket7DaysCents' => $averageTicket7DaysCents,
        ];

        $recentEvents = DB::table('sync_events')
            ->where('tenant_id', $tenantId)
            ->where('store_id', $storeId)
            ->latest('received_at')
            ->limit(8)
            ->get();

        $lowStockProducts = DB::table('cloud_catalog_products')
            ->where('store_id', $storeId)
            ->where('is_active', true)
            ->where('track_inventory', true)
            ->whereColumn('stock_on_hand', '<=', 'reorder_point')
            ->orderBy('stock_on_hand')
            ->limit(5)
            ->get(['name', 'sku', 'stock_on_hand', 'reorder_point']);

        $nextSteps = collect([
            [
                'done' => $stats['stores'] > 0,
                'title' => 'Ya tienes una sucursal lista',
                'detail' => 'Tu operacion principal ya puede recibir cajas y catalogo compartido.',
                'cta' => route('stores.index'),
                'ctaLabel' => 'Ver sucursales',
            ],
            [
                'done' => $stats['onlineDevices'] > 0,
                'title' => 'Conecta al menos una caja',
                'detail' => 'Una caja conectada te permite operar ventas y sincronizar cambios en vivo.',
                'cta' => route('devices.index'),
                'ctaLabel' => 'Ver cajas',
            ],
            [
                'done' => $stats['catalogItems'] > 0,
                'title' => 'Carga tu catalogo compartido',
                'detail' => 'Agrega tus productos base para que todas las cajas arranquen con el mismo inventario.',
                'cta' => route('catalog.index'),
                'ctaLabel' => 'Abrir catalogo',
            ],
            [
                'done' => $stats['salesToday'] > 0,
                'title' => 'Haz tu primera venta del dia',
                'detail' => 'En cuanto una caja cobre, aqui veras el pulso real de tu operacion.',
                'cta' => route('sync.index'),
                'ctaLabel' => 'Ver actividad',
            ],
        ]);

        $recentEventDeviceMeta = Device::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('device_id', $recentEvents->pluck('device_id')->filter()->unique()->values())
            ->get(['device_id', 'name', 'platform'])
            ->keyBy('device_id');

        $recentEvents = $recentEvents->map(function ($event) use ($recentEventDeviceMeta, $humanizeEventType, $humanizeAggregateType, $humanizeDeviceLabel, $describeSyncEvent) {
            $deviceMeta = $recentEventDeviceMeta->get($event->device_id);
            $payload = json_decode($event->payload_json, true) ?: [];
            $event->device_label = $humanizeDeviceLabel($event->device_id, $deviceMeta->name ?? null, $deviceMeta->platform ?? null);
            $event->event_label = $humanizeEventType($event->event_type);
            $event->aggregate_label = $humanizeAggregateType($event->aggregate_type);
            $event->detail_label = $describeSyncEvent($event->event_type, $payload);

            return $event;
        });

        return view('dashboard', compact(
            'user',
            'tenant',
            'store',
            'stats',
            'recentEvents',
            'salesTimeline',
            'paymentMix',
            'topProducts',
            'deviceSales',
            'hourlySales',
            'peakHour',
            'lowStockProducts',
            'nextSteps'
        ));
    })->name('dashboard');

    Route::get('/stores', function (Request $request) {
        /** @var User $user */
        $user = Auth::user();
        $editId = $request->integer('edit');

        $stores = Store::query()
            ->where('tenant_id', $user->tenant_id)
            ->orderBy('name')
            ->get();

        $storeIds = $stores->pluck('id');

        $deviceCounts = $storeIds->isEmpty()
            ? collect()
            : DB::table('devices')
                ->select('store_id', DB::raw('count(*) as aggregate'))
                ->whereIn('store_id', $storeIds)
                ->groupBy('store_id')
                ->pluck('aggregate', 'store_id');

        $catalogCounts = $storeIds->isEmpty()
            ? collect()
            : DB::table('cloud_catalog_products')
                ->select('store_id', DB::raw('count(*) as aggregate'))
                ->whereIn('store_id', $storeIds)
                ->groupBy('store_id')
                ->pluck('aggregate', 'store_id');

        $storeStats = [
            'total' => $stores->count(),
            'active' => $stores->where('is_active', true)->count(),
            'devices' => $deviceCounts->sum(),
            'catalogItems' => $catalogCounts->sum(),
        ];

        $editStore = $editId
            ? Store::query()->where('tenant_id', $user->tenant_id)->where('id', $editId)->first()
            : null;

        return view('stores.index', compact('stores', 'editStore', 'deviceCounts', 'catalogCounts', 'storeStats'));
    })->name('stores.index');

    Route::post('/stores', function (Request $request) {
        /** @var User $user */
        $user = Auth::user();

        $payload = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:60', Rule::unique('stores', 'code')],
            'timezone' => ['required', 'string', 'max:60'],
            'business_name' => ['nullable', 'string', 'max:160'],
            'terminal_name' => ['nullable', 'string', 'max:120'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Store::query()->create([
            'tenant_id' => $user->tenant_id,
            'name' => $payload['name'],
            'code' => $payload['code'],
            'timezone' => $payload['timezone'],
            'api_key' => bin2hex(random_bytes(16)),
            'catalog_version' => 1,
            'is_active' => (bool) ($payload['is_active'] ?? true),
            'branding_json' => json_encode([
                'business_name' => $payload['business_name'] ?: $payload['name'],
                'terminal_name' => $payload['terminal_name'] ?: $payload['name'],
            ]),
            'role_access_json' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('stores.index')->with('status', 'Sucursal creada correctamente.');
    })->name('stores.store');

    Route::put('/stores/{storeId}', function (Request $request, int $storeId) {
        /** @var User $user */
        $user = Auth::user();

        $store = Store::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('id', $storeId)
            ->firstOrFail();

        $payload = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:60', Rule::unique('stores', 'code')->ignore($storeId)],
            'timezone' => ['required', 'string', 'max:60'],
            'business_name' => ['nullable', 'string', 'max:160'],
            'terminal_name' => ['nullable', 'string', 'max:120'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $store->update([
            'name' => $payload['name'],
            'code' => $payload['code'],
            'timezone' => $payload['timezone'],
            'is_active' => (bool) ($payload['is_active'] ?? false),
            'branding_json' => json_encode([
                'business_name' => $payload['business_name'] ?: $payload['name'],
                'terminal_name' => $payload['terminal_name'] ?: $payload['name'],
            ]),
            'updated_at' => now(),
        ]);

        return redirect()->route('stores.index')->with('status', 'Sucursal actualizada.');
    })->name('stores.update');

    Route::post('/stores/{storeId}/rotate-key', function (int $storeId) {
        /** @var User $user */
        $user = Auth::user();

        $store = Store::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('id', $storeId)
            ->firstOrFail();

        $store->update([
            'api_key' => bin2hex(random_bytes(16)),
            'updated_at' => now(),
        ]);

        return redirect()->route('stores.index', ['edit' => $storeId])->with('status', 'Se renovo el acceso para nuevas cajas en esta sucursal.');
    })->name('stores.rotate-key');

    Route::get('/devices', function (Request $request) use ($resolveStoreForUser) {
        /** @var User $user */
        $user = Auth::user();
        $activeStore = $resolveStoreForUser($user);
        $search = trim((string) $request->query('q', ''));
        $storeFilter = $request->integer('store_id') ?: $activeStore->id;

        $deviceQuery = Device::query()
            ->where('tenant_id', $user->tenant_id)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('device_id', 'like', "%{$search}%")
                        ->orWhere('platform', 'like', "%{$search}%")
                        ->orWhere('app_mode', 'like', "%{$search}%");
                });
            })
            ->when($storeFilter, fn ($query) => $query->where('store_id', $storeFilter));

        $deviceStats = [
            'total' => (clone $deviceQuery)->count(),
            'ios' => (clone $deviceQuery)->where('platform', 'ios')->count(),
            'desktop' => (clone $deviceQuery)->where('platform', 'desktop')->count(),
            'seenToday' => (clone $deviceQuery)->where('last_seen_at', '>=', now()->subDay())->count(),
        ];

        $devices = $deviceQuery
            ->orderByDesc('last_seen_at')
            ->paginate(15)
            ->withQueryString();

        $storeOptions = Store::query()
            ->where('tenant_id', $user->tenant_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $storeNames = $storeOptions->pluck('name', 'id');
        $deviceSuggestions = Device::query()
            ->where('tenant_id', $user->tenant_id)
            ->when($storeFilter, fn ($query) => $query->where('store_id', $storeFilter))
            ->orderBy('name')
            ->limit(80)
            ->get(['device_id', 'name', 'platform'])
            ->map(function ($device) {
                return trim(collect([
                    $device->name,
                    $device->device_id,
                    $device->platform,
                ])->filter()->implode(' · '));
            })
            ->filter()
            ->unique()
            ->values();

        $tokenCounts = DB::table('personal_access_tokens')
            ->select('tokenable_id', DB::raw('count(*) as aggregate'))
            ->where('tokenable_type', Device::class)
            ->groupBy('tokenable_id')
            ->pluck('aggregate', 'tokenable_id');

        $deviceIds = $devices->getCollection()->pluck('device_id')->filter()->values();
        $syncHealthByDevice = $deviceIds->isEmpty()
            ? collect()
            : DB::table('sync_events')
                ->select(
                    'device_id',
                    DB::raw('count(*) as total_events'),
                    DB::raw('sum(case when applied_at is not null then 1 else 0 end) as applied_events'),
                    DB::raw('sum(case when apply_error is not null then 1 else 0 end) as conflict_events'),
                    DB::raw('max(received_at) as last_event_at')
                )
                ->where('tenant_id', $user->tenant_id)
                ->when($storeFilter, fn ($query) => $query->where('store_id', $storeFilter))
                ->whereIn('device_id', $deviceIds)
                ->groupBy('device_id')
                ->get()
                ->keyBy('device_id');

        $deviceStats['stale'] = (clone $deviceQuery)
            ->where(function ($query) {
                $query->whereNull('last_seen_at')
                    ->orWhere('last_seen_at', '<', now()->subDay());
            })
            ->count();

        $conflictedDeviceIds = DB::table('sync_events')
            ->where('tenant_id', $user->tenant_id)
            ->when($storeFilter, fn ($query) => $query->where('store_id', $storeFilter))
            ->whereNotNull('apply_error')
            ->distinct()
            ->pluck('device_id');

        $deviceStats['withConflicts'] = $conflictedDeviceIds->count();

        return view('devices.index', compact('devices', 'tokenCounts', 'syncHealthByDevice', 'deviceStats', 'storeOptions', 'storeNames', 'deviceSuggestions', 'search', 'storeFilter'));
    })->name('devices.index');

    Route::post('/devices/{deviceId}/revoke-token', function (int $deviceId) {
        /** @var User $user */
        $user = Auth::user();

        $device = Device::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('id', $deviceId)
            ->firstOrFail();

        DB::table('personal_access_tokens')
            ->where('tokenable_type', Device::class)
            ->where('tokenable_id', $device->id)
            ->delete();

        return redirect()->route('devices.index')->with('status', 'Se cerro el acceso vigente de esta caja.');
    })->name('devices.revoke-token');

    Route::get('/sync', function (Request $request) use ($resolveStoreForUser, $humanizeEventType, $humanizeAggregateType, $humanizeDeviceLabel, $describeSyncEvent) {
        /** @var User $user */
        $user = Auth::user();
        $activeStore = $resolveStoreForUser($user);
        $deviceFilter = trim((string) $request->query('device_id', ''));
        $eventFilter = trim((string) $request->query('event_type', ''));
        $storeFilter = $request->integer('store_id') ?: $activeStore->id;

        $baseQuery = DB::table('sync_events')
            ->where('tenant_id', $user->tenant_id)
            ->when($storeFilter, fn ($query) => $query->where('store_id', $storeFilter))
            ->when($deviceFilter !== '', fn ($query) => $query->where('device_id', $deviceFilter))
            ->when($eventFilter !== '', fn ($query) => $query->where('event_type', 'like', "%{$eventFilter}%"));

        $syncStats = [
            'total' => (clone $baseQuery)->count(),
            'devices' => (clone $baseQuery)->distinct('device_id')->count('device_id'),
            'last24h' => (clone $baseQuery)->where('received_at', '>=', now()->subDay())->count(),
            'conflicts' => (clone $baseQuery)->whereNotNull('apply_error')->count(),
            'applied' => (clone $baseQuery)->whereNotNull('applied_at')->count(),
            'lastEventAt' => (clone $baseQuery)->max('received_at'),
        ];

        $topEventTypes = (clone $baseQuery)
            ->select('event_type', DB::raw('count(*) as aggregate'))
            ->groupBy('event_type')
            ->orderByDesc('aggregate')
            ->limit(4)
            ->get()
            ->map(function ($row) use ($humanizeEventType) {
                $row->display_label = $humanizeEventType($row->event_type);

                return $row;
            });

        $eventTypeOptions = DB::table('sync_events')
            ->where('tenant_id', $user->tenant_id)
            ->when($storeFilter, fn ($query) => $query->where('store_id', $storeFilter))
            ->when($deviceFilter !== '', fn ($query) => $query->where('device_id', $deviceFilter))
            ->select('event_type')
            ->distinct()
            ->orderBy('event_type')
            ->get()
            ->map(function ($row) use ($humanizeEventType) {
                $row->display_label = $humanizeEventType($row->event_type);

                return $row;
            });

        $events = (clone $baseQuery)
            ->latest('received_at')
            ->paginate(20)
            ->withQueryString();

        $deviceOptions = DB::table('devices')
            ->where('tenant_id', $user->tenant_id)
            ->when($storeFilter, fn ($query) => $query->where('store_id', $storeFilter))
            ->orderBy('name')
            ->get(['device_id', 'name', 'platform'])
            ->map(function ($device) use ($humanizeDeviceLabel) {
                $device->display_name = $humanizeDeviceLabel($device->device_id, $device->name ?? null, $device->platform ?? null);

                return $device;
            });

        $storeOptions = Store::query()
            ->where('tenant_id', $user->tenant_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $storeNames = $storeOptions->pluck('name', 'id');
        $deviceMeta = Device::query()
            ->where('tenant_id', $user->tenant_id)
            ->whereIn('device_id', $events->getCollection()->pluck('device_id')->filter()->unique()->values())
            ->get(['device_id', 'name', 'platform'])
            ->keyBy('device_id');

        $events->setCollection(
            $events->getCollection()->map(function ($event) use ($storeNames, $deviceMeta, $humanizeEventType, $humanizeAggregateType, $humanizeDeviceLabel, $describeSyncEvent) {
                $payload = json_decode($event->payload_json, true) ?: [];
                $meta = $deviceMeta->get($event->device_id);
                $event->store_name = $storeNames[$event->store_id] ?? 'Sucursal sin nombre';
                $event->device_label = $humanizeDeviceLabel($event->device_id, $meta->name ?? null, $meta->platform ?? null);
                $event->event_label = $humanizeEventType($event->event_type);
                $event->aggregate_label = $humanizeAggregateType($event->aggregate_type);
                $event->detail_label = $describeSyncEvent($event->event_type, $payload);

                return $event;
            })
        );

        return view('sync.index', compact('events', 'deviceOptions', 'deviceFilter', 'eventFilter', 'eventTypeOptions', 'storeFilter', 'storeOptions', 'syncStats', 'topEventTypes'));
    })->name('sync.index');

    Route::get('/catalog', function (Request $request) use ($resolveStoreForUser) {
        /** @var User $user */
        $user = Auth::user();
        $store = $resolveStoreForUser($user);
        $search = trim((string) $request->query('q', ''));

        $catalogQuery = DB::table('cloud_catalog_products')
            ->where('store_id', $store->id)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('is_active')
            ->orderBy('name');

        $catalog = $catalogQuery->paginate(20)->withQueryString();
        $catalogStats = [
            'total' => DB::table('cloud_catalog_products')->where('store_id', $store->id)->count(),
            'active' => DB::table('cloud_catalog_products')->where('store_id', $store->id)->where('is_active', true)->count(),
            'inactive' => DB::table('cloud_catalog_products')->where('store_id', $store->id)->where('is_active', false)->count(),
            'lowStock' => DB::table('cloud_catalog_products')
                ->where('store_id', $store->id)
                ->where('is_active', true)
                ->where('track_inventory', true)
                ->whereColumn('stock_on_hand', '<=', 'reorder_point')
                ->count(),
            'lastUpdatedAt' => DB::table('cloud_catalog_products')
                ->where('store_id', $store->id)
                ->max('updated_at'),
        ];

        $catalogSuggestions = DB::table('cloud_catalog_products')
            ->where('store_id', $store->id)
            ->orderBy('name')
            ->limit(80)
            ->get(['name', 'sku', 'barcode'])
            ->flatMap(function ($product) {
                return collect([
                    $product->name,
                    $product->sku,
                    $product->barcode,
                ])->filter();
            })
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values();

        return view('catalog.index', [
            'catalog' => $catalog,
            'catalogStats' => $catalogStats,
            'catalogSuggestions' => $catalogSuggestions,
            'store' => $store,
            'search' => $search,
        ]);
    })->name('catalog.index');

    Route::get('/catalog/events', function (Request $request) use ($resolveStoreForUser, $streamCatalogVersionEvents) {
        /** @var User $user */
        $user = Auth::user();
        $requestedStoreId = $request->integer('store_id') ?: null;
        $store = $resolveStoreForUser($user, $requestedStoreId);

        return $streamCatalogVersionEvents(
            (int) $store->id,
            (string) $store->code,
            (int) $store->catalog_version,
        );
    })->name('catalog.events');

    Route::post('/catalog', function (Request $request) use ($bumpCatalogVersion, $resolveStoreForUser) {
        /** @var User $user */
        $user = Auth::user();
        $store = $resolveStoreForUser($user);

        $payload = $request->validate([
            'sku' => ['required', 'string', 'max:80', Rule::unique('cloud_catalog_products', 'sku')->where(fn ($q) => $q->where('store_id', $store->id))],
            'barcode' => ['nullable', 'string', 'max:120', Rule::unique('cloud_catalog_products', 'barcode')->where(fn ($q) => $q->where('store_id', $store->id))],
            'name' => ['required', 'string', 'max:160'],
            'price' => ['required', 'numeric', 'min:0'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'stock_on_hand' => ['required', 'integer', 'min:0'],
            'reorder_point' => ['required', 'integer', 'min:0'],
            'track_inventory' => ['nullable', 'boolean'],
        ]);

        $nextVersion = $bumpCatalogVersion($store->id);

        DB::table('cloud_catalog_products')->insert([
            'store_id' => $store->id,
            'sku' => $payload['sku'],
            'barcode' => $payload['barcode'] ?: null,
            'name' => $payload['name'],
            'price_cents' => (int) round(((float) $payload['price']) * 100),
            'cost_cents' => (int) round(((float) ($payload['cost'] ?? 0)) * 100),
            'stock_on_hand' => $payload['stock_on_hand'],
            'reorder_point' => $payload['reorder_point'],
            'track_inventory' => (bool) ($payload['track_inventory'] ?? false),
            'is_active' => true,
            'catalog_version' => $nextVersion,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('catalog.index')->with('status', 'Producto agregado al catalogo compartido.');
    })->name('catalog.store');

    Route::put('/catalog/{productId}', function (Request $request, int $productId) use ($bumpCatalogVersion, $resolveStoreForUser) {
        /** @var User $user */
        $user = Auth::user();
        $store = $resolveStoreForUser($user);

        $product = DB::table('cloud_catalog_products')
            ->where('store_id', $store->id)
            ->where('id', $productId)
            ->firstOrFail();

        $payload = $request->validate([
            'sku' => ['required', 'string', 'max:80', Rule::unique('cloud_catalog_products', 'sku')->where(fn ($q) => $q->where('store_id', $store->id))->ignore($productId)],
            'barcode' => ['nullable', 'string', 'max:120', Rule::unique('cloud_catalog_products', 'barcode')->where(fn ($q) => $q->where('store_id', $store->id))->ignore($productId)],
            'name' => ['required', 'string', 'max:160'],
            'price' => ['required', 'numeric', 'min:0'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'stock_on_hand' => ['required', 'integer', 'min:0'],
            'reorder_point' => ['required', 'integer', 'min:0'],
            'track_inventory' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $nextVersion = $bumpCatalogVersion($store->id);

        if ($product->sku !== $payload['sku'] || (($product->barcode ?? null) !== ($payload['barcode'] ?: null))) {
            DB::table('cloud_catalog_tombstones')->insert([
                'store_id' => $store->id,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'catalog_version' => $nextVersion,
                'deleted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('cloud_catalog_products')
            ->where('id', $product->id)
            ->update([
                'sku' => $payload['sku'],
                'barcode' => $payload['barcode'] ?: null,
                'name' => $payload['name'],
                'price_cents' => (int) round(((float) $payload['price']) * 100),
                'cost_cents' => (int) round(((float) ($payload['cost'] ?? 0)) * 100),
                'stock_on_hand' => $payload['stock_on_hand'],
                'reorder_point' => $payload['reorder_point'],
                'track_inventory' => (bool) ($payload['track_inventory'] ?? false),
                'is_active' => (bool) ($payload['is_active'] ?? false),
                'catalog_version' => $nextVersion,
                'updated_at' => now(),
            ]);

        return redirect()->route('catalog.index')->with('status', 'Producto actualizado en el catalogo compartido.');
    })->name('catalog.update');

    Route::post('/catalog/{productId}/toggle', function (int $productId) use ($bumpCatalogVersion, $resolveStoreForUser) {
        /** @var User $user */
        $user = Auth::user();
        $store = $resolveStoreForUser($user);

        $product = DB::table('cloud_catalog_products')
            ->where('store_id', $store->id)
            ->where('id', $productId)
            ->firstOrFail();

        $nextVersion = $bumpCatalogVersion($store->id);

        if ((bool) $product->is_active) {
            DB::table('cloud_catalog_tombstones')->insert([
                'store_id' => $store->id,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'catalog_version' => $nextVersion,
                'deleted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('cloud_catalog_products')
            ->where('id', $product->id)
            ->update([
                'is_active' => !((bool) $product->is_active),
                'catalog_version' => $nextVersion,
                'updated_at' => now(),
            ]);

        return redirect()->route(
            'catalog.index',
            ['page' => request()->query('page'), 'q' => request()->query('q')]
        )->with(
            'status',
            ((bool) $product->is_active ? 'Producto pausado' : 'Producto reactivado').' en el catalogo compartido.'
        );
    })->name('catalog.toggle');

    Route::delete('/catalog/{productId}', function (Request $request, int $productId) use ($bumpCatalogVersion, $resolveStoreForUser) {
        /** @var User $user */
        $user = Auth::user();
        $store = $resolveStoreForUser($user);

        $product = DB::table('cloud_catalog_products')
            ->where('store_id', $store->id)
            ->where('id', $productId)
            ->firstOrFail();

        $nextVersion = $bumpCatalogVersion($store->id);

        DB::table('cloud_catalog_tombstones')->insert([
            'store_id' => $store->id,
            'sku' => $product->sku,
            'barcode' => $product->barcode,
            'catalog_version' => $nextVersion,
            'deleted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('cloud_catalog_products')
            ->where('id', $product->id)
            ->delete();

        return redirect()->route(
            'catalog.index',
            ['page' => $request->query('page'), 'q' => $request->query('q')]
        )->with('status', 'Producto eliminado del catalogo compartido.');
    })->name('catalog.destroy');
});
