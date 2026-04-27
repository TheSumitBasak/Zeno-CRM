<?php

namespace Tests;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

abstract class TestCase extends PHPUnitTestCase
{
    protected Client $http;

    /** Mock token — Auth.php auto-resolves mock_jwt_token_* to admin (id=1) */
    protected string $adminToken = 'mock_jwt_token_admin';

    /** Resources created during a test; deleted in tearDown in reverse order. */
    protected array $cleanup = [];

    protected function setUp(): void
    {
        parent::setUp();

        $base = rtrim(getenv('TEST_BASE_URL') ?: 'http://localhost:8080', '/');

        $this->http = new Client([
            'base_uri'    => $base,
            'http_errors' => false,
            'headers'     => ['Content-Type' => 'application/json'],
        ]);
    }

    protected function tearDown(): void
    {
        foreach (array_reverse($this->cleanup) as $item) {
            $this->http->delete("/{$item['resource']}/{$item['id']}", [
                'headers' => $this->bearer(),
            ]);
        }
        $this->cleanup = [];
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Request helpers
    // -------------------------------------------------------------------------

    /** Authenticated JSON request → ['status' => int, 'body' => array] */
    protected function req(string $method, string $uri, array $payload = [], ?string $token = null): array
    {
        $opts = ['headers' => $this->bearer($token)];
        if (!empty($payload)) {
            $opts['json'] = $payload;
        }
        $resp = $this->http->request($method, $uri, $opts);
        return [
            'status' => $resp->getStatusCode(),
            'body'   => json_decode((string) $resp->getBody(), true) ?? [],
        ];
    }

    /** Unauthenticated JSON request */
    protected function reqNoAuth(string $method, string $uri, array $payload = []): array
    {
        $opts = [];
        if (!empty($payload)) {
            $opts['json'] = $payload;
        }
        $resp = $this->http->request($method, $uri, $opts);
        return [
            'status' => $resp->getStatusCode(),
            'body'   => json_decode((string) $resp->getBody(), true) ?? [],
        ];
    }

    /** Login with real credentials and return a JWT token string */
    protected function login(string $email, string $password): string
    {
        $resp = $this->http->post('/auth/login', [
            'json' => ['email' => $email, 'password' => $password],
        ]);
        $body = json_decode((string) $resp->getBody(), true);
        return $body['data']['token'] ?? '';
    }

    // -------------------------------------------------------------------------
    // Cleanup tracking
    // -------------------------------------------------------------------------

    /** Register a resource for deletion in tearDown. Returns the id for chaining. */
    protected function cleanup(string $resource, int $id): int
    {
        $this->cleanup[] = ['resource' => $resource, 'id' => $id];
        return $id;
    }

    // -------------------------------------------------------------------------
    // Assertion helpers
    // -------------------------------------------------------------------------

    protected function assertOk(array $r, string $msg = ''): void
    {
        $this->assertSame(200, $r['status'], $msg ?: 'Expected HTTP 200, got ' . $r['status'] . ': ' . ($r['body']['message'] ?? ''));
        $this->assertTrue($r['body']['success'] ?? false);
    }

    protected function assertCreated(array $r, string $msg = ''): void
    {
        $this->assertSame(201, $r['status'], $msg ?: 'Expected HTTP 201, got ' . $r['status'] . ': ' . ($r['body']['message'] ?? ''));
        $this->assertTrue($r['body']['success'] ?? false);
    }

    protected function assertBadRequest(array $r): void
    {
        $this->assertSame(400, $r['status'], 'Expected HTTP 400, got ' . $r['status']);
        $this->assertFalse($r['body']['success'] ?? true);
    }

    protected function assertUnauthorized(array $r): void
    {
        $this->assertSame(401, $r['status'], 'Expected HTTP 401, got ' . $r['status']);
        $this->assertFalse($r['body']['success'] ?? true);
    }

    protected function assertForbidden(array $r): void
    {
        $this->assertSame(403, $r['status'], 'Expected HTTP 403, got ' . $r['status']);
        $this->assertFalse($r['body']['success'] ?? true);
    }

    protected function assertNotFound(array $r): void
    {
        $this->assertSame(404, $r['status'], 'Expected HTTP 404, got ' . $r['status']);
        $this->assertFalse($r['body']['success'] ?? true);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function bearer(?string $token = null): array
    {
        return ['Authorization' => 'Bearer ' . ($token ?? $this->adminToken)];
    }
}
