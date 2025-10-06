<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PersonalAccessToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Laravel\Sanctum\Contracts\HasApiTokens;

class ApiTokenController extends Controller
{
    private const AVAILABLE_ABILITIES = [
        'api:read-invoices',
        'api:write-invoices',
        'api:read-clients',
        'api:write-clients',
        'api:import-invoices',
        'api:import-quotes',
    ];

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
    /** @var User&HasApiTokens|null $user */
    $user = Auth::user();
        $this->authorizeUser($user);

        $tokens = $user->tokens()->orderByDesc('created_at')->get()->map(function (PersonalAccessToken $token) {
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
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $tokens,
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
