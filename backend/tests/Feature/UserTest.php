<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Tests for GET/POST/PUT/DELETE /users[/:id]
 * All endpoints require admin role.
 */
class UserTest extends TestCase
{
    private ?string $userToken = null;

    /** Get a real JWT for a non-admin user (sarah.connor, role=user) */
    private function regularUserToken(): string
    {
        if ($this->userToken === null) {
            $this->userToken = $this->login('sarah.connor@zenocrm.com', 'Admin@123');
        }
        return $this->userToken;
    }

    // ---- Auth guards --------------------------------------------------------

    public function test_list_without_auth_returns_401(): void
    {
        $r = $this->reqNoAuth('GET', '/users');
        $this->assertUnauthorized($r);
    }

    public function test_list_with_regular_user_token_returns_403(): void
    {
        $r = $this->req('GET', '/users', [], $this->regularUserToken());
        $this->assertForbidden($r);
    }

    public function test_create_with_regular_user_token_returns_403(): void
    {
        $r = $this->req('POST', '/users', [
            'name'     => 'Should Not Create',
            'email'    => 'nope@test.com',
            'password' => 'Test@123',
        ], $this->regularUserToken());
        $this->assertForbidden($r);
    }

    // ---- Admin CRUD ---------------------------------------------------------

    public function test_list_returns_array_for_admin(): void
    {
        $r = $this->req('GET', '/users');
        $this->assertOk($r);
        $this->assertIsArray($r['body']['data']);
        $this->assertGreaterThan(0, count($r['body']['data']));
    }

    public function test_list_does_not_expose_passwords(): void
    {
        $r = $this->req('GET', '/users');
        $this->assertOk($r);
        foreach ($r['body']['data'] as $user) {
            $this->assertArrayNotHasKey('password', $user, 'Password should not be in list response');
        }
    }

    public function test_create_user(): void
    {
        $email = 'testuser_' . time() . '@test.com';
        $r = $this->req('POST', '/users', [
            'name'     => '[TEST] New User',
            'email'    => $email,
            'password' => 'TestPass@123',
            'role'     => 'user',
            'team'     => 'Sales',
        ]);

        $this->assertCreated($r);
        $data = $r['body']['data'];
        $this->assertSame('[TEST] New User', $data['name']);
        $this->assertSame($email, $data['email']);
        $this->assertSame('user', $data['role']);
        $this->assertSame('Sales', $data['team']);
        $this->assertArrayNotHasKey('password', $data);
        $this->cleanup('users', $data['id']);
    }

    public function test_create_user_defaults_role_to_user(): void
    {
        $r = $this->req('POST', '/users', [
            'name'     => '[TEST] Default Role User',
            'email'    => 'defaultrole_' . time() . '@test.com',
            'password' => 'TestPass@123',
        ]);

        $this->assertCreated($r);
        $this->assertSame('user', $r['body']['data']['role']);
        $this->cleanup('users', $r['body']['data']['id']);
    }

    public function test_create_user_with_page_permissions(): void
    {
        $r = $this->req('POST', '/users', [
            'name'             => '[TEST] Permissions User',
            'email'            => 'perms_' . time() . '@test.com',
            'password'         => 'TestPass@123',
            'page_permissions' => ['leads', 'contacts'],
        ]);

        $this->assertCreated($r);
        $perms = $r['body']['data']['page_permissions'];
        $this->assertIsArray($perms);
        $this->assertContains('leads', $perms);
        $this->cleanup('users', $r['body']['data']['id']);
    }

    public function test_create_user_missing_name_returns_400(): void
    {
        $r = $this->req('POST', '/users', [
            'email'    => 'missing@test.com',
            'password' => 'TestPass@123',
        ]);
        $this->assertBadRequest($r);
    }

    public function test_create_user_missing_email_returns_400(): void
    {
        $r = $this->req('POST', '/users', [
            'name'     => 'No Email',
            'password' => 'TestPass@123',
        ]);
        $this->assertBadRequest($r);
    }

    public function test_create_user_missing_password_returns_400(): void
    {
        $r = $this->req('POST', '/users', [
            'name'  => 'No Password',
            'email' => 'nopass@test.com',
        ]);
        $this->assertBadRequest($r);
    }

    public function test_create_user_duplicate_email_returns_400(): void
    {
        $r = $this->req('POST', '/users', [
            'name'     => 'Dup Email',
            'email'    => 'admin@zenocrm.com', // Already exists in seed data
            'password' => 'TestPass@123',
        ]);
        $this->assertBadRequest($r);
    }

    public function test_show_returns_user(): void
    {
        $r = $this->req('GET', '/users/1');
        $this->assertOk($r);
        $this->assertSame(1, $r['body']['data']['id']);
        $this->assertArrayNotHasKey('password', $r['body']['data']);
    }

    public function test_show_nonexistent_returns_404(): void
    {
        $r = $this->req('GET', '/users/999999');
        $this->assertNotFound($r);
    }

    public function test_show_with_regular_user_returns_403(): void
    {
        $r = $this->req('GET', '/users/1', [], $this->regularUserToken());
        $this->assertForbidden($r);
    }

    public function test_update_user_name_and_team(): void
    {
        $created = $this->req('POST', '/users', [
            'name'     => '[TEST] Update User',
            'email'    => 'update_' . time() . '@test.com',
            'password' => 'TestPass@123',
        ]);
        $id = $created['body']['data']['id'];
        $this->cleanup('users', $id);

        $r = $this->req('PUT', "/users/{$id}", [
            'name' => '[TEST] Updated User',
            'team' => 'Support',
        ]);

        $this->assertOk($r);
        $this->assertSame('[TEST] Updated User', $r['body']['data']['name']);
        $this->assertSame('Support', $r['body']['data']['team']);
    }

    public function test_update_user_deactivate(): void
    {
        $created = $this->req('POST', '/users', [
            'name'     => '[TEST] Deactivate User',
            'email'    => 'deactivate_' . time() . '@test.com',
            'password' => 'TestPass@123',
        ]);
        $id = $created['body']['data']['id'];
        $this->cleanup('users', $id);

        $r = $this->req('PUT', "/users/{$id}", ['is_active' => 0]);
        $this->assertOk($r);
        $this->assertSame(0, (int) $r['body']['data']['is_active']);
    }

    public function test_update_nonexistent_returns_404(): void
    {
        $r = $this->req('PUT', '/users/999999', ['name' => 'Nope']);
        $this->assertNotFound($r);
    }

    public function test_delete_user(): void
    {
        $created = $this->req('POST', '/users', [
            'name'     => '[TEST] Delete User',
            'email'    => 'delete_' . time() . '@test.com',
            'password' => 'TestPass@123',
        ]);
        $id = $created['body']['data']['id'];

        $del = $this->req('DELETE', "/users/{$id}");
        $this->assertOk($del);

        $check = $this->req('GET', "/users/{$id}");
        $this->assertNotFound($check);
    }

    public function test_delete_nonexistent_returns_404(): void
    {
        $r = $this->req('DELETE', '/users/999999');
        $this->assertNotFound($r);
    }
}
