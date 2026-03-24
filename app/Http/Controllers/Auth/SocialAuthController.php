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
use Laravel\Socialite\Two\InvalidStateException;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

class SocialAuthController extends Controller
{
    private const SUPPORTED = ['google', 'apple'];
    private const APP_RETURN_COOKIE = 'venpi_social_return_to';
    private const APP_STATE_COOKIE = 'venpi_social_state';

    public function redirect(Request $request, string $provider): SymfonyRedirectResponse
    {
        abort_unless(in_array($provider, self::SUPPORTED, true), 404);

        $returnTo = $this->normalizeAppReturnTo(trim((string) $request->query('return_to', '')));

        if ($this->isAllowedAppReturnTo($returnTo)) {
            cookie()->queue(cookie(
                self::APP_RETURN_COOKIE,
                $returnTo,
                15,
                '/',
                null,
                true,
                false,
                false,
                'lax',
            ));

            $state = Str::random(64);

            cookie()->queue(cookie(
                self::APP_STATE_COOKIE,
                $state,
                15,
                '/',
                null,
                true,
                true,
                false,
                'lax',
            ));

            return $this->socialiteDriver($request, $provider, true)
                ->with(['state' => $state])
                ->redirect();
        } else {
            $request->session()->forget('social_return_to');
            cookie()->queue(cookie()->forget(self::APP_RETURN_COOKIE));
            cookie()->queue(cookie()->forget(self::APP_STATE_COOKIE));
        }

        $request->session()->put('social_return_to', $returnTo);

        return $this->socialiteDriver($request, $provider)->redirect();
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, self::SUPPORTED, true), 404);

        $appReturnTo = $this->normalizeAppReturnTo(trim((string) $request->cookie(self::APP_RETURN_COOKIE, '')));
        $isAppReturn = $this->isAllowedAppReturnTo($appReturnTo);
        $returnTo = $isAppReturn
            ? $appReturnTo
            : $this->normalizeAppReturnTo((string) $request->session()->pull('social_return_to', ''));

        try {
            if ($isAppReturn) {
                $expectedState = trim((string) $request->cookie(self::APP_STATE_COOKIE, ''));
                $providedState = trim((string) $request->query('state', ''));

                abort_if(
                    $expectedState === '' || ! hash_equals($expectedState, $providedState),
                    419,
                    'No pude validar el regreso seguro desde tu cuenta social. Intenta de nuevo.'
                );
            }

            $socialUser = $this->socialiteDriver($request, $provider, $isAppReturn)->user();
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

                cookie()->queue(cookie()->forget(self::APP_RETURN_COOKIE));
                cookie()->queue(cookie()->forget(self::APP_STATE_COOKIE));

                return $this->redirectBackToApp($returnTo, [
                    'cloud_token' => $token,
                    'cloud_provider' => $provider,
                ]);
            }

            $adminHost = trim((string) config('app.admin_host', ''));
            $isAdminHostRequest = $adminHost !== '' && strcasecmp($request->getHost(), $adminHost) === 0;

            return $user->is_platform_admin && $isAdminHostRequest
                ? redirect()->route('admin.dashboard')
                : ($user->tenant?->onboarding_completed_at
                    ? redirect()->route('dashboard')
                    : redirect()->route('onboarding.index'));
        } catch (Throwable $exception) {
            if ($this->isAllowedAppReturnTo($returnTo)) {
                report($exception);

                $message = trim($exception->getMessage());

                if ($message === '' && $exception instanceof InvalidStateException) {
                    $message = 'No pude validar el regreso seguro desde tu cuenta social. Intenta de nuevo.';
                }

                if ($message === '') {
                    $message = 'No pude completar el login social en Venpi Cloud.';
                }

                cookie()->queue(cookie()->forget(self::APP_RETURN_COOKIE));
                cookie()->queue(cookie()->forget(self::APP_STATE_COOKIE));

                return $this->redirectBackToApp($returnTo, [
                    'cloud_error' => $message,
                ]);
            }

            throw $exception;
        }
    }

    private function socialiteDriver(Request $request, string $provider, bool $forceStateless = false)
    {
        $configKey = $this->oauthConfigKey($request, $provider);
        $providerConfig = config("services.{$configKey}");

        if (is_array($providerConfig) && $providerConfig !== []) {
            config(["services.{$provider}" => $providerConfig]);
        }

        $driver = Socialite::driver($provider);

        return $forceStateless ? $driver->stateless() : $driver;
    }

    private function oauthConfigKey(Request $request, string $provider): string
    {
        return $this->usesAdminOauthSurface($request) ? "{$provider}_admin" : $provider;
    }

    private function usesAdminOauthSurface(Request $request): bool
    {
        $adminHost = trim((string) config('app.admin_host', ''));

        return $adminHost !== '' && strcasecmp($request->getHost(), $adminHost) === 0;
    }

    private function normalizeAppReturnTo(?string $returnTo): string
    {
        if (! is_string($returnTo) || $returnTo === '') {
            return '';
        }

        $parts = parse_url($returnTo);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = '/'.ltrim((string) ($parts['path'] ?? ''), '/');

        if ($scheme === 'capacitor' && $host === 'localhost' && $path === '/cloud-tenant') {
            return 'venpi://cloud-tenant';
        }

        return trim($returnTo);
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

        if ($scheme === 'venpi') {
            return $host === 'cloud-tenant';
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
        $tenantSlugBase = Str::slug($businessName) ?: 'venpi-cloud';
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
