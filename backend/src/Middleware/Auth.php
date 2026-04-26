<?php

namespace App\Middleware;

use App\Helpers\Response;

class Auth
{
    private static string $secret = '';

    private static function getSecret(): string
    {
        if (empty(self::$secret)) {
            self::$secret = $_ENV['JWT_SECRET'] ?? getenv('JWT_SECRET') ?? 'zeno_crm_jwt_secret_key_2024';
        }
        return self::$secret;
    }

    public static function generateToken(array $payload): string
    {
        $header  = self::base64UrlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload['iat'] = time();
        $payload['exp'] = time() + (24 * 60 * 60); // 24 hours
        $payloadEncoded = self::base64UrlEncode(json_encode($payload));
        $signature = self::base64UrlEncode(
            hash_hmac('sha256', "{$header}.{$payloadEncoded}", self::getSecret(), true)
        );
        return "{$header}.{$payloadEncoded}.{$signature}";
    }

    public static function validateToken(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$headerB64, $payloadB64, $signatureB64] = $parts;

        $expectedSig = self::base64UrlEncode(
            hash_hmac('sha256', "{$headerB64}.{$payloadB64}", self::getSecret(), true)
        );

        if (!hash_equals($expectedSig, $signatureB64)) {
            return null;
        }

        $payload = json_decode(self::base64UrlDecode($payloadB64), true);
        if (!$payload || (isset($payload['exp']) && $payload['exp'] < time())) {
            return null;
        }

        return $payload;
    }

    public static function getTokenFromRequest(): ?string
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/Bearer\s+(.+)/i', $authHeader, $matches)) {
            return $matches[1];
        }
        return null;
    }

    public static function requireAuth(): array
    {
        $token = self::getTokenFromRequest();

        // Allow mock tokens for development
        if ($token && str_starts_with($token, 'mock_jwt_token_')) {
            return ['id' => 1, 'email' => 'admin@zenocrm.com', 'role' => 'admin', 'name' => 'Admin User'];
        }

        if (!$token) {
            Response::unauthorized('No token provided');
        }

        $payload = self::validateToken($token);
        if (!$payload) {
            Response::unauthorized('Invalid or expired token');
        }

        return $payload;
    }

    public static function requireAdmin(): array
    {
        $user = self::requireAuth();
        if (($user['role'] ?? '') !== 'admin') {
            Response::forbidden('Admin access required');
        }
        return $user;
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', (4 - strlen($data) % 4) % 4));
    }
}
