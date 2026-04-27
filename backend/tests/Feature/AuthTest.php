<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Tests for POST /auth/login and POST /auth/logout.
 * Also covers the auth middleware (missing/invalid token).
 */
class AuthTest extends TestCase
{
    public function test_login_with_valid_admin_credentials(): void
    {
        $r = $this->reqNoAuth('POST', '/auth/login', [
            'email'    => 'admin@zenocrm.com',
            'password' => 'Admin@123',
        ]);

        $this->assertOk($r);
        $this->assertArrayHasKey('token', $r['body']['data']);
        $this->assertArrayHasKey('user', $r['body']['data']);
        $this->assertSame('admin', $r['body']['data']['user']['role']);
        $this->assertNotEmpty($r['body']['data']['token']);
    }

    public function test_login_returns_user_fields(): void
    {
        $r = $this->reqNoAuth('POST', '/auth/login', [
            'email'    => 'admin@zenocrm.com',
            'password' => 'Admin@123',
        ]);

        $this->assertOk($r);
        $user = $r['body']['data']['user'];
        foreach (['id', 'name', 'email', 'role', 'team', 'page_permissions'] as $field) {
            $this->assertArrayHasKey($field, $user, "Missing field: {$field}");
        }
    }

    public function test_login_returns_page_permissions_as_array(): void
    {
        $r = $this->reqNoAuth('POST', '/auth/login', [
            'email'    => 'sarah.connor@zenocrm.com',
            'password' => 'Admin@123',
        ]);

        $this->assertOk($r);
        $perms = $r['body']['data']['user']['page_permissions'];
        $this->assertIsArray($perms);
        $this->assertContains('accounts', $perms);
        $this->assertContains('leads', $perms);
    }

    public function test_login_with_wrong_password_returns_401(): void
    {
        $r = $this->reqNoAuth('POST', '/auth/login', [
            'email'    => 'admin@zenocrm.com',
            'password' => 'WrongPassword!',
        ]);

        $this->assertUnauthorized($r);
    }

    public function test_login_with_unknown_email_returns_401(): void
    {
        $r = $this->reqNoAuth('POST', '/auth/login', [
            'email'    => 'nobody@zenocrm.com',
            'password' => 'Admin@123',
        ]);

        $this->assertUnauthorized($r);
    }

    public function test_login_missing_password_returns_400(): void
    {
        $r = $this->reqNoAuth('POST', '/auth/login', ['email' => 'admin@zenocrm.com']);
        $this->assertBadRequest($r);
    }

    public function test_login_missing_email_returns_400(): void
    {
        $r = $this->reqNoAuth('POST', '/auth/login', ['password' => 'Admin@123']);
        $this->assertBadRequest($r);
    }

    public function test_login_empty_body_returns_400(): void
    {
        $r = $this->reqNoAuth('POST', '/auth/login');
        $this->assertBadRequest($r);
    }

    public function test_login_with_disabled_account_returns_403(): void
    {
        // lisa.simpson is is_active = 0 in seed data
        $r = $this->reqNoAuth('POST', '/auth/login', [
            'email'    => 'lisa.simpson@zenocrm.com',
            'password' => 'Admin@123',
        ]);

        $this->assertForbidden($r);
    }

    public function test_logout_returns_success(): void
    {
        $r = $this->req('POST', '/auth/logout');
        $this->assertOk($r);
    }

    public function test_protected_endpoint_without_token_returns_401(): void
    {
        $r = $this->reqNoAuth('GET', '/accounts');
        $this->assertUnauthorized($r);
    }

    public function test_protected_endpoint_with_malformed_token_returns_401(): void
    {
        $r = $this->req('GET', '/accounts', [], 'not.a.valid.token');
        $this->assertUnauthorized($r);
    }

    public function test_real_jwt_token_authenticates_requests(): void
    {
        $token = $this->login('admin@zenocrm.com', 'Admin@123');
        $this->assertNotEmpty($token);

        $r = $this->req('GET', '/accounts', [], $token);
        $this->assertOk($r);
    }

    public function test_mock_token_authenticates_requests(): void
    {
        $r = $this->req('GET', '/accounts', [], 'mock_jwt_token_admin');
        $this->assertOk($r);
    }
}
