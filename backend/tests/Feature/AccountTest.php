<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Tests for GET/POST/PUT/DELETE /accounts[/:id]
 */
class AccountTest extends TestCase
{
    public function test_list_requires_auth(): void
    {
        $r = $this->reqNoAuth('GET', '/accounts');
        $this->assertUnauthorized($r);
    }

    public function test_list_returns_array_with_seed_data(): void
    {
        $r = $this->req('GET', '/accounts');
        $this->assertOk($r);
        $this->assertIsArray($r['body']['data']);
        $this->assertGreaterThan(0, count($r['body']['data']));
    }

    public function test_create_with_required_fields(): void
    {
        $r = $this->req('POST', '/accounts', ['name' => '[TEST] Basic Account']);
        $this->assertCreated($r);
        $this->assertSame('[TEST] Basic Account', $r['body']['data']['name']);
        $this->cleanup('accounts', $r['body']['data']['id']);
    }

    public function test_create_with_all_fields(): void
    {
        $r = $this->req('POST', '/accounts', [
            'name'            => '[TEST] Full Account',
            'email'           => 'full@test.com',
            'phone'           => '+1-555-9000',
            'industry'        => 'Technology',
            'type'            => 'Customer',
            'website'         => 'https://full-test.com',
            'billing_address' => '1 Test St, Test City',
            'description'     => 'Full test account',
        ]);

        $this->assertCreated($r);
        $data = $r['body']['data'];
        $this->assertSame('Technology', $data['industry']);
        $this->assertSame('https://full-test.com', $data['website']);
        $this->cleanup('accounts', $data['id']);
    }

    public function test_create_without_name_returns_400(): void
    {
        $r = $this->req('POST', '/accounts', ['email' => 'noop@test.com']);
        $this->assertBadRequest($r);
    }

    public function test_create_sets_created_by_from_auth_user(): void
    {
        $r = $this->req('POST', '/accounts', ['name' => '[TEST] CreatedBy Account']);
        $this->assertCreated($r);
        $this->assertSame(1, (int) $r['body']['data']['created_by']); // mock token → user id 1
        $this->cleanup('accounts', $r['body']['data']['id']);
    }

    public function test_response_contains_expected_fields(): void
    {
        $r = $this->req('POST', '/accounts', ['name' => '[TEST] Fields Check']);
        $this->assertCreated($r);
        $data = $r['body']['data'];
        foreach (['id', 'name', 'email', 'phone', 'industry', 'type', 'website',
                  'billing_address', 'shipping_address', 'description', 'created_by', 'created_at', 'updated_at'] as $field) {
            $this->assertArrayHasKey($field, $data, "Missing field: {$field}");
        }
        $this->cleanup('accounts', $data['id']);
    }

    public function test_show_returns_account(): void
    {
        $created = $this->req('POST', '/accounts', ['name' => '[TEST] Show Account']);
        $id = $created['body']['data']['id'];
        $this->cleanup('accounts', $id);

        $r = $this->req('GET', "/accounts/{$id}");
        $this->assertOk($r);
        $this->assertSame($id, $r['body']['data']['id']);
        $this->assertSame('[TEST] Show Account', $r['body']['data']['name']);
    }

    public function test_show_nonexistent_returns_404(): void
    {
        $r = $this->req('GET', '/accounts/999999');
        $this->assertNotFound($r);
    }

    public function test_update_name_and_website(): void
    {
        $created = $this->req('POST', '/accounts', ['name' => '[TEST] Update Account']);
        $id = $created['body']['data']['id'];
        $this->cleanup('accounts', $id);

        $r = $this->req('PUT', "/accounts/{$id}", [
            'name'    => '[TEST] Updated Account',
            'website' => 'https://updated.test',
        ]);

        $this->assertOk($r);
        $this->assertSame('[TEST] Updated Account', $r['body']['data']['name']);
        $this->assertSame('https://updated.test', $r['body']['data']['website']);
    }

    public function test_update_nonexistent_returns_404(): void
    {
        $r = $this->req('PUT', '/accounts/999999', ['name' => 'Nope']);
        $this->assertNotFound($r);
    }

    public function test_delete_removes_account(): void
    {
        $created = $this->req('POST', '/accounts', ['name' => '[TEST] Delete Account']);
        $id = $created['body']['data']['id'];

        $del = $this->req('DELETE', "/accounts/{$id}");
        $this->assertOk($del);

        $check = $this->req('GET', "/accounts/{$id}");
        $this->assertNotFound($check);
    }

    public function test_delete_nonexistent_returns_404(): void
    {
        $r = $this->req('DELETE', '/accounts/999999');
        $this->assertNotFound($r);
    }

    public function test_update_only_allowed_fields(): void
    {
        $created = $this->req('POST', '/accounts', [
            'name'    => '[TEST] Partial Update',
            'phone'   => '+1-555-0001',
            'website' => 'https://original.test',
        ]);
        $id = $created['body']['data']['id'];
        $this->cleanup('accounts', $id);

        // Update only phone — name and website should stay the same
        $r = $this->req('PUT', "/accounts/{$id}", ['phone' => '+1-555-9999']);
        $this->assertOk($r);
        $this->assertSame('[TEST] Partial Update', $r['body']['data']['name']);
        $this->assertSame('+1-555-9999', $r['body']['data']['phone']);
        $this->assertSame('https://original.test', $r['body']['data']['website']);
    }
}
