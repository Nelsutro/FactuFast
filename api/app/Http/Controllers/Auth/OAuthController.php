<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\OAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class OAuthController extends Controller
{
    public function __construct(private readonly OAuthService $oauthService)
    {
    }

    public function providers(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->oauthService->getSupportedProviders(),
        ]);
    }

    public function redirect(Request $request, string $provider): JsonResponse
    {
        $redirectUri = $request->query('redirect_uri', Config::get('oauth.default_redirect'));
        $payload = Arr::only($request->all(), ['return_url', 'from']);

        $state = $this->oauthService->createState($provider, $redirectUri, $payload);
        $authUrl = $this->oauthService->buildAuthorizationUrl($provider, $state);

        return response()->json([
            'success' => true,
            'data' => [
                'authorization_url' => $authUrl,
                'state' => $state->state,
                'expires_at' => $state->expires_at->toIso8601String(),
                'provider' => $state->provider,
            ],
        ]);
    }

    public function callback(Request $request, string $provider): Response
    {
        $defaultRedirect = Config::get('oauth.default_redirect');
        $statusParam = Config::get('oauth.status_param', 'status');
        $tokenParam = Config::get('oauth.token_param', 'token');
        $messageParam = Config::get('oauth.message_param', 'message');

        try {
            $result = $this->oauthService->handleCallback($provider);
            $state = $result['state'];
            $user = $result['user'];
            $redirectUri = $state->redirect_uri ?: $defaultRedirect;

            $query = [
                $statusParam => 'success',
                $tokenParam => $result['token'],
                'provider' => $provider,
                'email' => $user->email,
                'state' => $state->state,
            ];

            if (!$user->company_id) {
                $query['requires_company'] = '1';
            }

            if ($state->payload && isset($state->payload['return_url'])) {
                $query['return_url'] = $state->payload['return_url'];
            }

            $finalUrl = $this->mergeQueryString($redirectUri, $query);

            return redirect()->away($finalUrl);
        } catch (\Throwable $e) {
            Log::error('Error en callback OAuth', [
                'provider' => $provider,
                'message' => $e->getMessage(),
            ]);

            $errorUrl = $this->mergeQueryString($defaultRedirect, [
                $statusParam => 'error',
                $messageParam => 'No fue posible iniciar sesión con '.$provider.'.',
            ]);

            return redirect()->away($errorUrl);
        }
    }

    protected function mergeQueryString(string $baseUrl, array $params): string
    {
        $parsed = parse_url($baseUrl) ?: [];
        $existing = [];

        if (!empty($parsed['query'])) {
            parse_str($parsed['query'], $existing);
        }

        $merged = array_filter(array_merge($existing, $params), fn ($value) => $value !== null && $value !== '');

        $query = http_build_query($merged);

        $scheme = $parsed['scheme'] ?? 'https';
        $host = $parsed['host'] ?? '';
        $path = $parsed['path'] ?? '';
        $port = isset($parsed['port']) ? ':'.$parsed['port'] : '';
        $fragment = isset($parsed['fragment']) ? '#'.$parsed['fragment'] : '';

        return sprintf('%s://%s%s%s%s%s',
            $scheme,
            $host,
            $port,
            $path,
            $query ? '?'.$query : '',
            $fragment
        );
    }
}
