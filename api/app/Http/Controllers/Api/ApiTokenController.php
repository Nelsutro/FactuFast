<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiTokenLog;
use App\Models\PersonalAccessToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Laravel\Sanctum\Contracts\HasApiTokens;

class ApiTokenController extends Controller
{
    private const AVAILABLE_ABILITIES = [
        'api:read-dashboard',
        'api:manage-settings',
        'api:read-invoices',
        'api:write-invoices',
        'api:import-invoices',
        'api:read-clients',
        'api:write-clients',
        'api:read-quotes',
        'api:write-quotes',
        'api:import-quotes',
        'api:read-payments',
        'api:write-payments',
        'api:read-companies',
    ];

    public function index(Request $request): JsonResponse
    {
        /** @var User&HasApiTokens|null $user */
        $user = Auth::user();
        $this->authorizeUser($user);

        $tokens = $user->tokens()->orderByDesc('created_at')->get();
        $tokenIds = $tokens->pluck('id');

        $aggregated = [];
        if ($tokenIds->isNotEmpty()) {
            $sevenDaysAgo = now()->subDays(7);

            $baseStats = ApiTokenLog::query()
                ->selectRaw('personal_access_token_id, COUNT(*) as total_requests, MAX(created_at) as last_request_at')
                ->whereIn('personal_access_token_id', $tokenIds)
                ->groupBy('personal_access_token_id')
                ->get()
                ->keyBy('personal_access_token_id');

            $lastWeek = ApiTokenLog::query()
                ->selectRaw('personal_access_token_id, COUNT(*) as requests_last_7_days')
                ->whereIn('personal_access_token_id', $tokenIds)
                ->where('created_at', '>=', $sevenDaysAgo)
                ->groupBy('personal_access_token_id')
                ->pluck('requests_last_7_days', 'personal_access_token_id');

            $lastError = ApiTokenLog::query()
                ->select(['personal_access_token_id', 'status_code', 'created_at'])
                ->whereIn('personal_access_token_id', $tokenIds)
                ->where('status_code', '>=', 400)
                ->orderByDesc('created_at')
                ->get()
                ->groupBy('personal_access_token_id')
                ->map(fn ($group) => $group->first());

            foreach ($tokenIds as $id) {
                $stats = $baseStats->get($id);
                $lastRequestAt = $stats && $stats->last_request_at
                    ? Carbon::parse($stats->last_request_at)
                    : null;
                $errorEntry = $lastError->get($id) ?? null;

                $aggregated[$id] = [
                    'total_requests' => (int) ($stats->total_requests ?? 0),
                    'last_request_at' => optional($lastRequestAt)->toIso8601String(),
                    'requests_last_7_days' => (int) ($lastWeek[$id] ?? 0),
                    'last_error_at' => optional($errorEntry?->created_at)->toIso8601String(),
                    'last_error_status' => $errorEntry?->status_code,
                ];
            }
        }

        $payload = $tokens->map(function (PersonalAccessToken $token) use ($aggregated) {
            $metrics = $aggregated[$token->id] ?? [
                'total_requests' => 0,
                'last_request_at' => null,
                'requests_last_7_days' => 0,
                'last_error_at' => null,
                'last_error_status' => null,
            ];

            return [
                'id' => $token->id,
                'name' => $token->name,
                'abilities' => $token->abilities,
                'rate_limit_per_minute' => $token->meta['rate_limit_per_minute'] ?? config('services.api_tokens.default_rate'),
                'rate_limit_decay_seconds' => $token->meta['rate_limit_decay_seconds'] ?? config('services.api_tokens.default_decay', 60),
                'created_at' => $token->created_at,
                'last_used_at' => $token->last_used_at,
                'expires_at' => $token->expires_at,
                'revoked' => $token->isRevoked(),
                'metrics' => $metrics,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $payload,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        /** @var User&HasApiTokens|null $user */
        $user = Auth::user();
        $this->authorizeUser($user);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'abilities' => 'required|array|min:1',
            'abilities.*' => 'string',
            'rate_limit_per_minute' => 'nullable|integer|min:10|max:10000',
            'expires_in_days' => 'nullable|integer|min:1|max:365',
            'description' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $abilities = collect($request->input('abilities', []))
            ->unique()
            ->filter(fn ($ability) => in_array($ability, self::AVAILABLE_ABILITIES, true))
            ->values()
            ->all();

        if (empty($abilities)) {
            throw new HttpException(422, 'No se especificaron habilidades válidas');
        }

        $expiresAt = null;
        if ($days = $request->integer('expires_in_days')) {
            $expiresAt = now()->addDays($days);
        } else {
            $defaultDays = config('services.api_tokens.default_expiration_days');
            if ($defaultDays) {
                $expiresAt = now()->addDays($defaultDays);
            }
        }

        $plainToken = $user->createToken(
            $request->string('name')->trim()->value(),
            $abilities,
            $expiresAt
        );

        $rateLimit = $request->integer('rate_limit_per_minute') ?? config('services.api_tokens.default_rate');

        $accessToken = $plainToken->accessToken;
        $accessToken->forceFill([
            'meta' => array_filter([
                'rate_limit_per_minute' => $rateLimit,
                'rate_limit_decay_seconds' => config('services.api_tokens.default_decay', 60),
                'description' => $request->input('description'),
                'token_prefix' => Str::before($plainToken->plainTextToken, '|'),
                'company_id' => $user->company_id,
            ], fn ($value) => !is_null($value)),
        ])->save();

        return response()->json([
            'success' => true,
            'message' => 'Token API generado correctamente',
            'data' => [
                'token' => $plainToken->plainTextToken,
                'token_id' => $accessToken->id,
                'expires_at' => $accessToken->expires_at,
                'rate_limit_per_minute' => $rateLimit,
                'abilities' => $abilities,
            ],
        ], 201);
    }

    public function destroy(string $tokenId): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $this->authorizeUser($user);

        $token = $user->tokens()->where('id', $tokenId)->first();

        if (!$token) {
            throw new HttpException(404, 'Token no encontrado');
        }

        $token->revoke();

        return response()->json([
            'success' => true,
            'message' => 'Token revocado correctamente',
        ]);
    }

    public function logs(Request $request, string $tokenId): JsonResponse
    {
        /** @var User&HasApiTokens|null $user */
        $user = Auth::user();
        $this->authorizeUser($user);

        /** @var PersonalAccessToken|null $token */
        $token = $user->tokens()->where('id', $tokenId)->first();

        if (!$token) {
            throw new HttpException(404, 'Token no encontrado');
        }

        $query = ApiTokenLog::query()
            ->where('personal_access_token_id', $token->id)
            ->orderByDesc('created_at');

        if ($request->filled('since')) {
            try {
                $since = Carbon::parse($request->string('since')->toString());
                $query->where('created_at', '>=', $since);
            } catch (\Throwable $e) {
                throw new HttpException(422, 'Fecha "since" inválida');
            }
        }

        if ($request->boolean('only_errors')) {
            $query->where('status_code', '>=', 400);
        }

        $perPage = (int) $request->integer('per_page', 25);
        $perPage = min(100, max(10, $perPage));

        $logs = $query->paginate($perPage);

        $transformed = collect($logs->items())->map(function (ApiTokenLog $log) {
            return [
                'id' => $log->id,
                'ip' => $log->ip,
                'method' => $log->method,
                'path' => $log->path,
                'status_code' => $log->status_code,
                'duration_ms' => $log->duration_ms,
                'meta' => $log->meta,
                'created_at' => optional($log->created_at)->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'token' => [
                    'id' => $token->id,
                    'name' => $token->name,
                    'abilities' => $token->abilities,
                    'revoked' => $token->isRevoked(),
                    'last_used_at' => optional($token->last_used_at)->toIso8601String(),
                ],
                'logs' => $transformed,
            ],
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
                'last_page' => $logs->lastPage(),
            ],
        ]);
    }

    protected function authorizeUser(?User $user): void
    {
        if (!$user) {
            throw new HttpException(401, 'No autenticado');
        }

        if (!in_array($user->role, ['admin', 'client'], true)) {
            throw new HttpException(403, 'No autorizado para administrar tokens');
        }
    }
}
