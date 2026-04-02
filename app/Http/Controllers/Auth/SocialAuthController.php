<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

class SocialAuthController extends Controller
{
    private const SUPPORTED = ['google', 'apple'];
    private const LINK_CONTEXT_SESSION_KEY = 'social_link_context';

    public function redirect(Request $request, string $provider): SymfonyRedirectResponse
    {
        abort_unless(in_array($provider, self::SUPPORTED, true), 404);

        if ($request->boolean('link_account')) {
            $user = $request->user();
            abort_unless($user instanceof User && $user->is_active, 403, 'Necesitas una sesion activa para vincular accesos.');

            $request->session()->put(self::LINK_CONTEXT_SESSION_KEY, [
                'user_id' => $user->id,
                'provider' => $provider,
                'redirect_to' => route('settings.index'),
            ]);
            $request->session()->forget('social_return_to');

            return $this->socialiteDriver($request, $provider)->redirect();
        }

        $returnTo = $this->normalizeAppReturnTo(trim((string) $request->query('return_to', '')));

        if ($this->isAllowedAppReturnTo($returnTo)) {
            $request->session()->put('social_return_to', $returnTo);
        } else {
            $request->session()->forget('social_return_to');
        }

        return $this->socialiteDriver($request, $provider)->redirect();
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, self::SUPPORTED, true), 404);

        $returnTo = $this->normalizeAppReturnTo((string) $request->session()->pull('social_return_to', ''));
        $linkContext = $request->session()->pull(self::LINK_CONTEXT_SESSION_KEY);

        try {
            $socialUser = $this->socialiteDriver($request, $provider)->user();

            if (is_array($linkContext) && ($linkContext['provider'] ?? null) === $provider) {
                return $this->linkProviderToCurrentUser($request, $provider, $socialUser, $linkContext);
            }

            $email = $socialUser->getEmail();
            $providerIdField = $provider === 'google' ? 'google_id' : 'apple_id';

            $user = User::query()
                ->when($socialUser->getId(), fn ($query) => $query->orWhere($providerIdField, $socialUser->getId()))
                ->when($email, fn ($query) => $query->orWhere('email', $email))
                ->first();

            if (! $user) {
                abort_if(! $email, 422, 'La cuenta social no devolvio correo. Vuelve a intentar con una cuenta que comparta email.');
                $user = $this->provisionAccountFromSocialUser(
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
            if (is_array($linkContext) && ($linkContext['provider'] ?? null) === $provider) {
                $redirectTo = trim((string) ($linkContext['redirect_to'] ?? '')) ?: route('settings.index');

                return redirect()
                    ->to($redirectTo)
                    ->withErrors([
                        'account_access' => trim($exception->getMessage()) ?: 'No pude vincular ese acceso social a tu cuenta.',
                    ]);
            }

            if ($this->isAllowedAppReturnTo($returnTo)) {
                return $this->redirectBackToApp($returnTo, [
                    'cloud_error' => $exception->getMessage() ?: 'No pude vincular la cuenta social de Venpi Cloud.',
                ]);
            }

            throw $exception;
        }
    }

    private function socialiteDriver(Request $request, string $provider)
    {
        $configKey = $this->oauthConfigKey($request, $provider);
        $providerConfig = config("services.{$configKey}");

        if (is_array($providerConfig) && $providerConfig !== []) {
            config(["services.{$provider}" => $providerConfig]);
        }

        return Socialite::driver($provider);
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

    private function linkProviderToCurrentUser(Request $request, string $provider, object $socialUser, array $linkContext): RedirectResponse
    {
        $currentUser = User::query()->find((int) ($linkContext['user_id'] ?? 0));
        abort_unless($currentUser instanceof User && $currentUser->is_active, 403, 'Tu sesion ya no esta disponible para vincular accesos.');

        $providerIdField = $provider === 'google' ? 'google_id' : 'apple_id';
        $providerId = trim((string) $socialUser->getId());
        abort_if($providerId === '', 422, 'La cuenta social no devolvio un identificador valido.');

        $linkedUser = User::query()
            ->where($providerIdField, $providerId)
            ->first();

        abort_if(
            $linkedUser instanceof User && $linkedUser->id !== $currentUser->id,
            409,
            'Ese acceso social ya esta vinculado a otra cuenta de Venpi Cloud.'
        );

        $socialEmail = trim((string) ($socialUser->getEmail() ?? ''));

        if ($socialEmail !== '') {
            $emailOwner = User::query()
                ->where('email', $socialEmail)
                ->first();

            abort_if(
                $emailOwner instanceof User && $emailOwner->id !== $currentUser->id,
                409,
                'Ese correo social ya pertenece a otra cuenta cloud. Por ahora no puedo fusionar dos usuarios distintos automaticamente.'
            );
        }

        $currentUser->fill([
            $providerIdField => $providerId,
            'avatar_url' => $socialUser->getAvatar() ?: $currentUser->avatar_url,
            'email_verified_at' => $currentUser->email_verified_at ?: now(),
            'name' => $currentUser->name ?: ($socialUser->getName() ?: $currentUser->name),
        ])->save();

        if ((int) Auth::id() !== (int) $currentUser->id) {
            Auth::login($currentUser, true);
            $request->session()->regenerate();
        }

        $redirectTo = trim((string) ($linkContext['redirect_to'] ?? '')) ?: route('settings.index');

        return redirect()->to($redirectTo)->with('status', ucfirst($provider).' ya quedo vinculado a tu cuenta.');
    }

    private function provisionAccountFromSocialUser(
        string $provider,
        string $providerId,
        string $email,
        ?string $name,
        ?string $avatarUrl,
    ): User {
        $displayName = trim($name ?: Str::before($email, '@')) ?: 'Owner';

        return User::query()->create([
            'name' => $displayName,
            'email' => $email,
            'password' => Str::password(32),
            'password_login_enabled_at' => null,
            'role' => 'owner',
            'is_active' => true,
            'email_verified_at' => now(),
            'google_id' => $provider === 'google' ? $providerId : null,
            'apple_id' => $provider === 'apple' ? $providerId : null,
            'avatar_url' => $avatarUrl,
        ]);
    }
}
