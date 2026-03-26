<?php

use App\Http\Controllers\Auth\NativeSocialAuthController;
use App\Models\Device;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
use Laravel\Sanctum\PersonalAccessToken;

$supportedPlatforms = [
    'windows-x64',
    'windows-arm64',
    'linux-x64',
    'linux-arm64',
    'linux-armhf',
    'macos-x64',
    'macos-arm64',
    'ios',
    'android',
];

$streamCatalogVersionEvents = function (int $storeId, string $storeCode, int $initialCatalogVersion) {
    return response()->stream(function () use ($storeId, $storeCode, $initialCatalogVersion) {
        ignore_user_abort(true);
        @set_time_limit(0);

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

Route::get('/health', function () {
    return response()->json([
        'ok' => true,
        'service' => 'venpi-cloud',
        'timestamp' => now()->toIso8601String(),
    ]);
});

Route::post('/auth/native/consume', [NativeSocialAuthController::class, 'consume']);

$resolveStoreContext = function (Request $request) {
    $actor = $request->user();

    if (!$actor) {
        $bearerToken = trim((string) $request->bearerToken());

        if ($bearerToken !== '') {
            $token = PersonalAccessToken::findToken($bearerToken);
            $actor = $token?->tokenable;
        }
    }

    if (!$actor) {
        $deviceToken = trim((string) $request->query('device_token', ''));

        if ($deviceToken !== '') {
            $token = PersonalAccessToken::findToken($deviceToken);
            $actor = $token?->tokenable;
        }
    }

    if ($actor instanceof Device) {
        $store = DB::table('stores')
            ->join('tenants', 'tenants.id', '=', 'stores.tenant_id')
            ->select([
                'stores.id as store_row_id',
                'stores.tenant_id',
                'stores.name as store_name',
                'stores.code as store_code',
                'stores.timezone',
                'stores.api_key',
                'stores.catalog_version',
                'stores.is_active as store_is_active',
                'stores.branding_json',
                'stores.role_access_json',
                'tenants.name as tenant_name',
                'tenants.slug as tenant_slug',
                'tenants.plan_code',
                'tenants.subscription_status',
                'tenants.is_active as tenant_is_active',
            ])
            ->where('stores.id', $actor->store_id)
            ->first();

        if ($store) {
            return $store;
        }
    }

    if ($actor instanceof User && $actor->store_id) {
        $store = DB::table('stores')
            ->join('tenants', 'tenants.id', '=', 'stores.tenant_id')
            ->select([
                'stores.id as store_row_id',
                'stores.tenant_id',
                'stores.name as store_name',
                'stores.code as store_code',
                'stores.timezone',
                'stores.api_key',
                'stores.catalog_version',
                'stores.is_active as store_is_active',
                'stores.branding_json',
                'stores.role_access_json',
                'tenants.name as tenant_name',
                'tenants.slug as tenant_slug',
                'tenants.plan_code',
                'tenants.subscription_status',
                'tenants.is_active as tenant_is_active',
            ])
            ->where('stores.id', $actor->store_id)
            ->first();

        if ($store) {
            return $store;
        }
    }

    $storeCode = $request->header('X-VENPI-Store-Code', $request->input('store_code'));
    $storeKey = $request->header('X-VENPI-Store-Key', $request->input('store_key'));

    if (!$storeCode || !$storeKey) {
        abort(response()->json([
            'message' => 'Debes enviar X-VENPI-Store-Code y X-VENPI-Store-Key.',
        ], 401));
    }

    $store = DB::table('stores')
        ->join('tenants', 'tenants.id', '=', 'stores.tenant_id')
        ->select([
            'stores.id as store_row_id',
            'stores.tenant_id',
            'stores.name as store_name',
            'stores.code as store_code',
            'stores.timezone',
            'stores.api_key',
            'stores.catalog_version',
            'stores.is_active as store_is_active',
            'stores.branding_json',
            'stores.role_access_json',
            'tenants.name as tenant_name',
            'tenants.slug as tenant_slug',
            'tenants.plan_code',
            'tenants.subscription_status',
            'tenants.is_active as tenant_is_active',
        ])
        ->where('stores.code', $storeCode)
        ->first();

    if (!$store || $store->api_key !== $storeKey) {
        abort(response()->json([
            'message' => 'No pude autenticar la tienda en Venpi Cloud.',
        ], 403));
    }

    if (!(bool) $store->store_is_active || !(bool) $store->tenant_is_active) {
        abort(response()->json([
            'message' => 'La tienda o el tenant estan inactivos en Venpi Cloud.',
        ], 403));
    }

    return $store;
};

$issueDeviceTokenForStore = function (object $store, array $payload, Request $request) {
    $now = now();

    DB::table('devices')->updateOrInsert(
        ['device_id' => $payload['device_id']],
        [
            'tenant_id' => $store->tenant_id,
            'store_id' => $store->store_row_id,
            'name' => $payload['name'] ?? null,
            'platform' => $payload['platform'],
            'device_type' => $payload['device_type'] ?? null,
            'app_mode' => $payload['app_mode'] ?? null,
            'channel' => 'stable',
            'branch_name' => $store->store_name,
            'current_version' => $payload['current_version'] ?? null,
            'ip_address' => $request->ip(),
            'metadata_json' => array_key_exists('metadata', $payload) ? json_encode($payload['metadata']) : null,
            'last_seen_at' => $now,
            'updated_at' => $now,
            'created_at' => DB::raw('coalesce(created_at, CURRENT_TIMESTAMP)'),
        ]
    );

    /** @var Device $device */
    $device = Device::query()->where('device_id', $payload['device_id'])->firstOrFail();
    $device->tokens()->where('name', 'device-sync')->delete();
    $token = $device->createToken('device-sync', ['catalog:read', 'events:write', 'bootstrap:read'])->plainTextToken;

    return response()->json([
        'token' => $token,
        'deviceId' => $device->device_id,
        'storeCode' => $store->store_code,
        'issuedAt' => $now->toIso8601String(),
    ]);
};

$buildDashboardSummaryForStore = function (int $tenantId, int $storeId) {
    $now = now();
    $todayStart = now()->copy()->startOfDay();
    $yesterdayStart = $todayStart->copy()->subDay();
    $sevenDaysAgo = now()->copy()->subDays(6)->startOfDay();
    $thirtyDaysAgo = now()->copy()->subDays(29)->startOfDay();

    $store = DB::table('stores')
        ->where('tenant_id', $tenantId)
        ->where('id', $storeId)
        ->select(['id', 'name', 'code', 'catalog_version'])
        ->first();

    abort_unless($store, 404, 'No pude encontrar esa sucursal en Venpi Cloud.');

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
                    ? Carbon::parse($payload['createdAt'])
                    : (!empty($event->occurred_at)
                        ? Carbon::parse($event->occurred_at)
                        : Carbon::parse($event->received_at));
            } catch (\Throwable) {
                $occurredAt = Carbon::parse($event->received_at);
            }

            $items = $payload['items'] ?? data_get($payload, 'sale.items', []);

            return (object) [
                'device_id' => $event->device_id,
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
            'label' => $day->locale('es_MX')->translatedFormat('D j'),
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

            return [
                'key' => $key,
                'label' => $label,
                'tickets' => (int) $rows->count(),
                'amountCents' => (int) $rows->sum('total_cents'),
            ];
        })
        ->filter(fn ($row) => $row['tickets'] > 0 || $row['amountCents'] > 0)
        ->values();

    $catalogProducts = DB::table('cloud_catalog_products')
        ->where('store_id', $storeId)
        ->get(['sku', 'name'])
        ->mapWithKeys(fn ($product) => [[mb_strtolower(trim((string) $product->sku)) => $product->name]]);

    $topProducts = $salesLast7Days
        ->flatMap(function ($sale) {
            $saleItems = collect($sale->items)->map(function ($item) {
                return [
                    'sku' => trim((string) ($item['productSku'] ?? '')),
                    'name' => trim((string) ($item['productName'] ?? $item['name'] ?? '')),
                    'quantity' => (int) ($item['quantity'] ?? 0),
                    'amountCents' => (int) ($item['totalCents'] ?? (($item['unitPriceCents'] ?? 0) * ((int) ($item['quantity'] ?? 0)))),
                ];
            })->values();

            $hasAmounts = $saleItems->contains(fn ($item) => $item['amountCents'] > 0);

            if (!$hasAmounts && $saleItems->isNotEmpty()) {
                $ticketTotal = max(0, (int) $sale->total_cents);
                $totalQuantity = max(1, (int) $saleItems->sum('quantity'));

                $saleItems = $saleItems->map(function ($item) use ($ticketTotal, $totalQuantity) {
                    $quantity = max(0, (int) $item['quantity']);
                    $amount = $quantity > 0 ? (int) round(($ticketTotal * $quantity) / $totalQuantity) : 0;

                    return [
                        'sku' => $item['sku'],
                        'name' => $item['name'],
                        'quantity' => $quantity,
                        'amountCents' => $amount,
                    ];
                })->values();
            }

            return $saleItems;
        })
        ->filter(fn ($item) => $item['sku'] !== '' && $item['quantity'] > 0)
        ->groupBy(fn ($item) => mb_strtolower($item['sku']))
        ->map(function ($rows, string $skuKey) use ($catalogProducts) {
            $sku = (string) ($rows->first()['sku'] ?? $skuKey);
            $fallbackName = collect($rows)
                ->pluck('name')
                ->map(fn ($name) => trim((string) $name))
                ->first(fn ($name) => $name !== '');

            return [
                'sku' => $sku,
                'name' => $catalogProducts[$skuKey] ?? $fallbackName ?? $sku,
                'quantity' => (int) $rows->sum('quantity'),
                'tickets' => (int) $rows->count(),
                'amountCents' => (int) $rows->sum('amountCents'),
            ];
        })
        ->sortByDesc('amountCents')
        ->values()
        ->take(5)
        ->values();

    $deviceSales = $salesLast7Days
        ->groupBy('device_id')
        ->map(function ($rows, string $deviceId) use ($deviceMetaById) {
            $meta = $deviceMetaById->get($deviceId);
            $tickets = (int) $rows->count();
            $amountCents = (int) $rows->sum('total_cents');

            return [
                'deviceId' => $deviceId,
                'label' => trim((string) ($meta->name ?? '')) !== '' ? (string) $meta->name : $deviceId,
                'tickets' => $tickets,
                'amountCents' => $amountCents,
                'averageTicketCents' => $tickets > 0 ? (int) round($amountCents / $tickets) : 0,
            ];
        })
        ->sortByDesc('amountCents')
        ->take(5)
        ->values();

    $lowStockProducts = DB::table('cloud_catalog_products')
        ->where('store_id', $storeId)
        ->where('is_active', true)
        ->where('track_inventory', true)
        ->whereColumn('stock_on_hand', '<=', 'reorder_point')
        ->orderBy('stock_on_hand')
        ->limit(5)
        ->get(['name', 'sku', 'stock_on_hand', 'reorder_point'])
        ->map(fn ($product) => [
            'name' => (string) $product->name,
            'sku' => (string) $product->sku,
            'stockOnHand' => (int) $product->stock_on_hand,
            'reorderPoint' => (int) $product->reorder_point,
        ])
        ->values();

    return [
        'store' => [
            'id' => (int) $store->id,
            'name' => (string) $store->name,
            'code' => (string) $store->code,
            'catalogVersion' => (int) $store->catalog_version,
        ],
        'stats' => [
            'onlineDevices' => (int) DB::table('devices')
                ->where('tenant_id', $tenantId)
                ->where('store_id', $storeId)
                ->where('last_seen_at', '>=', $now->copy()->subMinutes(10))
                ->count(),
            'catalogItems' => (int) DB::table('cloud_catalog_products')->where('store_id', $storeId)->count(),
            'pendingEvents' => (int) DB::table('sync_events')->where('tenant_id', $tenantId)->where('store_id', $storeId)->count(),
            'conflicts' => (int) DB::table('sync_events')
                ->where('tenant_id', $tenantId)
                ->where('store_id', $storeId)
                ->whereNotNull('apply_error')
                ->count(),
            'lowStock' => (int) DB::table('cloud_catalog_products')
                ->where('store_id', $storeId)
                ->where('is_active', true)
                ->where('track_inventory', true)
                ->whereColumn('stock_on_hand', '<=', 'reorder_point')
                ->count(),
            'salesToday' => $salesTodayCount,
            'salesTodayAmountCents' => $salesTodayAmountCents,
            'averageTicketTodayCents' => $averageTicketTodayCents,
            'salesLast7Days' => $salesLast7DaysCount,
            'salesLast7DaysAmountCents' => $salesLast7DaysAmountCents,
            'salesDeltaPercent' => $salesDeltaPercent,
        ],
        'salesTimeline' => $salesTimeline,
        'paymentMix' => $paymentMix,
        'topProducts' => $topProducts,
        'deviceSales' => $deviceSales,
        'lowStockProducts' => $lowStockProducts,
    ];
};

Route::post('/auth/login', function (Request $request) {
    $payload = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required', 'string', 'min:6'],
    ]);

    /** @var User|null $user */
    $user = User::query()->where('email', $payload['email'])->first();

    if (!$user || !$user->is_active || !Hash::check($payload['password'], $user->password)) {
        return response()->json([
            'message' => 'Credenciales invalidas para Venpi Cloud.',
        ], 422);
    }

    $token = $user->createToken('cloud-admin', ['cloud:read', 'cloud:write'])->plainTextToken;
    $store = $user->store_id ? DB::table('stores')->where('id', $user->store_id)->first() : null;
    $tenant = $user->tenant_id ? DB::table('tenants')->where('id', $user->tenant_id)->first() : null;

    return response()->json([
        'token' => $token,
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ],
        'tenant' => $tenant ? [
            'id' => $tenant->id,
            'name' => $tenant->name,
            'slug' => $tenant->slug,
            'planCode' => $tenant->plan_code,
            'subscriptionStatus' => $tenant->subscription_status,
        ] : null,
        'store' => $store ? [
            'id' => $store->id,
            'name' => $store->name,
            'code' => $store->code,
        ] : null,
    ]);
});

Route::middleware('auth:sanctum')->get('/auth/me', function (Request $request) {
    $user = $request->user();
    $tenant = $user instanceof User && $user->tenant_id ? DB::table('tenants')->where('id', $user->tenant_id)->first() : null;
    $store = $user instanceof User && $user->store_id ? DB::table('stores')->where('id', $user->store_id)->first() : null;

    return response()->json([
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'role' => $user->role,
        'tenantId' => $user->tenant_id,
        'storeId' => $user->store_id,
        'tenant' => $tenant ? [
            'id' => $tenant->id,
            'name' => $tenant->name,
            'slug' => $tenant->slug,
            'planCode' => $tenant->plan_code,
            'subscriptionStatus' => $tenant->subscription_status,
        ] : null,
        'store' => $store ? [
            'id' => $store->id,
            'name' => $store->name,
            'code' => $store->code,
        ] : null,
    ]);
});

Route::middleware('auth:sanctum')->post('/auth/logout', function (Request $request) {
    $request->user()?->currentAccessToken()?->delete();

    return response()->json([
        'ok' => true,
    ]);
});

Route::middleware('auth:sanctum')->get('/cloud/admin/stores', function (Request $request) {
    $user = $request->user();
    abort_unless($user instanceof User && $user->tenant_id, 403, 'Tu cuenta cloud no tiene tenant asignado.');

    $stores = DB::table('stores')
        ->where('tenant_id', $user->tenant_id)
        ->orderBy('name')
        ->get([
            'id',
            'name',
            'code',
            'catalog_version as catalogVersion',
            'is_active as isActive',
        ]);

    return response()->json([
        'items' => $stores,
    ]);
});

Route::middleware('auth:sanctum')->get('/cloud/admin/dashboard-summary', function (Request $request) use ($buildDashboardSummaryForStore) {
    /** @var User $user */
    $user = $request->user();
    abort_unless($user instanceof User && $user->tenant_id, 403, 'No pude autenticar tu cuenta cloud.');

    $storeId = $request->integer('store_id') ?: $user->store_id;
    abort_unless($storeId, 422, 'Debes elegir una sucursal para cargar el resumen.');

    $exists = DB::table('stores')
        ->where('tenant_id', $user->tenant_id)
        ->where('id', $storeId)
        ->exists();

    abort_unless($exists, 404, 'No pude encontrar esa sucursal en tu cuenta cloud.');

    return response()->json($buildDashboardSummaryForStore((int) $user->tenant_id, (int) $storeId));
});

Route::middleware('auth:sanctum')->post('/cloud/admin/stores', function (Request $request) {
    $user = $request->user();
    abort_unless($user instanceof User && $user->tenant_id, 403, 'Tu cuenta cloud no tiene tenant asignado.');

    $payload = $request->validate([
        'name' => ['required', 'string', 'max:120'],
        'timezone' => ['nullable', 'string', 'max:60'],
        'business_name' => ['nullable', 'string', 'max:160'],
        'terminal_name' => ['nullable', 'string', 'max:120'],
        'is_active' => ['nullable', 'boolean'],
    ]);

    $baseCode = 'MATRIZ-001-'.$user->tenant_id;
    $code = $baseCode;
    $counter = 1;

    while (Store::query()->where('code', $code)->exists()) {
        $counter++;
        $code = $baseCode.'-'.$counter;
    }

    $store = Store::query()->create([
        'tenant_id' => $user->tenant_id,
        'name' => trim($payload['name']),
        'code' => $code,
        'timezone' => trim((string) ($payload['timezone'] ?? 'America/Tijuana')) ?: 'America/Tijuana',
        'api_key' => bin2hex(random_bytes(16)),
        'catalog_version' => 1,
        'is_active' => (bool) ($payload['is_active'] ?? true),
        'branding_json' => [
            'business_name' => trim((string) ($payload['business_name'] ?? '')) ?: trim($payload['name']),
            'terminal_name' => trim((string) ($payload['terminal_name'] ?? '')) ?: trim($payload['name']),
        ],
        'role_access_json' => null,
    ]);

    return response()->json([
        'item' => [
            'id' => $store->id,
            'name' => $store->name,
            'code' => $store->code,
            'catalogVersion' => (int) $store->catalog_version,
            'isActive' => (bool) $store->is_active,
        ],
    ], 201);
});

Route::middleware('auth:sanctum')->post('/cloud/admin/device-token', function (Request $request) use ($supportedPlatforms, $issueDeviceTokenForStore) {
    $payload = $request->validate([
        'store_id' => ['required', 'integer', 'min:1'],
        'device_id' => ['required', 'string', 'max:120'],
        'name' => ['nullable', 'string', 'max:120'],
        'platform' => ['required', 'string', 'max:40', Rule::in($supportedPlatforms)],
        'device_type' => ['nullable', 'string', 'max:40'],
        'app_mode' => ['nullable', 'string', 'max:40'],
        'current_version' => ['nullable', 'string', 'max:40'],
        'metadata' => ['nullable', 'array'],
    ]);

    $user = $request->user();
    abort_unless($user instanceof User && $user->tenant_id, 403, 'Tu cuenta cloud no tiene tenant asignado.');

    $store = DB::table('stores')
        ->join('tenants', 'tenants.id', '=', 'stores.tenant_id')
        ->select([
            'stores.id as store_row_id',
            'stores.tenant_id',
            'stores.name as store_name',
            'stores.code as store_code',
            'stores.timezone',
            'stores.api_key',
            'stores.catalog_version',
            'stores.is_active as store_is_active',
            'stores.branding_json',
            'stores.role_access_json',
            'tenants.name as tenant_name',
            'tenants.slug as tenant_slug',
            'tenants.plan_code',
            'tenants.subscription_status',
            'tenants.is_active as tenant_is_active',
        ])
        ->where('stores.id', $payload['store_id'])
        ->where('stores.tenant_id', $user->tenant_id)
        ->first();

    if (!$store) {
        return response()->json([
            'message' => 'No encontre esa store dentro de tu tenant en Venpi Cloud.',
        ], 404);
    }

    if (!(bool) $store->store_is_active || !(bool) $store->tenant_is_active) {
        return response()->json([
            'message' => 'La store o el tenant estan inactivos en Venpi Cloud.',
        ], 403);
    }

    return $issueDeviceTokenForStore($store, $payload, $request);
});

Route::post('/cloud/device-token', function (Request $request) use ($supportedPlatforms, $resolveStoreContext, $issueDeviceTokenForStore) {
    $payload = $request->validate([
        'device_id' => ['required', 'string', 'max:120'],
        'name' => ['nullable', 'string', 'max:120'],
        'platform' => ['required', 'string', 'max:40', Rule::in($supportedPlatforms)],
        'device_type' => ['nullable', 'string', 'max:40'],
        'app_mode' => ['nullable', 'string', 'max:40'],
        'current_version' => ['nullable', 'string', 'max:40'],
        'metadata' => ['nullable', 'array'],
    ]);

    $store = $resolveStoreContext($request);
    return $issueDeviceTokenForStore($store, $payload, $request);
});

Route::get('/cloud/health', function () {
    return response()->json([
        'ok' => true,
        'service' => 'venpi-cloud',
        'capabilities' => [
            'tenants' => true,
            'stores' => true,
            'catalogSync' => true,
            'deviceBootstrap' => true,
            'eventSync' => true,
            'subscriptions' => 'pending',
        ],
        'timestamp' => now()->toIso8601String(),
    ]);
});

Route::post('/cloud/bootstrap', function (Request $request) use ($supportedPlatforms, $resolveStoreContext) {
    $payload = $request->validate([
        'device_id' => ['required', 'string', 'max:120'],
        'name' => ['nullable', 'string', 'max:120'],
        'platform' => ['required', 'string', 'max:40', Rule::in($supportedPlatforms)],
        'device_type' => ['nullable', 'string', 'max:40'],
        'app_mode' => ['nullable', 'string', 'max:40'],
        'channel' => ['nullable', 'string', 'max:40'],
        'current_version' => ['nullable', 'string', 'max:40'],
        'metadata' => ['nullable', 'array'],
    ]);

    $store = $resolveStoreContext($request);
    $now = now();

    DB::table('devices')->updateOrInsert(
        ['device_id' => $payload['device_id']],
        [
            'tenant_id' => $store->tenant_id,
            'store_id' => $store->store_row_id,
            'name' => $payload['name'] ?? null,
            'platform' => $payload['platform'],
            'device_type' => $payload['device_type'] ?? null,
            'app_mode' => $payload['app_mode'] ?? null,
            'channel' => $payload['channel'] ?? 'stable',
            'branch_name' => $store->store_name,
            'current_version' => $payload['current_version'] ?? null,
            'ip_address' => $request->ip(),
            'metadata_json' => array_key_exists('metadata', $payload) ? json_encode($payload['metadata']) : null,
            'last_seen_at' => $now,
            'updated_at' => $now,
            'created_at' => DB::raw('coalesce(created_at, CURRENT_TIMESTAMP)'),
        ]
    );

    return response()->json([
        'ok' => true,
        'tenant' => [
            'id' => $store->tenant_id,
            'name' => $store->tenant_name,
            'slug' => $store->tenant_slug,
            'planCode' => $store->plan_code,
            'subscriptionStatus' => $store->subscription_status,
        ],
        'store' => [
            'id' => $store->store_row_id,
            'name' => $store->store_name,
            'code' => $store->store_code,
            'timezone' => $store->timezone,
            'catalogVersion' => $store->catalog_version,
            'branding' => $store->branding_json ? json_decode($store->branding_json, true) : null,
            'roleAccess' => $store->role_access_json ? json_decode($store->role_access_json, true) : null,
        ],
        'device' => [
            'deviceId' => $payload['device_id'],
            'checkedInAt' => $now->toIso8601String(),
        ],
        'entitlements' => [
            'offlineFirst' => true,
            'catalogSync' => true,
            'salesSync' => true,
            'sharedCatalogAcrossDevices' => true,
        ],
    ]);
})->middleware('auth:sanctum');

Route::get('/cloud/catalog', function (Request $request) use ($resolveStoreContext) {
    $store = $resolveStoreContext($request);

    $products = DB::table('cloud_catalog_products')
        ->where('store_id', $store->store_row_id)
        ->where('is_active', true)
        ->orderBy('name')
        ->get([
            'sku',
            'barcode',
            'name',
            'price_cents',
            'cost_cents',
            'stock_on_hand',
            'reorder_point',
            'track_inventory',
            'catalog_version',
            'updated_at',
        ]);

    return response()->json([
        'store' => [
            'code' => $store->store_code,
            'catalogVersion' => $store->catalog_version,
        ],
        'items' => $products->map(fn (object $product) => [
            'sku' => $product->sku,
            'barcode' => $product->barcode,
            'name' => $product->name,
            'priceCents' => $product->price_cents,
            'costCents' => $product->cost_cents,
            'stockOnHand' => $product->stock_on_hand,
            'reorderPoint' => $product->reorder_point,
            'trackInventory' => (bool) $product->track_inventory,
            'catalogVersion' => $product->catalog_version,
            'updatedAt' => $product->updated_at,
        ])->values(),
    ]);
})->middleware('auth:sanctum');

Route::get('/cloud/catalog/changes', function (Request $request) use ($resolveStoreContext) {
    $payload = $request->validate([
        'since_version' => ['required', 'integer', 'min:0'],
    ]);

    $store = $resolveStoreContext($request);
    $sinceVersion = (int) $payload['since_version'];

    $products = DB::table('cloud_catalog_products')
        ->where('store_id', $store->store_row_id)
        ->where('is_active', true)
        ->where('catalog_version', '>', $sinceVersion)
        ->orderBy('catalog_version')
        ->orderBy('id')
        ->get([
            'sku',
            'barcode',
            'name',
            'price_cents',
            'cost_cents',
            'stock_on_hand',
            'reorder_point',
            'track_inventory',
            'catalog_version',
            'updated_at',
        ]);

    $deletes = DB::table('cloud_catalog_tombstones')
        ->where('store_id', $store->store_row_id)
        ->where('catalog_version', '>', $sinceVersion)
        ->orderBy('catalog_version')
        ->orderBy('id')
        ->get([
            'sku',
            'barcode',
            'catalog_version',
            'deleted_at',
        ]);

    return response()->json([
        'store' => [
            'code' => $store->store_code,
            'catalogVersion' => $store->catalog_version,
        ],
        'upserts' => $products->map(fn (object $product) => [
            'sku' => $product->sku,
            'barcode' => $product->barcode,
            'name' => $product->name,
            'priceCents' => $product->price_cents,
            'costCents' => $product->cost_cents,
            'stockOnHand' => $product->stock_on_hand,
            'reorderPoint' => $product->reorder_point,
            'trackInventory' => (bool) $product->track_inventory,
            'catalogVersion' => $product->catalog_version,
            'updatedAt' => $product->updated_at,
        ])->values(),
        'deletes' => $deletes->map(fn (object $delete) => [
            'sku' => $delete->sku,
            'barcode' => $delete->barcode,
            'catalogVersion' => $delete->catalog_version,
            'deletedAt' => $delete->deleted_at,
        ])->values(),
    ]);
})->middleware('auth:sanctum');

Route::get('/cloud/events/stream', function (Request $request) use ($resolveStoreContext, $streamCatalogVersionEvents) {
    $store = $resolveStoreContext($request);

    return $streamCatalogVersionEvents(
        (int) $store->store_row_id,
        (string) $store->store_code,
        (int) $store->catalog_version,
    );
});

Route::post('/cloud/sync/events', function (Request $request) use ($resolveStoreContext) {
    $payload = $request->validate([
        'device_id' => ['required', 'string', 'max:120'],
        'events' => ['required', 'array', 'min:1'],
        'events.*.event_id' => ['required', 'string', 'max:120'],
        'events.*.aggregate_type' => ['required', 'string', 'max:60'],
        'events.*.event_type' => ['required', 'string', 'max:120'],
        'events.*.occurred_at' => ['required', 'date'],
        'events.*.payload' => ['required', 'array'],
    ]);

    $store = $resolveStoreContext($request);
    $device = DB::table('devices')
        ->where('device_id', $payload['device_id'])
        ->where('store_id', $store->store_row_id)
        ->first();

    $nextCatalogVersion = function () use ($store) {
        DB::table('stores')->where('id', $store->store_row_id)->increment('catalog_version');

        return (int) DB::table('stores')->where('id', $store->store_row_id)->value('catalog_version');
    };

    $findCatalogProduct = function (array $eventPayload) use ($store) {
        $sku = trim((string) ($eventPayload['sku'] ?? ''));
        $barcode = trim((string) ($eventPayload['barcode'] ?? ''));

        $query = DB::table('cloud_catalog_products')->where('store_id', $store->store_row_id);

        if ($sku !== '') {
            $query->where('sku', $sku);
        } elseif ($barcode !== '') {
            $query->where('barcode', $barcode);
        } else {
            return null;
        }

        return $query->first();
    };

    $recordCatalogTombstone = function (string|null $sku, string|null $barcode, int $catalogVersion) use ($store) {
        $normalizedSku = $sku ? trim($sku) : null;
        $normalizedBarcode = $barcode ? trim($barcode) : null;

        if (($normalizedSku ?? '') === '' && ($normalizedBarcode ?? '') === '') {
            return;
        }

        DB::table('cloud_catalog_tombstones')->insert([
            'store_id' => $store->store_row_id,
            'sku' => $normalizedSku ?: null,
            'barcode' => $normalizedBarcode ?: null,
            'catalog_version' => $catalogVersion,
            'deleted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    };

    $accepted = [];
    $conflicts = [];
    $catalogMutationEventTypes = ['product.created', 'product.updated', 'product.deleted'];
    $startingCatalogVersion = (int) DB::table('stores')->where('id', $store->store_row_id)->value('catalog_version');

    foreach ($payload['events'] as $event) {
        $existingSyncEvent = DB::table('sync_events')
            ->where('store_id', $store->store_row_id)
            ->where('event_id', $event['event_id'])
            ->first();

        DB::table('sync_events')->updateOrInsert(
            [
                'store_id' => $store->store_row_id,
                'event_id' => $event['event_id'],
            ],
            [
                'tenant_id' => $store->tenant_id,
                'device_row_id' => $device?->id,
                'device_id' => $payload['device_id'],
                'aggregate_type' => $event['aggregate_type'],
                'event_type' => $event['event_type'],
                'occurred_at' => Carbon::parse($event['occurred_at']),
                'payload_json' => json_encode($event['payload']),
                'received_at' => now(),
                'updated_at' => now(),
                'created_at' => DB::raw('coalesce(created_at, CURRENT_TIMESTAMP)'),
                'apply_error' => null,
            ]
        );

        if ($existingSyncEvent?->applied_at) {
            $accepted[] = $event['event_id'];
            continue;
        }

        try {
            $eventPayload = $event['payload'];
            $eventType = $event['event_type'];
            $baseCatalogVersion = array_key_exists('baseCatalogVersion', $eventPayload) && $eventPayload['baseCatalogVersion'] !== null
                ? (int) $eventPayload['baseCatalogVersion']
                : null;
            $currentCatalogVersion = (int) DB::table('stores')->where('id', $store->store_row_id)->value('catalog_version');

            if (in_array($eventType, $catalogMutationEventTypes, true) && $baseCatalogVersion !== null && $baseCatalogVersion !== $startingCatalogVersion) {
                $message = "La caja intento editar catalogo sobre v{$baseCatalogVersion}, pero Venpi Cloud ya va en v{$currentCatalogVersion}.";

                DB::table('sync_events')
                    ->where('store_id', $store->store_row_id)
                    ->where('event_id', $event['event_id'])
                    ->update([
                        'apply_error' => $message,
                        'updated_at' => now(),
                    ]);

                $conflicts[] = [
                    'eventId' => $event['event_id'],
                    'message' => $message,
                    'currentCatalogVersion' => $currentCatalogVersion,
                ];
                continue;
            }

            if (in_array($eventType, ['product.created', 'product.updated'], true)) {
                $sku = trim((string) ($eventPayload['sku'] ?? ''));

                if ($sku !== '') {
                    $existingProduct = $findCatalogProduct($eventPayload);
                    $catalogVersion = $nextCatalogVersion();
                    $attributes = [
                        'store_id' => $store->store_row_id,
                        'sku' => $sku,
                        'barcode' => ($eventPayload['barcode'] ?? null) ?: null,
                        'name' => trim((string) ($eventPayload['name'] ?? $sku)),
                        'price_cents' => (int) ($eventPayload['priceCents'] ?? 0),
                        'cost_cents' => (int) ($eventPayload['costCents'] ?? 0),
                        'stock_on_hand' => (int) round($eventPayload['stockOnHand'] ?? ($existingProduct->stock_on_hand ?? 0)),
                        'reorder_point' => (int) round($eventPayload['reorderPoint'] ?? 0),
                        'track_inventory' => (bool) ($eventPayload['trackInventory'] ?? true),
                        'is_active' => true,
                        'catalog_version' => $catalogVersion,
                        'metadata_json' => json_encode([
                            'source' => 'sync-event',
                            'last_event_type' => $event['event_type'],
                            'last_device_id' => $payload['device_id'],
                        ]),
                        'updated_at' => now(),
                    ];

                    if ($existingProduct) {
                        if ($existingProduct->sku !== $sku || (($existingProduct->barcode ?? null) !== (($eventPayload['barcode'] ?? null) ?: null))) {
                            $recordCatalogTombstone($existingProduct->sku, $existingProduct->barcode, $catalogVersion);
                        }

                        DB::table('cloud_catalog_products')
                            ->where('id', $existingProduct->id)
                            ->update($attributes);
                    } else {
                        DB::table('cloud_catalog_products')->insert([
                            ...$attributes,
                            'created_at' => now(),
                        ]);
                    }
                }
            }

            if ($eventType === 'product.stock-adjusted') {
                $existingProduct = $findCatalogProduct($eventPayload);

                if ($existingProduct) {
                    $catalogVersion = $nextCatalogVersion();
                    $delta = (int) round($eventPayload['delta'] ?? 0);

                    DB::table('cloud_catalog_products')
                        ->where('id', $existingProduct->id)
                        ->update([
                            'barcode' => ($eventPayload['barcode'] ?? $existingProduct->barcode) ?: null,
                            'stock_on_hand' => max(0, (int) $existingProduct->stock_on_hand + $delta),
                            'catalog_version' => $catalogVersion,
                            'updated_at' => now(),
                        ]);
                }
            }

            if ($eventType === 'product.deleted') {
                $existingProduct = $findCatalogProduct($eventPayload);

                if ($existingProduct) {
                    $catalogVersion = $nextCatalogVersion();
                    $recordCatalogTombstone($existingProduct->sku, $existingProduct->barcode, $catalogVersion);

                    DB::table('cloud_catalog_products')
                        ->where('id', $existingProduct->id)
                        ->delete();
                }
            }

            $saleItems = $eventPayload['items'] ?? $eventPayload['sale']['items'] ?? null;

            if ($eventType === 'sale.created' && is_array($saleItems)) {
                $catalogVersion = null;

                foreach ($saleItems as $item) {
                    $sku = trim((string) ($item['productSku'] ?? ''));
                    $quantity = (int) round($item['quantity'] ?? 0);

                    if ($sku === '' || $quantity <= 0) {
                        continue;
                    }

                    $catalogProduct = DB::table('cloud_catalog_products')
                        ->where('store_id', $store->store_row_id)
                        ->where('sku', $sku)
                        ->first();

                    if (!$catalogProduct || !(bool) $catalogProduct->track_inventory) {
                        continue;
                    }

                    $catalogVersion ??= $nextCatalogVersion();

                    DB::table('cloud_catalog_products')
                        ->where('id', $catalogProduct->id)
                        ->update([
                            'stock_on_hand' => max(0, (int) $catalogProduct->stock_on_hand - $quantity),
                            'catalog_version' => $catalogVersion,
                            'updated_at' => now(),
                        ]);
                }
            }

            DB::table('sync_events')
                ->where('store_id', $store->store_row_id)
                ->where('event_id', $event['event_id'])
                ->update([
                    'applied_at' => now(),
                    'apply_error' => null,
                    'updated_at' => now(),
                ]);

            $accepted[] = $event['event_id'];
        } catch (Throwable $exception) {
            report($exception);

            DB::table('sync_events')
                ->where('store_id', $store->store_row_id)
                ->where('event_id', $event['event_id'])
                ->update([
                    'apply_error' => mb_substr($exception->getMessage(), 0, 1000),
                    'updated_at' => now(),
                ]);
        }
    }

    $finalCatalogVersion = (int) DB::table('stores')->where('id', $store->store_row_id)->value('catalog_version');

    return response()->json([
        'ok' => true,
        'accepted' => $accepted,
        'conflicts' => $conflicts,
        'count' => count($accepted),
        'catalogVersion' => $finalCatalogVersion,
        'storeCode' => $store->store_code,
        'deviceId' => $payload['device_id'],
    ]);
})->middleware('auth:sanctum');
