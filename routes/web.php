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
        $todayStart = now()->copy()->startOfDay();
        $sevenDaysAgo = now()->copy()->subDays(6)->startOfDay();

        $tenant = $tenantId ? DB::table('tenants')->where('id', $tenantId)->first() : null;

        $stats = [
            'stores' => DB::table('stores')->where('tenant_id', $tenantId)->count(),
            'devices' => DB::table('devices')->where('tenant_id', $tenantId)->count(),
            'onlineDevices' => DB::table('devices')
                ->where('tenant_id', $tenantId)
                ->where('store_id', $storeId)
                ->where('last_seen_at', '>=', now()->subMinutes(10))
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
            'salesToday' => DB::table('sync_events')
                ->where('tenant_id', $tenantId)
                ->where('store_id', $storeId)
                ->where('event_type', 'sale.created')
                ->where('received_at', '>=', $todayStart)
                ->count(),
        ];

        $recentDevices = Device::query()
            ->where('tenant_id', $tenantId)
            ->where('store_id', $storeId)
            ->latest('last_seen_at')
            ->limit(6)
            ->get();

        $recentEvents = DB::table('sync_events')
            ->where('tenant_id', $tenantId)
            ->where('store_id', $storeId)
            ->latest('received_at')
            ->limit(8)
            ->get();

        $dailyEventCounts = DB::table('sync_events')
            ->selectRaw('DATE(received_at) as day_key, count(*) as total_events')
            ->where('tenant_id', $tenantId)
            ->where('store_id', $storeId)
            ->where('received_at', '>=', $sevenDaysAgo)
            ->groupBy('day_key')
            ->pluck('total_events', 'day_key');

        $dailySalesCounts = DB::table('sync_events')
            ->selectRaw('DATE(received_at) as day_key, count(*) as total_sales')
            ->where('tenant_id', $tenantId)
            ->where('store_id', $storeId)
            ->where('event_type', 'sale.created')
            ->where('received_at', '>=', $sevenDaysAgo)
            ->groupBy('day_key')
            ->pluck('total_sales', 'day_key');

        $activityTimeline = collect(range(6, 0))->map(function (int $daysAgo) use ($dailyEventCounts, $dailySalesCounts) {
            $day = now()->copy()->subDays($daysAgo);
            $key = $day->toDateString();

            return [
                'label' => $day->format('d/m'),
                'events' => (int) ($dailyEventCounts[$key] ?? 0),
                'sales' => (int) ($dailySalesCounts[$key] ?? 0),
            ];
        })->values();

        $topEventMix = DB::table('sync_events')
            ->select('event_type', DB::raw('count(*) as aggregate'))
            ->where('tenant_id', $tenantId)
            ->where('store_id', $storeId)
            ->where('received_at', '>=', $sevenDaysAgo)
            ->groupBy('event_type')
            ->orderByDesc('aggregate')
            ->limit(5)
            ->get()
            ->map(function ($row) use ($humanizeEventType) {
                $row->label = $humanizeEventType($row->event_type);

                return $row;
            });

        $deviceActivity = DB::table('sync_events')
            ->select('device_id', DB::raw('count(*) as aggregate'))
            ->where('tenant_id', $tenantId)
            ->where('store_id', $storeId)
            ->where('received_at', '>=', $sevenDaysAgo)
            ->groupBy('device_id')
            ->orderByDesc('aggregate')
            ->limit(4)
            ->get();

        $deviceMetaById = Device::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('device_id', $deviceActivity->pluck('device_id')->filter()->values())
            ->get(['device_id', 'name', 'platform'])
            ->keyBy('device_id');

        $deviceActivity = $deviceActivity->map(function ($row) use ($deviceMetaById, $humanizeDeviceLabel) {
            $meta = $deviceMetaById->get($row->device_id);
            $row->label = $humanizeDeviceLabel($row->device_id, $meta->name ?? null, $meta->platform ?? null);

            return $row;
        });

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
            'recentDevices',
            'recentEvents',
            'activityTimeline',
            'topEventMix',
            'deviceActivity',
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

        return view('devices.index', compact('devices', 'tokenCounts', 'syncHealthByDevice', 'deviceStats', 'storeOptions', 'storeNames', 'search', 'storeFilter'));
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

        return view('sync.index', compact('events', 'deviceOptions', 'deviceFilter', 'eventFilter', 'storeFilter', 'storeOptions', 'syncStats', 'topEventTypes'));
    })->name('sync.index');

    Route::get('/catalog', function (Request $request) use ($resolveStoreForUser) {
        /** @var User $user */
        $user = Auth::user();
        $store = $resolveStoreForUser($user);
        $search = trim((string) $request->query('q', ''));
        $editId = $request->integer('edit');

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

        $editProduct = null;
        if ($editId) {
            $editProduct = DB::table('cloud_catalog_products')
                ->where('store_id', $store->id)
                ->where('id', $editId)
                ->first();
        }

        return view('catalog.index', [
            'catalog' => $catalog,
            'catalogStats' => $catalogStats,
            'editProduct' => $editProduct,
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
