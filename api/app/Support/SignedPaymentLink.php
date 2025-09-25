<?php

namespace App\Support;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Crypt;

class SignedPaymentLink
{
    public static function generate(int $invoiceId, int $companyId, int $ttlSeconds = 86400): string
    {
        $exp = now()->addSeconds($ttlSeconds)->timestamp;
        $payload = json_encode([
            'i' => $invoiceId,
            'c' => $companyId,
            'e' => $exp
        ]);
        $key = config('app.key');
        $sig = hash_hmac('sha256', $payload, $key);
        return rtrim(strtr(base64_encode($payload), '+/', '-_'), '=') . '.' . $sig;
    }

    public static function parse(string $hash): ?array
    {
        if (!str_contains($hash, '.')) return null;
        [$b64, $sig] = explode('.', $hash, 2);
        $payload = base64_decode(strtr($b64, '-_', '+/'));
        if (!$payload) return null;
        $expected = hash_hmac('sha256', $payload, config('app.key'));
        if (!hash_equals($expected, $sig)) return null;
        $data = json_decode($payload, true);
        if (!$data || ($data['e'] ?? 0) < now()->timestamp) return null;
        return [
            'invoice_id' => $data['i'],
            'company_id' => $data['c'],
            'expires_at' => $data['e'],
        ];
    }
}
