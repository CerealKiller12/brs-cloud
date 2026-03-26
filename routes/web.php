<?php

use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Auth\NativeSocialAuthController;
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
        ->get(['id', 'name', 'code', 'catalog_version', 'timezone', 'is_active', 'branding_json']);

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

$cloudThemePresets = [
    'amber' => [
        'label' => 'Ambar',
        'description' => 'Base clara con tonos miel y contraste calido.',
        'accent' => '#8a6343',
        'sidebar' => '#1f1712',
        'panel' => '#f9f2eb',
        'vars' => [
            'bg' => '#fffaf3',
            'bg_soft' => '#f6efe6',
            'bg_strong' => '#efe3d4',
            'panel' => 'rgba(255,255,255,.94)',
            'panel_soft' => '#f9f2eb',
            'muted' => '#6f5846',
            'text' => '#231910',
            'accent' => '#8a6343',
            'accent_soft' => '#b98b62',
            'line' => '#e2d2c0',
            'soft' => '#efe2d3',
            'sidebar_bg' => 'linear-gradient(180deg, #1f1712 0%, #2f241d 100%)',
            'sidebar_text' => '#f7efe7',
            'sidebar_muted' => '#d4b89a',
            'sidebar_panel' => 'rgba(255,255,255,.06)',
            'nav_idle' => 'rgba(255,255,255,.04)',
            'nav_hover' => 'rgba(255,255,255,.08)',
            'nav_active' => '#4a3428',
            'sidebar_button' => 'rgba(255,255,255,.1)',
        ],
    ],
    'ocean' => [
        'label' => 'Costa',
        'description' => 'Azules suaves y un contraste limpio para operar todo el dia.',
        'accent' => '#1f3244',
        'sidebar' => '#162330',
        'panel' => '#f8fbfd',
        'vars' => [
            'bg' => '#f3f6f9',
            'bg_soft' => '#eef4f9',
            'bg_strong' => '#e9f0f6',
            'panel' => 'rgba(255,255,255,.94)',
            'panel_soft' => '#f8fbfd',
            'muted' => '#6a7a8f',
            'text' => '#213043',
            'accent' => '#1f3244',
            'accent_soft' => '#31506b',
            'line' => '#d8e0e8',
            'soft' => '#edf3f8',
            'sidebar_bg' => 'linear-gradient(180deg, #162330 0%, #1e3142 100%)',
            'sidebar_text' => '#eef4f8',
            'sidebar_muted' => '#b7c8d7',
            'sidebar_panel' => 'rgba(255,255,255,.06)',
            'nav_idle' => 'rgba(255,255,255,.04)',
            'nav_hover' => 'rgba(255,255,255,.08)',
            'nav_active' => '#29475f',
            'sidebar_button' => 'rgba(255,255,255,.1)',
        ],
    ],
    'forest' => [
        'label' => 'Bosque',
        'description' => 'Verdes profundos con una base suave y sobria para operacion continua.',
        'accent' => '#244532',
        'sidebar' => '#182a21',
        'panel' => '#f4faf5',
        'vars' => [
            'bg' => '#f3f7f2',
            'bg_soft' => '#eef5ee',
            'bg_strong' => '#e5efe6',
            'panel' => 'rgba(255,255,255,.95)',
            'panel_soft' => '#f3f8f3',
            'muted' => '#667d70',
            'text' => '#213128',
            'accent' => '#244532',
            'accent_soft' => '#3d6750',
            'line' => '#d4e1d8',
            'soft' => '#e9f2eb',
            'sidebar_bg' => 'linear-gradient(180deg, #182a21 0%, #22392d 100%)',
            'sidebar_text' => '#eef7ef',
            'sidebar_muted' => '#bfd5c5',
            'sidebar_panel' => 'rgba(255,255,255,.06)',
            'nav_idle' => 'rgba(255,255,255,.04)',
            'nav_hover' => 'rgba(255,255,255,.08)',
            'nav_active' => '#315541',
            'sidebar_button' => 'rgba(255,255,255,.1)',
        ],
    ],
    'sunset' => [
        'label' => 'Atardecer',
        'description' => 'Arena clara con acentos terracota para un tono mas calido.',
        'accent' => '#6a3f2d',
        'sidebar' => '#37251d',
        'panel' => '#fdf8f2',
        'vars' => [
            'bg' => '#faf4ee',
            'bg_soft' => '#f5eee7',
            'bg_strong' => '#eee3d7',
            'panel' => 'rgba(255,255,255,.95)',
            'panel_soft' => '#fbf3eb',
            'muted' => '#7d6a5d',
            'text' => '#34261f',
            'accent' => '#6a3f2d',
            'accent_soft' => '#8c5c49',
            'line' => '#e7d8cc',
            'soft' => '#f1e7dc',
            'sidebar_bg' => 'linear-gradient(180deg, #37251d 0%, #4a3126 100%)',
            'sidebar_text' => '#f9f1ea',
            'sidebar_muted' => '#dcc4b4',
            'sidebar_panel' => 'rgba(255,255,255,.06)',
            'nav_idle' => 'rgba(255,255,255,.04)',
            'nav_hover' => 'rgba(255,255,255,.08)',
            'nav_active' => '#7a4d38',
            'sidebar_button' => 'rgba(255,255,255,.1)',
        ],
    ],
    'ember' => [
        'label' => 'Ascua',
        'description' => 'Naranjas suaves y contraste terracota para un tono energico.',
        'accent' => '#a65d43',
        'sidebar' => '#2d1713',
        'panel' => '#fbf4ee',
        'vars' => [
            'bg' => '#fff7f2',
            'bg_soft' => '#f5e7e0',
            'bg_strong' => '#ead4ca',
            'panel' => 'rgba(255,255,255,.94)',
            'panel_soft' => '#fbf4ee',
            'muted' => '#73574b',
            'text' => '#2a1e1a',
            'accent' => '#a65d43',
            'accent_soft' => '#c68368',
            'line' => '#e6d1c8',
            'soft' => '#f4e9de',
            'sidebar_bg' => 'linear-gradient(180deg, #2d1713 0%, #41231d 100%)',
            'sidebar_text' => '#fff0eb',
            'sidebar_muted' => '#e1b4a6',
            'sidebar_panel' => 'rgba(255,255,255,.06)',
            'nav_idle' => 'rgba(255,255,255,.04)',
            'nav_hover' => 'rgba(255,255,255,.08)',
            'nav_active' => '#6a3d31',
            'sidebar_button' => 'rgba(255,255,255,.1)',
        ],
    ],
    'sol' => [
        'label' => 'Sol',
        'description' => 'Luminoso, claro y alto en energia para un look mas vivo.',
        'accent' => '#a67e1d',
        'sidebar' => '#362709',
        'panel' => '#fff7d8',
        'vars' => [
            'bg' => '#fffdf0',
            'bg_soft' => '#f9efc9',
            'bg_strong' => '#efd88f',
            'panel' => 'rgba(255,251,233,.92)',
            'panel_soft' => '#fff7d8',
            'muted' => '#78612d',
            'text' => '#34270a',
            'accent' => '#a67e1d',
            'accent_soft' => '#d0a63f',
            'line' => '#ead694',
            'soft' => '#f5e7b7',
            'sidebar_bg' => 'linear-gradient(180deg, #362709 0%, #4b370d 100%)',
            'sidebar_text' => '#fff8df',
            'sidebar_muted' => '#f2d07f',
            'sidebar_panel' => 'rgba(255,255,255,.06)',
            'nav_idle' => 'rgba(255,255,255,.04)',
            'nav_hover' => 'rgba(255,255,255,.08)',
            'nav_active' => '#6b5213',
            'sidebar_button' => 'rgba(255,255,255,.1)',
        ],
    ],
    'crimson' => [
        'label' => 'Carmesí',
        'description' => 'Borgoña suave con tonos claros para una identidad mas intensa.',
        'accent' => '#b14a62',
        'sidebar' => '#2f0e15',
        'panel' => '#faeef1',
        'vars' => [
            'bg' => '#fff6f7',
            'bg_soft' => '#f4dbe0',
            'bg_strong' => '#e7c1ca',
            'panel' => 'rgba(255,252,253,.94)',
            'panel_soft' => '#faeef1',
            'muted' => '#785462',
            'text' => '#2f1620',
            'accent' => '#b14a62',
            'accent_soft' => '#cf6c84',
            'line' => '#e7ccd4',
            'soft' => '#f2dde2',
            'sidebar_bg' => 'linear-gradient(180deg, #2f0e15 0%, #451823 100%)',
            'sidebar_text' => '#fff0f3',
            'sidebar_muted' => '#e6a8b6',
            'sidebar_panel' => 'rgba(255,255,255,.06)',
            'nav_idle' => 'rgba(255,255,255,.04)',
            'nav_hover' => 'rgba(255,255,255,.08)',
            'nav_active' => '#6d2738',
            'sidebar_button' => 'rgba(255,255,255,.1)',
        ],
    ],
    'midnight' => [
        'label' => 'Medianoche',
        'description' => 'Azul profundo con superficies frias y contraste mas marcado.',
        'accent' => '#203a67',
        'sidebar' => '#0f1724',
        'panel' => '#f3f6fb',
        'vars' => [
            'bg' => '#eef2f8',
            'bg_soft' => '#e8edf6',
            'bg_strong' => '#dde5f0',
            'panel' => 'rgba(255,255,255,.94)',
            'panel_soft' => '#f4f7fb',
            'muted' => '#687891',
            'text' => '#202d42',
            'accent' => '#203a67',
            'accent_soft' => '#35558c',
            'line' => '#d4dcea',
            'soft' => '#e8eef7',
            'sidebar_bg' => 'linear-gradient(180deg, #0f1724 0%, #182233 100%)',
            'sidebar_text' => '#eef4fb',
            'sidebar_muted' => '#b7c4d6',
            'sidebar_panel' => 'rgba(255,255,255,.06)',
            'nav_idle' => 'rgba(255,255,255,.04)',
            'nav_hover' => 'rgba(255,255,255,.08)',
            'nav_active' => '#253756',
            'sidebar_button' => 'rgba(255,255,255,.1)',
        ],
    ],
];

$resolveCloudTheme = function (?array $branding) use ($cloudThemePresets) {
    $preset = (string) data_get($branding ?? [], 'cloud_theme_preset', 'ocean');
    $selected = $cloudThemePresets[$preset] ?? $cloudThemePresets['ocean'];

    return ['id' => $preset, 'label' => $selected['label']] + $selected['vars'];
};

View::composer('layouts.app', function ($view) use ($buildStoreContext, $resolveCloudTheme) {
    if (!Auth::check()) {
        return;
    }

    /** @var User|null $user */
    $user = Auth::user();

    if (!$user?->tenant_id) {
        return;
    }

    [$activeStore, $availableStores] = $buildStoreContext($user);
    $branding = is_array($activeStore->branding_json) ? $activeStore->branding_json : (json_decode($activeStore->branding_json ?? '[]', true) ?: []);

    $view->with([
        'cloudActiveStore' => $activeStore,
        'cloudAvailableStores' => $availableStores,
        'cloudTheme' => $resolveCloudTheme($branding),
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

$subscriptionPlanOptions = ['starter', 'growth', 'pro', 'enterprise'];
$subscriptionStatusOptions = ['trialing', 'active', 'grace_period', 'past_due', 'paused', 'canceled'];

$subscriptionStatusPill = function (?string $status): string {
    return match ($status) {
        'active', 'grace_period' => 'success',
        'past_due', 'paused' => 'warning',
        'canceled' => 'danger',
        default => '',
    };
};

$adminTenantListingQuery = function () {
    return Tenant::query()
        ->withCount(['stores', 'devices', 'users'])
        ->addSelect([
            'owner_name' => User::query()
                ->select('name')
                ->whereColumn('tenant_id', 'tenants.id')
                ->orderByRaw("case when role = 'owner' then 0 else 1 end")
                ->orderBy('id')
                ->limit(1),
            'owner_email' => User::query()
                ->select('email')
                ->whereColumn('tenant_id', 'tenants.id')
                ->orderByRaw("case when role = 'owner' then 0 else 1 end")
                ->orderBy('id')
                ->limit(1),
            'sync_events_count' => DB::table('sync_events')
                ->selectRaw('count(*)')
                ->whereColumn('tenant_id', 'tenants.id'),
            'last_event_at' => DB::table('sync_events')
                ->selectRaw('max(received_at)')
                ->whereColumn('tenant_id', 'tenants.id'),
        ])
        ->orderByDesc('created_at');
};

$attachAdminTenantMeta = function ($rows) use ($subscriptionStatusPill) {
    $decorate = fn ($tenant) => tap($tenant, function ($item) use ($subscriptionStatusPill) {
        $item->status_pill = $subscriptionStatusPill($item->subscription_status);
    });

    if (method_exists($rows, 'getCollection') && method_exists($rows, 'setCollection')) {
        $rows->setCollection($rows->getCollection()->map($decorate));

        return $rows;
    }

    return collect($rows)->map($decorate);
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
    $isAdminHostRequest = ($adminHost = trim((string) config('app.admin_host', ''))) !== ''
        && strcasecmp(request()->getHost(), $adminHost) === 0;

    if (!Auth::check()) {
        return $isAdminHostRequest
            ? redirect()->route('login')
            : view('landing');
    }

    /** @var User|null $user */
    $user = Auth::user();

    return $user?->is_platform_admin && $isAdminHostRequest
        ? redirect()->route('admin.dashboard')
        : redirect()->route('dashboard');
});

Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])->name('social.redirect');
Route::match(['GET', 'POST'], '/auth/{provider}/callback', [SocialAuthController::class, 'callback'])->name('social.callback');
Route::get('/auth/native/{provider}/redirect', [NativeSocialAuthController::class, 'redirect'])->name('social.native.redirect');
Route::match(['GET', 'POST'], '/auth/native/{provider}/callback', [NativeSocialAuthController::class, 'callback'])->name('social.native.callback');

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
                ->withErrors(['email' => 'Credenciales invalidas para Venpi Cloud.']);
        }

        $request->session()->regenerate();

        $user = Auth::user();
        $adminHost = trim((string) config('app.admin_host', ''));
        $isAdminHostRequest = $adminHost !== '' && strcasecmp($request->getHost(), $adminHost) === 0;
        $defaultRoute = $user?->tenant?->onboarding_completed_at ? route('dashboard') : route('onboarding.index');

        return redirect()->intended(
            $user?->is_platform_admin && $isAdminHostRequest
                ? route('admin.dashboard')
                : $defaultRoute
        );
    })->name('login.submit');
});

$registerAdminRoutes = function () use ($adminTenantListingQuery, $attachAdminTenantMeta, $subscriptionPlanOptions, $subscriptionStatusOptions, $subscriptionStatusPill) {
    Route::get('/', function () use ($adminTenantListingQuery, $attachAdminTenantMeta) {
        $baseQuery = $adminTenantListingQuery();

        $stats = [
            'tenants' => Tenant::query()->count(),
            'activeTenants' => Tenant::query()->where('is_active', true)->count(),
            'trialingTenants' => Tenant::query()->where('subscription_status', 'trialing')->count(),
            'paidTenants' => Tenant::query()->whereIn('subscription_status', ['active', 'grace_period'])->count(),
            'attentionTenants' => Tenant::query()
                ->where(function ($query) {
                    $query->whereIn('subscription_status', ['past_due', 'paused', 'canceled'])
                        ->orWhere('is_active', false);
                })
                ->count(),
            'stores' => Store::query()->count(),
            'devices' => Device::query()->count(),
            'users' => User::query()->count(),
            'syncEvents' => DB::table('sync_events')->count(),
        ];

        $recentTenants = $attachAdminTenantMeta((clone $baseQuery)->limit(5)->get());
        $attentionTenants = $attachAdminTenantMeta(
            (clone $baseQuery)
                ->where(function ($query) {
                    $query->whereIn('subscription_status', ['past_due', 'paused', 'canceled'])
                        ->orWhere('is_active', false);
                })
                ->limit(5)
                ->get()
        );

        return view('admin.dashboard', compact('stats', 'recentTenants', 'attentionTenants'));
    })->name('dashboard');

    Route::get('/clients', function (Request $request) use ($adminTenantListingQuery, $attachAdminTenantMeta, $subscriptionPlanOptions, $subscriptionStatusOptions) {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => trim((string) $request->query('status', '')),
            'plan' => trim((string) $request->query('plan', '')),
        ];

        $query = $adminTenantListingQuery();

        if ($filters['q'] !== '') {
            $search = '%'.$filters['q'].'%';

            $query->where(function ($tenantQuery) use ($search) {
                $tenantQuery
                    ->where('tenants.name', 'like', $search)
                    ->orWhere('tenants.slug', 'like', $search)
                    ->orWhereExists(function ($userQuery) use ($search) {
                        $userQuery->selectRaw('1')
                            ->from('users')
                            ->whereColumn('users.tenant_id', 'tenants.id')
                            ->where(function ($match) use ($search) {
                                $match->where('users.name', 'like', $search)
                                    ->orWhere('users.email', 'like', $search);
                            });
                    });
            });
        }

        if ($filters['status'] !== '') {
            $query->where('subscription_status', $filters['status']);
        }

        if ($filters['plan'] !== '') {
            $query->where('plan_code', $filters['plan']);
        }

        $clients = $attachAdminTenantMeta($query->paginate(16)->withQueryString());
        $statusOptions = $subscriptionStatusOptions;
        $planOptions = $subscriptionPlanOptions;

        return view('admin.clients.index', compact('clients', 'filters', 'statusOptions', 'planOptions'));
    })->name('clients.index');

    Route::get('/clients/{tenantId}', function (int $tenantId) use ($subscriptionPlanOptions, $subscriptionStatusOptions, $subscriptionStatusPill) {
        $tenant = Tenant::query()->findOrFail($tenantId);
        $owner = User::query()
            ->where('tenant_id', $tenantId)
            ->orderByRaw("case when role = 'owner' then 0 else 1 end")
            ->orderBy('id')
            ->first();

        $stores = Store::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'catalog_version', 'is_active']);

        $users = User::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role', 'is_active']);

        $stats = [
            'stores' => Store::query()->where('tenant_id', $tenantId)->count(),
            'devices' => Device::query()->where('tenant_id', $tenantId)->count(),
            'users' => User::query()->where('tenant_id', $tenantId)->count(),
            'syncEvents' => DB::table('sync_events')->where('tenant_id', $tenantId)->count(),
            'lastEventAt' => DB::table('sync_events')->where('tenant_id', $tenantId)->max('received_at'),
        ];

        $planOptions = $subscriptionPlanOptions;
        $statusOptions = $subscriptionStatusOptions;
        $statusPill = $subscriptionStatusPill($tenant->subscription_status);

        return view('admin.clients.show', compact('tenant', 'owner', 'stores', 'users', 'stats', 'planOptions', 'statusOptions', 'statusPill'));
    })->name('clients.show');

    Route::post('/clients/{tenantId}/profile', function (Request $request, int $tenantId) {
        $tenant = Tenant::query()->findOrFail($tenantId);
        $normalizedSlug = Str::slug(trim((string) $request->input('slug'))) ?: $tenant->slug;
        $request->merge(['slug' => $normalizedSlug]);

        $payload = $request->validate([
            'tenant_name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:120', Rule::unique('tenants', 'slug')->ignore($tenant->id)],
        ]);

        $tenant->update([
            'name' => $payload['tenant_name'],
            'slug' => $normalizedSlug,
        ]);

        return redirect()->route('admin.clients.show', $tenant->id)->with('status', 'Datos del cliente actualizados.');
    })->name('clients.profile.update');

    Route::post('/clients/{tenantId}/subscription', function (Request $request, int $tenantId) use ($subscriptionPlanOptions, $subscriptionStatusOptions) {
        $tenant = Tenant::query()->findOrFail($tenantId);

        $payload = $request->validate([
            'plan_code' => ['required', 'string', Rule::in($subscriptionPlanOptions)],
            'subscription_status' => ['required', 'string', Rule::in($subscriptionStatusOptions)],
            'trial_ends_at' => ['nullable', 'date'],
            'is_active' => ['required', 'boolean'],
        ]);

        $tenant->update([
            'plan_code' => $payload['plan_code'],
            'subscription_status' => $payload['subscription_status'],
            'trial_ends_at' => $payload['trial_ends_at'] ?: null,
            'is_active' => (bool) $payload['is_active'],
        ]);

        return redirect()->route('admin.clients.show', $tenant->id)->with('status', 'Subscripcion del cliente actualizada.');
    })->name('clients.subscription.update');

    Route::get('/subscriptions', function (Request $request) use ($adminTenantListingQuery, $attachAdminTenantMeta, $subscriptionPlanOptions, $subscriptionStatusOptions) {
        $filters = [
            'status' => trim((string) $request->query('status', '')),
            'plan' => trim((string) $request->query('plan', '')),
        ];

        $query = $adminTenantListingQuery();

        if ($filters['status'] !== '') {
            $query->where('subscription_status', $filters['status']);
        }

        if ($filters['plan'] !== '') {
            $query->where('plan_code', $filters['plan']);
        }

        $subscriptions = $attachAdminTenantMeta($query->paginate(20)->withQueryString());
        $statusOptions = $subscriptionStatusOptions;
        $planOptions = $subscriptionPlanOptions;

        return view('admin.subscriptions.index', compact('subscriptions', 'filters', 'statusOptions', 'planOptions'));
    })->name('subscriptions.index');
};

$adminHost = trim((string) config('app.admin_host', ''));

if ($adminHost !== '') {
    Route::middleware(['auth', 'platform.admin'])
        ->domain($adminHost)
        ->name('admin.')
        ->group($registerAdminRoutes);

    Route::middleware(['auth', 'platform.admin'])->prefix('admin')->group(function () {
        Route::get('/', fn () => redirect()->route('admin.dashboard'));
        Route::get('/clients', fn () => redirect()->route('admin.clients.index', request()->query()));
        Route::get('/clients/{tenantId}', fn (int $tenantId) => redirect()->route('admin.clients.show', $tenantId));
        Route::get('/subscriptions', fn () => redirect()->route('admin.subscriptions.index', request()->query()));
    });
} else {
    Route::middleware(['auth', 'platform.admin'])
        ->prefix('admin')
        ->name('admin.')
        ->group($registerAdminRoutes);
}

Route::middleware(['auth', 'cloud.surface'])->group(function () use ($resolveStoreForUser, $bumpCatalogVersion, $streamCatalogVersionEvents, $humanizeEventType, $humanizeAggregateType, $humanizeDeviceLabel, $describeSyncEvent, $cloudThemePresets) {
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

    Route::get('/settings', function () use ($cloudThemePresets, $resolveStoreForUser) {
        /** @var User $user */
        $user = Auth::user();
        $tenant = Tenant::query()->findOrFail($user->tenant_id);
        $store = Store::query()->where('tenant_id', $user->tenant_id)->findOrFail($user->store_id);
        $branding = is_array($store->branding_json) ? $store->branding_json : (json_decode($store->branding_json ?? '[]', true) ?: []);
        $themeStore = $resolveStoreForUser($user);
        $themeBranding = is_array($themeStore->branding_json) ? $themeStore->branding_json : (json_decode($themeStore->branding_json ?? '[]', true) ?: []);

        return view('settings', compact('user', 'tenant', 'store', 'branding', 'cloudThemePresets', 'themeStore', 'themeBranding'));
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

    Route::post('/settings/theme', function (Request $request) use ($cloudThemePresets, $resolveStoreForUser) {
        /** @var User $user */
        $user = Auth::user();
        $store = $resolveStoreForUser($user);
        $branding = is_array($store->branding_json) ? $store->branding_json : (json_decode($store->branding_json ?? '[]', true) ?: []);

        $payload = $request->validate([
            'cloud_theme_preset' => ['required', 'string', Rule::in(array_keys($cloudThemePresets))],
        ]);

        $branding['cloud_theme_preset'] = $payload['cloud_theme_preset'];

        $store->update([
            'branding_json' => $branding,
        ]);

        return redirect()->route('settings.index')->with('status', "Tema visual actualizado para {$store->name}.");
    })->name('settings.theme');

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
                        'name' => trim((string) ($item['productName'] ?? $item['name'] ?? '')),
                        'quantity' => (int) ($item['quantity'] ?? 0),
                    ];
                });
            })
            ->filter(fn ($item) => $item['sku'] !== '' && $item['quantity'] > 0)
            ->groupBy(fn ($item) => mb_strtolower($item['sku']))
            ->map(function ($rows, string $skuKey) use ($catalogProducts) {
                $sku = (string) ($rows->first()['sku'] ?? $skuKey);
                $fallbackName = collect($rows)
                    ->pluck('name')
                    ->map(fn ($name) => trim((string) $name))
                    ->first(fn ($name) => $name !== '');

                return (object) [
                    'sku' => $sku,
                    'name' => $catalogProducts[$skuKey] ?? $fallbackName ?? $sku,
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
