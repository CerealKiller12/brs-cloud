<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class SocialAuthController extends Controller
{
    private const SUPPORTED = ['google', 'apple'];

    public function redirect(string $provider): SymfonyRedirectResponse
    {
        abort_unless(in_array($provider, self::SUPPORTED, true), 404);

        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, self::SUPPORTED, true), 404);

        $socialUser = Socialite::driver($provider)->user();
        $email = $socialUser->getEmail();
        $providerIdField = $provider === 'google' ? 'google_id' : 'apple_id';

        $user = User::query()
            ->when($socialUser->getId(), fn ($query) => $query->orWhere($providerIdField, $socialUser->getId()))
            ->when($email, fn ($query) => $query->orWhere('email', $email))
            ->first();

        if (! $user) {
            abort_if(! $email, 422, 'Apple no devolvio correo. Vuelve a intentar y comparte tu email con la app la primera vez.');
            $user = $this->provisionOwnerFromSocialUser(
                $provider,
                $socialUser->getId(),
                $email,
                $socialUser->getName(),
                $socialUser->getAvatar(),
            );
        } else {
            abort_unless($user->is_active, 403, 'Tu acceso cloud esta inactivo.');

            $updates = [
                'email_verified_at' => $user->email_verified_at ?: now(),
                'avatar_url' => $socialUser->getAvatar() ?: $user->avatar_url,
            ];

            if (! $user->{$providerIdField}) {
                $updates[$providerIdField] = $socialUser->getId();
            }

            if (! $user->name && $socialUser->getName()) {
                $updates['name'] = $socialUser->getName();
            }

            $user->fill($updates)->save();
        }

        Auth::login($user, true);
        request()->session()->regenerate();

        return redirect()->route('dashboard');
    }

    private function provisionOwnerFromSocialUser(
        string $provider,
        string $providerId,
        string $email,
        ?string $name,
        ?string $avatarUrl,
    ): User {
        $displayName = trim($name ?: Str::before($email, '@')) ?: 'Owner';
        $businessName = trim($displayName).' Retail';
        $tenantSlugBase = Str::slug($businessName) ?: 'brs-cloud';
        $tenantSlug = $tenantSlugBase;
        $counter = 1;

        while (Tenant::query()->where('slug', $tenantSlug)->exists()) {
            $counter++;
            $tenantSlug = $tenantSlugBase.'-'.$counter;
        }

        $defaultRoleAccess = [
            'admin' => [
                'checkout' => true,
                'sales' => true,
                'cash' => true,
                'products' => true,
                'users' => true,
                'settings' => true,
                'updates' => true,
            ],
            'supervisor' => [
                'checkout' => true,
                'sales' => true,
                'cash' => true,
                'products' => true,
                'users' => false,
                'settings' => false,
                'updates' => false,
            ],
            'cashier' => [
                'checkout' => true,
                'sales' => true,
                'cash' => true,
                'products' => false,
                'users' => false,
                'settings' => false,
                'updates' => false,
            ],
        ];

        return DB::transaction(function () use ($provider, $providerId, $email, $displayName, $businessName, $avatarUrl, $tenantSlug, $defaultRoleAccess) {
            $tenant = Tenant::query()->create([
                'name' => $businessName,
                'slug' => $tenantSlug,
                'plan_code' => 'starter',
                'subscription_status' => 'trialing',
                'is_active' => true,
                'trial_ends_at' => now()->addDays(14),
            ]);

            $store = Store::query()->create([
                'tenant_id' => $tenant->id,
                'name' => 'Caja principal',
                'code' => 'MATRIZ-001-'.$tenant->id,
                'timezone' => 'America/Tijuana',
                'api_key' => bin2hex(random_bytes(16)),
                'catalog_version' => 1,
                'is_active' => true,
                'branding_json' => [
                    'business_name' => $businessName,
                    'terminal_name' => 'Caja principal',
                ],
                'role_access_json' => $defaultRoleAccess,
            ]);

            return User::query()->create([
                'tenant_id' => $tenant->id,
                'store_id' => $store->id,
                'name' => $displayName,
                'email' => $email,
                'password' => Str::password(32),
                'role' => 'owner',
                'is_active' => true,
                'email_verified_at' => now(),
                'google_id' => $provider === 'google' ? $providerId : null,
                'apple_id' => $provider === 'apple' ? $providerId : null,
                'avatar_url' => $avatarUrl,
            ]);
        });
    }
}
