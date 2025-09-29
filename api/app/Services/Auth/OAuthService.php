<?php

namespace App\Services\Auth;

use App\Models\OAuthState;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class OAuthService
{
    public function getSupportedProviders(): array
    {
        return Config::get('oauth.providers', []);
    }

    public function validateProvider(string $provider): string
    {
        $provider = SocialAccount::normalizeProvider($provider);

        if (!in_array($provider, $this->getSupportedProviders(), true)) {
            throw new BadRequestHttpException('Proveedor OAuth no soportado.');
        }

        return $provider;
    }

    public function createState(string $provider, string $redirectUri, array $payload = []): OAuthState
    {
        $provider = $this->validateProvider($provider);
        $this->ensureRedirectAllowed($redirectUri);

        $state = OAuthState::create([
            'state' => OAuthState::generateToken(),
            'provider' => $provider,
            'redirect_uri' => $redirectUri,
            'payload' => $payload,
            'expires_at' => now()->addSeconds(Config::get('oauth.state_ttl_seconds', 300)),
        ]);

        // Limpieza de estados expirados en background
        OAuthState::where('expires_at', '<', now()->subMinutes(5))->delete();

        return $state;
    }

    public function consumeState(string $stateToken, string $provider): OAuthState
    {
        $provider = $this->validateProvider($provider);

        $state = OAuthState::where('state', $stateToken)
            ->where('provider', $provider)
            ->first();

        if (!$state) {
            throw new BadRequestHttpException('Token de estado inválido o expirado.');
        }

        if ($state->isExpired()) {
            $state->delete();
            throw new BadRequestHttpException('El flujo OAuth expiró, inténtalo nuevamente.');
        }

        if ($state->consumed_at) {
            throw new BadRequestHttpException('El flujo OAuth ya fue utilizado.');
        }

        $state->markConsumed();

        return $state;
    }

    public function buildAuthorizationUrl(string $provider, OAuthState $state): string
    {
        $driver = Socialite::driver($provider);

        /** @var object $driver */
        if (method_exists($driver, 'stateless')) {
            $driver = $driver->stateless();
        }

        if ($redirect = Config::get("services.$provider.redirect")) {
            $driver = $driver->redirectUrl($redirect);
        }

        $scopes = $this->providerScopes($provider);
        if (!empty($scopes)) {
            $driver = $driver->scopes($scopes);
        }

        $with = ['state' => $state->state];
        $extra = $this->providerExtraParameters($provider, $state);
        if (!empty($extra)) {
            $with = array_merge($with, $extra);
        }

        $response = $driver->with($with)->redirect();

        return $response->getTargetUrl();
    }

    public function handleCallback(string $provider): array
    {
        $provider = $this->validateProvider($provider);

        $driver = Socialite::driver($provider);
        /** @var object $driver */
        if (method_exists($driver, 'stateless')) {
            $driver = $driver->stateless();
        }

        $socialiteUser = $driver->user();

        $stateToken = request()->query('state');
        if (!$stateToken) {
            throw new BadRequestHttpException('Falta parámetro state.');
        }

        $state = $this->consumeState($stateToken, $provider);

        $user = $this->resolveUser($provider, $socialiteUser);
        $socialAccount = $this->syncSocialAccount($user, $provider, $socialiteUser);

        $accessToken = $this->issueToken($user, $provider);

        return [
            'state' => $state,
            'user' => $user->fresh('company'),
            'social_account' => $socialAccount,
            'token' => $accessToken->plainTextToken,
            'access_token' => $accessToken,
        ];
    }

    public function issueToken(User $user, string $provider): NewAccessToken
    {
        $provider = SocialAccount::normalizeProvider($provider);

        return $user->createToken('oauth:'.$provider, ['role:'.$user->role]);
    }

    protected function resolveUser(string $provider, SocialiteUser $socialiteUser): User
    {
        $provider = SocialAccount::normalizeProvider($provider);
        $providerId = $socialiteUser->getId();
        $email = $socialiteUser->getEmail();

        $socialAccount = SocialAccount::where('provider', $provider)
            ->where('provider_id', $providerId)
            ->first();

        if ($socialAccount) {
            return $socialAccount->user;
        }

        if ($email) {
            $existingUser = User::where('email', $email)->first();
            if ($existingUser) {
                return $existingUser;
            }
        }

        return User::create([
            'name' => $socialiteUser->getName() ?: ($email ? Str::before($email, '@') : 'Usuario '.Str::random(6)),
            'email' => $email ?? Str::uuid().'@oauth.local',
            'password' => Str::password(40),
            'role' => 'client',
            'email_verified_at' => now(),
        ]);
    }

    protected function syncSocialAccount(User $user, string $provider, SocialiteUser $socialiteUser): SocialAccount
    {
        $provider = SocialAccount::normalizeProvider($provider);

        $account = SocialAccount::updateOrCreate([
            'provider' => $provider,
            'provider_id' => $socialiteUser->getId(),
        ], [
            'user_id' => $user->id,
            'email' => $socialiteUser->getEmail(),
            'avatar' => $socialiteUser->getAvatar(),
            'access_token' => $socialiteUser->token ?? null,
            'refresh_token' => $socialiteUser->refreshToken ?? null,
            'token_expires_at' => isset($socialiteUser->expiresIn)
                ? now()->addSeconds((int) $socialiteUser->expiresIn)
                : null,
            'profile_raw' => $this->extractRaw($socialiteUser),
        ]);

        return $account;
    }

    protected function extractRaw(SocialiteUser $socialiteUser): array
    {
        return array_filter([
            'id' => $socialiteUser->getId(),
            'name' => $socialiteUser->getName(),
            'nickname' => $socialiteUser->getNickname(),
            'email' => $socialiteUser->getEmail(),
            'avatar' => $socialiteUser->getAvatar(),
            'user' => $socialiteUser->user ?? null,
        ], fn ($value) => $value !== null);
    }

    protected function providerScopes(string $provider): array
    {
        return match ($provider) {
            'google' => ['openid', 'profile', 'email'],
            'microsoft' => ['openid', 'profile', 'email', 'offline_access', 'User.Read'],
            'apple' => ['name', 'email'],
            default => [],
        };
    }

    protected function providerExtraParameters(string $provider, OAuthState $state): array
    {
        if ($provider === 'apple') {
            return ['response_mode' => 'form_post'];
        }

        if ($provider === 'microsoft') {
            $tenant = Config::get('services.microsoft.tenant', 'common');
            return ['tenant' => $tenant];
        }

        return [];
    }

    protected function ensureRedirectAllowed(string $redirectUri): void
    {
        $allowed = Config::get('oauth.allowed_redirects', []);
        if (empty($allowed)) {
            return;
        }

        $normalized = rtrim($redirectUri, '/');

        foreach ($allowed as $candidate) {
            if ($normalized === rtrim($candidate, '/')) {
                return;
            }
        }

        Log::warning('Intento de redirect OAuth no permitido', [
            'redirect_uri' => $redirectUri,
        ]);

        throw new BadRequestHttpException('Redirect URI no permitido.');
    }
}
