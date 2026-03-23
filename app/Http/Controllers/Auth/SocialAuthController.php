<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

class SocialAuthController extends Controller
{
    private const SUPPORTED = ['google', 'apple'];

    public function redirect(Request $request, string $provider): SymfonyRedirectResponse
    {
        abort_unless(in_array($provider, self::SUPPORTED, true), 404);

        $returnTo = trim((string) $request->query('return_to', ''));

        if ($this->isAllowedAppReturnTo($returnTo)) {
            $request->session()->put('social_return_to', $returnTo);

            cookie()->queue(cookie(
                'brs_social_return_to',
                $returnTo,
                15,
                '/',
                null,
                true,
                false,
                false,
                'lax',
            ));
        } else {
            $request->session()->forget('social_return_to');
            cookie()->queue(cookie()->forget('brs_social_return_to'));
        }

        return Socialite::driver($provider)->redirect();
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, self::SUPPORTED, true), 404);

        $returnTo = $request->session()->pull('social_return_to') ?: trim((string) $request->cookie('brs_social_return_to', ''));

        try {
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
            $request->session()->regenerate();

            if ($this->isAllowedAppReturnTo($returnTo)) {
                $token = $user->createToken('cloud-admin', ['cloud:read', 'cloud:write'])->plainTextToken;

                cookie()->queue(cookie()->forget('brs_social_return_to'));

                return $this->redirectBackToApp($returnTo, [
                    'cloud_token' => $token,
                    'cloud_provider' => $provider,
                ]);
            }

            return $user->tenant?->onboarding_completed_at
                ? redirect()->route('dashboard')
                : redirect()->route('onboarding.index');
        } catch (Throwable $exception) {
            if ($this->isAllowedAppReturnTo($returnTo)) {
                cookie()->queue(cookie()->forget('brs_social_return_to'));

                return $this->redirectBackToApp($returnTo, [
                    'cloud_error' => $exception->getMessage() ?: 'No pude vincular la cuenta social de BRS Cloud.',
                ]);
            }

            throw $exception;
        }
    }

    private function isAllowedAppReturnTo(?string $returnTo): bool
    {
        if (! is_string($returnTo) || $returnTo === '') {
            return false;
        }

        $parts = parse_url($returnTo);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));

        if ($scheme === 'capacitor') {
            return $host === 'localhost';
        }

        if (in_array($scheme, ['http', 'https'], true)) {
            return in_array($host, ['localhost', '127.0.0.1'], true);
        }

        return false;
    }

    private function redirectBackToApp(string $returnTo, array $params): RedirectResponse
    {
        $filtered = array_filter($params, fn ($value) => $value !== null && $value !== '');
        $separator = str_contains($returnTo, '?') ? '&' : '?';

        return redirect()->away($returnTo.$separator.http_build_query($filtered));
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
