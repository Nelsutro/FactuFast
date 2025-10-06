<?php

namespace App\Http\Middleware;

use App\Models\ApiTokenLog;
use App\Models\PersonalAccessToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnforceApiTokenPolicies
{
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);

        $user = $request->user('sanctum') ?? $request->user();
        $token = $user?->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            if ($token->isRevoked()) {
                return response()->json([
                    'message' => 'Este token fue revocado.',
                ], 401);
            }

            if ($token->expires_at && now()->greaterThan($token->expires_at)) {
                $token->revoke();

                return response()->json([
                    'message' => 'Este token expiró.',
                ], 401);
            }

            $rateLimit = max(1, $token->rateLimit());
            $decay = $token->rateLimitDecaySeconds();
            $key = $token->rateLimiterKey($request->ip());

            if (RateLimiter::tooManyAttempts($key, $rateLimit)) {
                $retryAfter = RateLimiter::availableIn($key);

                return response()->json([
                    'message' => 'Límite de peticiones excedido para este token.',
                    'retry_after_seconds' => $retryAfter,
                ], 429)->header('Retry-After', $retryAfter);
            }

            RateLimiter::hit($key, $decay);
        }

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $next($request);

        if ($token instanceof PersonalAccessToken) {
            $duration = (int) round((microtime(true) - $start) * 1000);

            ApiTokenLog::create([
                'personal_access_token_id' => $token->id,
                'user_id' => $token->tokenable_id,
                'company_id' => $token->meta['company_id'] ?? null,
                'ip' => $request->ip(),
                'method' => $request->method(),
                'path' => Str::limit($request->path(), 255, ''),
                'status_code' => $response->getStatusCode(),
                'duration_ms' => $duration,
                'meta' => array_filter([
                    'user_agent' => Str::limit($request->userAgent() ?? '', 255, ''),
                    'query' => $request->query() ?: null,
                ]),
            ]);
        }

        return $response;
    }
}
