<?php

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

    Route::get('/stores', function () {
        /** @var User $user */
        $user = Auth::user();

        $stores = Store::query()
            ->where('tenant_id', $user->tenant_id)
            ->orderBy('name')
            ->get();

        return view('stores.index', compact('stores'));
    })->name('stores.index');

    Route::get('/devices', function () {
        /** @var User $user */
        $user = Auth::user();

        $devices = Device::query()
            ->where('tenant_id', $user->tenant_id)
            ->orderByDesc('last_seen_at')
            ->paginate(15);

        return view('devices.index', compact('devices'));
    })->name('devices.index');

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

        return redirect()->route('catalog.index')->with(
            'status',
            ((bool) $product->is_active ? 'Producto desactivado' : 'Producto reactivado').' en el catalogo cloud.'
        );
    })->name('catalog.toggle');
});
