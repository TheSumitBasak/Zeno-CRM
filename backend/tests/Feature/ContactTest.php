<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Tests for GET/POST/PUT/DELETE /contacts[/:id]
 */
class ContactTest extends TestCase
{
    public function test_list_requires_auth(): void
    {
        $r = $this->reqNoAuth('GET', '/contacts');
        $this->assertUnauthorized($r);
    }

    public function test_list_returns_array_with_seed_data(): void
    {
        $r = $this->req('GET', '/contacts');
        $this->assertOk($r);
        $this->assertIsArray($r['body']['data']);
        $this->assertGreaterThan(0, count($r['body']['data']));
    }

    public function test_create_with_required_fields(): void
    {
        $r = $this->req('POST', '/contacts', [
            'first_name' => 'Test',
            'last_name'  => 'Contact',
        ]);

        $this->assertCreated($r);
        $this->assertSame('Test', $r['body']['data']['first_name']);
        $this->assertSame('Contact', $r['body']['data']['last_name']);
        $this->cleanup('contacts', $r['body']['data']['id']);
    }

    public function test_create_with_all_fields(): void
    {
        $r = $this->req('POST', '/contacts', [
            'first_name'  => 'Full',
            'last_name'   => 'Contact',
            'email'       => 'full.contact@test.com',
            'phone'       => '+1-555-0002',
            'title'       => 'Senior Engineer',
            'department'  => 'Engineering',
            'address'     => '100 Test Ave',
            'birthday'    => '1990-06-15',
            'description' => 'Full contact record',
        ]);

        $this->assertCreated($r);
        $data = $r['body']['data'];
        $this->assertSame('1990-06-15', $data['birthday']);
        $this->assertSame('Engineering', $data['department']);
        $this->cleanup('contacts', $data['id']);
    }

    public function test_create_without_first_name_returns_400(): void
    {
        $r = $this->req('POST', '/contacts', ['last_name' => 'NoFirst']);
        $this->assertBadRequest($r);
    }

    public function test_create_without_last_name_returns_400(): void
    {
        $r = $this->req('POST', '/contacts', ['first_name' => 'NoLast']);
        $this->assertBadRequest($r);
    }

    public function test_create_empty_body_returns_400(): void
    {
        $r = $this->req('POST', '/contacts');
        $this->assertBadRequest($r);
    }

    public function test_create_linked_to_account(): void
    {
        $r = $this->req('POST', '/contacts', [
            'first_name' => 'Linked',
            'last_name'  => 'Contact',
            'account_id' => 1, // Acme Corporation from seed data
        ]);

        $this->assertCreated($r);
        $this->assertSame(1, (int) $r['body']['data']['account_id']);
        $this->cleanup('contacts', $r['body']['data']['id']);
    }

    public function test_show_returns_contact(): void
    {
        $created = $this->req('POST', '/contacts', [
            'first_name' => 'Show',
            'last_name'  => 'Contact',
        ]);
        $id = $created['body']['data']['id'];
        $this->cleanup('contacts', $id);

        $r = $this->req('GET', "/contacts/{$id}");
        $this->assertOk($r);
        $this->assertSame($id, $r['body']['data']['id']);
    }

    public function test_show_nonexistent_returns_404(): void
    {
        $r = $this->req('GET', '/contacts/999999');
        $this->assertNotFound($r);
    }

    public function test_update_contact_fields(): void
    {
        $created = $this->req('POST', '/contacts', [
            'first_name' => 'Update',
            'last_name'  => 'Contact',
        ]);
        $id = $created['body']['data']['id'];
        $this->cleanup('contacts', $id);

        $r = $this->req('PUT', "/contacts/{$id}", [
            'title'      => 'VP Engineering',
            'department' => 'Engineering',
        ]);

        $this->assertOk($r);
        $this->assertSame('VP Engineering', $r['body']['data']['title']);
        $this->assertSame('Engineering', $r['body']['data']['department']);
    }

    public function test_update_nonexistent_returns_404(): void
    {
        $r = $this->req('PUT', '/contacts/999999', ['first_name' => 'Nope']);
        $this->assertNotFound($r);
    }

    public function test_delete_removes_contact(): void
    {
        $created = $this->req('POST', '/contacts', [
            'first_name' => 'Delete',
            'last_name'  => 'Contact',
        ]);
        $id = $created['body']['data']['id'];

        $del = $this->req('DELETE', "/contacts/{$id}");
        $this->assertOk($del);

        $check = $this->req('GET', "/contacts/{$id}");
        $this->assertNotFound($check);
    }

    public function test_delete_nonexistent_returns_404(): void
    {
        $r = $this->req('DELETE', '/contacts/999999');
        $this->assertNotFound($r);
    }

    public function test_response_contains_expected_fields(): void
    {
        $r = $this->req('POST', '/contacts', [
            'first_name' => 'Fields',
            'last_name'  => 'Check',
        ]);
        $this->assertCreated($r);
        $data = $r['body']['data'];
        foreach (['id', 'account_id', 'first_name', 'last_name', 'email', 'phone',
                  'title', 'department', 'address', 'birthday', 'description', 'created_at', 'updated_at'] as $field) {
            $this->assertArrayHasKey($field, $data, "Missing field: {$field}");
        }
        $this->cleanup('contacts', $data['id']);
    }
}
