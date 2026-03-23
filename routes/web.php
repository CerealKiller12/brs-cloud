<?php

use App\Models\Device;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth')->group(function () {
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

    Route::get('/catalog', function () {
        /** @var User $user */
        $user = Auth::user();

        $catalog = DB::table('cloud_catalog_products')
            ->where('store_id', $user->store_id)
            ->orderBy('name')
            ->paginate(20);

        return view('catalog.index', compact('catalog'));
    })->name('catalog.index');
});
