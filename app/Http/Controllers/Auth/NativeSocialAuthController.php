<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

class NativeSocialAuthController extends Controller
{
    private const SUPPORTED = ['google', 'apple'];
    private const LOGIN_CODE_CACHE_PREFIX = 'native_social_login:';

    public function redirect(Request $request, string $provider): SymfonyRedirectResponse
    {
        abort_unless(in_array($provider, self::SUPPORTED, true), 404);

        $returnTo = $this->normalizeAppReturnTo(trim((string) $request->query('return_to', '')));
        abort_unless($this->isAllowedAppReturnTo($returnTo, $provider), 422, 'El retorno nativo solicitado no es valido.');

        $state = $this->encodeState($provider, $returnTo);

        return $this->socialiteDriver($provider)
            ->with(['state' => $state])
            ->redirect();
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, self::SUPPORTED, true), 404);

        $statePayload = $this->decodeState(trim((string) $request->input('state', '')), $provider);
        abort_unless(is_array($statePayload), 419, 'No pude validar el regreso seguro desde tu cuenta social. Intenta de nuevo.');

        $returnTo = (string) ($statePayload['return_to'] ?? '');
        abort_unless($this->isAllowedAppReturnTo($returnTo, $provider), 422, 'El retorno nativo solicitado no es valido.');

        try {
            $socialUser = $this->socialiteDriver($provider)->user();
            $email = $socialUser->getEmail();
            $providerIdField = $provider === 'google' ? 'google_id' : 'apple_id';

            $user = User::query()
                ->when($socialUser->getId(), fn ($query) => $query->orWhere($providerIdField, $socialUser->getId()))
                ->when($email, fn ($query) => $query->orWhere('email', $email))
                ->first();

            if (! $user) {
                abort_if(! $email, 422, 'Google no devolvio correo. Vuelve a intentar con una cuenta que comparta email.');
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

            $loginCode = Str::random(72);
            Cache::put(
                self::LOGIN_CODE_CACHE_PREFIX.$loginCode,
                ['user_id' => $user->id],
                now()->addMinutes(5)
            );

            return $this->redirectBackToApp($returnTo, [
                'native_login_code' => $loginCode,
                'cloud_provider' => $provider,
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return $this->redirectBackToApp($returnTo, [
                'cloud_error' => trim($exception->getMessage()) ?: 'No pude completar el login social en Venpi Cloud.',
            ]);
        }
    }

    public function consume(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'code' => ['required', 'string', 'min:32', 'max:200'],
        ]);

        $login = Cache::pull(self::LOGIN_CODE_CACHE_PREFIX.trim($payload['code']));
        abort_unless(is_array($login), 422, 'Ese codigo de acceso ya expiro o no es valido.');

        $user = User::query()->find((int) ($login['user_id'] ?? 0));
        abort_unless($user instanceof User && $user->is_active, 403, 'Tu acceso cloud esta inactivo.');

        $token = $user->createToken('cloud-admin', ['cloud:read', 'cloud:write'])->plainTextToken;

        return response()->json([
            'token' => $token,
        ]);
    }

    private function socialiteDriver(string $provider)
    {
        $configKey = $provider;
        $providerConfig = config("services.{$configKey}");

        if (is_array($providerConfig) && $providerConfig !== []) {
            $nativeRedirect = route('social.native.callback', ['provider' => $provider], true);
            $providerConfig['redirect'] = $nativeRedirect;
            config(["services.{$provider}" => $providerConfig]);
        }

        return Socialite::driver($provider)->stateless();
    }

    private function normalizeAppReturnTo(?string $returnTo): string
    {
        if (! is_string($returnTo) || $returnTo === '') {
            return '';
        }

        return trim($returnTo);
    }

    private function isAllowedAppReturnTo(?string $returnTo, string $provider): bool
    {
        if (! is_string($returnTo) || $returnTo === '') {
            return false;
        }

        $parts = parse_url($returnTo);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = '/'.ltrim((string) ($parts['path'] ?? ''), '/');

        return $scheme === 'venpi'
            && $host === 'oauth'
            && $path === "/{$provider}/callback";
    }

    private function redirectBackToApp(string $returnTo, array $params): RedirectResponse
    {
        $filtered = array_filter($params, fn ($value) => $value !== null && $value !== '');
        $separator = str_contains($returnTo, '?') ? '&' : '?';

        return redirect()->away($returnTo.$separator.http_build_query($filtered));
    }

    private function encodeState(string $provider, string $returnTo): string
    {
        $payload = [
            'provider' => $provider,
            'return_to' => $returnTo,
            'issued_at' => now()->getTimestamp(),
            'nonce' => Str::random(24),
        ];

        $encodedPayload = $this->base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES));
        $signature = hash_hmac('sha256', $encodedPayload, $this->stateSigningKey());

        return $encodedPayload.'.'.$signature;
    }

    private function decodeState(string $state, string $provider): ?array
    {
        if (! str_contains($state, '.')) {
            return null;
        }

        [$encodedPayload, $providedSignature] = explode('.', $state, 2);
        $expectedSignature = hash_hmac('sha256', $encodedPayload, $this->stateSigningKey());

        if (! hash_equals($expectedSignature, $providedSignature)) {
            return null;
        }

        $decodedPayload = $this->base64UrlDecode($encodedPayload);
        $payload = json_decode($decodedPayload, true);

        if (! is_array($payload) || ($payload['provider'] ?? null) !== $provider) {
            return null;
        }

        $issuedAt = (int) ($payload['issued_at'] ?? 0);

        if ($issuedAt <= 0 || abs(now()->getTimestamp() - $issuedAt) > 900) {
            return null;
        }

        return $payload;
    }

    private function stateSigningKey(): string
    {
        $appKey = (string) config('app.key');

        if (str_starts_with($appKey, 'base64:')) {
            return base64_decode(substr($appKey, 7), true) ?: $appKey;
        }

        return $appKey;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        $padding = strlen($value) % 4;

        if ($padding > 0) {
            $value .= str_repeat('=', 4 - $padding);
        }

        return base64_decode(strtr($value, '-_', '+/'), true) ?: '';
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
