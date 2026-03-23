<?php

use App\Http\Controllers\Auth\SocialAuthController;
use App\Models\Device;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;

$resolveStoreForUser = function (User $user) {
    abort_unless($user->store_id, 403, 'El usuario cloud no tiene store asignada.');

    return DB::table('stores')->where('id', $user->store_id)->firstOrFail();
};

$bumpCatalogVersion = function (int $storeId): int {
    DB::table('stores')->where('id', $storeId)->increment('catalog_version');

    return (int) DB::table('stores')->where('id', $storeId)->value('catalog_version');
};

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/register', function () {
        return view('auth.register');
    })->name('register');

    Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])->name('social.redirect');
    Route::match(['GET', 'POST'], '/auth/{provider}/callback', [SocialAuthController::class, 'callback'])->name('social.callback');

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

        return redirect()->intended(route('dashboard'));
    })->name('login.submit');
});

Route::middleware('auth')->group(function () use ($resolveStoreForUser, $bumpCatalogVersion) {
    Route::post('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');

    Route::get('/dashboard', function () {
        /** @var User $user */
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        $storeId = $user->store_id;

        $tenant = $tenantId ? DB::table('tenants')->where('id', $tenantId)->first() : null;
        $store = $storeId ? DB::table('stores')->where('id', $storeId)->first() : null;

        $stats = [
            'stores' => DB::table('stores')->where('tenant_id', $tenantId)->count(),
            'devices' => DB::table('devices')->where('tenant_id', $tenantId)->count(),
            'catalogItems' => DB::table('cloud_catalog_products')->where('store_id', $storeId)->count(),
            'pendingEvents' => DB::table('sync_events')->where('tenant_id', $tenantId)->count(),
        ];

        $recentDevices = Device::query()
            ->where('tenant_id', $tenantId)
            ->latest('last_seen_at')
            ->limit(6)
            ->get();

        $recentEvents = DB::table('sync_events')
            ->where('tenant_id', $tenantId)
            ->latest('received_at')
            ->limit(8)
            ->get();

        return view('dashboard', compact('user', 'tenant', 'store', 'stats', 'recentDevices', 'recentEvents'));
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

        return redirect()->route('stores.index')->with('status', 'Store creada en BRS Cloud.');
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

        return redirect()->route('stores.index')->with('status', 'Store actualizada.');
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

        return redirect()->route('stores.index', ['edit' => $storeId])->with('status', 'Store key regenerada.');
    })->name('stores.rotate-key');

    Route::get('/devices', function (Request $request) {
        /** @var User $user */
        $user = Auth::user();
        $search = trim((string) $request->query('q', ''));
        $storeFilter = $request->integer('store_id');

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

        return view('devices.index', compact('devices', 'tokenCounts', 'deviceStats', 'storeOptions', 'storeNames', 'search', 'storeFilter'));
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

        return redirect()->route('devices.index')->with('status', 'Tokens del device revocados.');
    })->name('devices.revoke-token');

    Route::get('/sync', function (Request $request) {
        /** @var User $user */
        $user = Auth::user();
        $deviceFilter = trim((string) $request->query('device_id', ''));
        $eventFilter = trim((string) $request->query('event_type', ''));

        $baseQuery = DB::table('sync_events')
            ->where('tenant_id', $user->tenant_id)
            ->when($deviceFilter !== '', fn ($query) => $query->where('device_id', $deviceFilter))
            ->when($eventFilter !== '', fn ($query) => $query->where('event_type', 'like', "%{$eventFilter}%"));

        $syncStats = [
            'total' => (clone $baseQuery)->count(),
            'devices' => (clone $baseQuery)->distinct('device_id')->count('device_id'),
            'last24h' => (clone $baseQuery)->where('received_at', '>=', now()->subDay())->count(),
            'lastEventAt' => (clone $baseQuery)->max('received_at'),
        ];

        $topEventTypes = (clone $baseQuery)
            ->select('event_type', DB::raw('count(*) as aggregate'))
            ->groupBy('event_type')
            ->orderByDesc('aggregate')
            ->limit(4)
            ->get();

        $events = (clone $baseQuery)
            ->latest('received_at')
            ->paginate(20)
            ->withQueryString();

        $deviceOptions = DB::table('devices')
            ->where('tenant_id', $user->tenant_id)
            ->orderBy('name')
            ->get(['device_id', 'name']);

        return view('sync.index', compact('events', 'deviceOptions', 'deviceFilter', 'eventFilter', 'syncStats', 'topEventTypes'));
    })->name('sync.index');

    Route::get('/catalog', function (Request $request) use ($resolveStoreForUser) {
        /** @var User $user */
        $user = Auth::user();
        $store = $resolveStoreForUser($user);
        $search = trim((string) $request->query('q', ''));
        $editId = $request->integer('edit');

        $catalogQuery = DB::table('cloud_catalog_products')
            ->where('store_id', $user->store_id)
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

        $editProduct = null;
        if ($editId) {
            $editProduct = DB::table('cloud_catalog_products')
                ->where('store_id', $user->store_id)
                ->where('id', $editId)
                ->first();
        }

        return view('catalog.index', [
            'catalog' => $catalog,
            'editProduct' => $editProduct,
            'store' => $store,
            'search' => $search,
        ]);
    })->name('catalog.index');

    Route::post('/catalog', function (Request $request) use ($bumpCatalogVersion, $resolveStoreForUser) {
        /** @var User $user */
        $user = Auth::user();
        $store = $resolveStoreForUser($user);

        $payload = $request->validate([
            'sku' => ['required', 'string', 'max:80', Rule::unique('cloud_catalog_products', 'sku')->where(fn ($q) => $q->where('store_id', $user->store_id))],
            'barcode' => ['nullable', 'string', 'max:120', Rule::unique('cloud_catalog_products', 'barcode')->where(fn ($q) => $q->where('store_id', $user->store_id))],
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

        return redirect()->route('catalog.index')->with('status', 'Producto creado en el catalogo cloud.');
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
            'sku' => ['required', 'string', 'max:80', Rule::unique('cloud_catalog_products', 'sku')->where(fn ($q) => $q->where('store_id', $user->store_id))->ignore($productId)],
            'barcode' => ['nullable', 'string', 'max:120', Rule::unique('cloud_catalog_products', 'barcode')->where(fn ($q) => $q->where('store_id', $user->store_id))->ignore($productId)],
            'name' => ['required', 'string', 'max:160'],
            'price' => ['required', 'numeric', 'min:0'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'stock_on_hand' => ['required', 'integer', 'min:0'],
            'reorder_point' => ['required', 'integer', 'min:0'],
            'track_inventory' => ['nullable', 'boolean'],
        ]);

        $nextVersion = $bumpCatalogVersion($store->id);

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

        return redirect()->route('catalog.index')->with('status', 'Producto actualizado en el catalogo cloud.');
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
            ((bool) $product->is_active ? 'Producto desactivado' : 'Producto reactivado').' en el catalogo cloud.'
        );
    })->name('catalog.toggle');
});
