<?php

use App\Events\CatalogVersionChanged;
use App\Models\Device;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;

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

$broadcastCatalogVersionChanged = function (int $storeId): void {
    $store = DB::table('stores')
        ->where('id', $storeId)
        ->first(['id', 'code', 'catalog_version']);

    if (!$store) {
        return;
    }

    event(new CatalogVersionChanged(
        (int) $store->id,
        (string) $store->code,
        (int) $store->catalog_version,
    ));
};

Route::get('/health', function () {
    return response()->json([
        'ok' => true,
        'service' => 'brs-cloud',
        'timestamp' => now()->toIso8601String(),
    ]);
});

$resolveStoreContext = function (Request $request) {
    $actor = $request->user();

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

    $storeCode = $request->header('X-BRS-Store-Code', $request->input('store_code'));
    $storeKey = $request->header('X-BRS-Store-Key', $request->input('store_key'));

    if (!$storeCode || !$storeKey) {
        abort(response()->json([
            'message' => 'Debes enviar X-BRS-Store-Code y X-BRS-Store-Key.',
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
            'message' => 'No pude autenticar la tienda en BRS Cloud.',
        ], 403));
    }

    if (!(bool) $store->store_is_active || !(bool) $store->tenant_is_active) {
        abort(response()->json([
            'message' => 'La tienda o el tenant estan inactivos en BRS Cloud.',
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

Route::post('/auth/login', function (Request $request) {
    $payload = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required', 'string', 'min:6'],
    ]);

    /** @var User|null $user */
    $user = User::query()->where('email', $payload['email'])->first();

    if (!$user || !$user->is_active || !Hash::check($payload['password'], $user->password)) {
        return response()->json([
            'message' => 'Credenciales invalidas para BRS Cloud.',
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
            'message' => 'No encontre esa store dentro de tu tenant en BRS Cloud.',
        ], 404);
    }

    if (!(bool) $store->store_is_active || !(bool) $store->tenant_is_active) {
        return response()->json([
            'message' => 'La store o el tenant estan inactivos en BRS Cloud.',
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
        'service' => 'brs-cloud',
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

Route::get('/cloud/realtime-config', function (Request $request) use ($resolveStoreContext) {
    $store = $resolveStoreContext($request);
    $defaultConnection = (string) config('broadcasting.default', 'null');
    $connection = (array) config("broadcasting.connections.{$defaultConnection}", []);
    $options = (array) ($connection['options'] ?? []);
    $configuredHost = env($defaultConnection === 'pusher' ? 'PUSHER_HOST' : 'REVERB_HOST');
    $scheme = (string) ($options['scheme'] ?? ($request->isSecure() ? 'https' : 'http'));
    $defaultPort = $scheme === 'https' ? 443 : 80;

    return response()->json([
        'broadcast' => [
            'driver' => $defaultConnection,
            'key' => (string) ($connection['key'] ?? ''),
            'cluster' => (string) ($options['cluster'] ?? 'mt1'),
            'host' => $configuredHost ? (string) ($options['host'] ?? $request->getHost()) : '',
            'port' => (int) ($options['port'] ?? $defaultPort),
            'scheme' => $scheme,
            'path' => (string) config('reverb.servers.reverb.path', ''),
        ],
        'channel' => [
            'name' => "catalog.store.{$store->store_row_id}",
            'event' => 'catalog.version.changed',
        ],
        'store' => [
            'id' => (int) $store->store_row_id,
            'code' => (string) $store->store_code,
            'catalogVersion' => (int) $store->catalog_version,
        ],
    ]);
})->middleware('auth:sanctum');

Route::post('/cloud/sync/events', function (Request $request) use ($resolveStoreContext, $broadcastCatalogVersionChanged) {
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
                $message = "La caja intento editar catalogo sobre v{$baseCatalogVersion}, pero BRS Cloud ya va en v{$currentCatalogVersion}.";

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

            if ($eventType === 'sale.created' && is_array($eventPayload['items'] ?? null)) {
                $catalogVersion = null;

                foreach ($eventPayload['items'] as $item) {
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

    if ($finalCatalogVersion > $startingCatalogVersion) {
        $broadcastCatalogVersionChanged((int) $store->store_row_id);
    }

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
