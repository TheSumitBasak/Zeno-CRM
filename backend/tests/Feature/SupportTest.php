<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Tests for GET/POST/PUT/DELETE /support[/:id]
 */
class SupportTest extends TestCase
{
    public function test_list_requires_auth(): void
    {
        $r = $this->reqNoAuth('GET', '/support');
        $this->assertUnauthorized($r);
    }

    public function test_list_returns_array(): void
    {
        $r = $this->req('GET', '/support');
        $this->assertOk($r);
        $this->assertIsArray($r['body']['data']);
    }

    public function test_create_with_required_fields(): void
    {
        $r = $this->req('POST', '/support', ['subject' => '[TEST] Basic Ticket']);

        $this->assertCreated($r);
        $data = $r['body']['data'];
        $this->assertSame('[TEST] Basic Ticket', $data['subject']);
        $this->assertSame('open', $data['status']);   // default
        $this->assertSame('medium', $data['priority']); // default
        $this->cleanup('support', $data['id']);
    }

    public function test_create_with_all_fields(): void
    {
        $r = $this->req('POST', '/support', [
            'subject'     => '[TEST] Full Ticket',
            'status'      => 'in_progress',
            'priority'    => 'urgent',
            'contact_id'  => 1,
            'account_id'  => 1,
            'assigned_to' => 1,
            'description' => 'Full ticket description',
            'resolution'  => 'Pending investigation',
        ]);

        $this->assertCreated($r);
        $data = $r['body']['data'];
        $this->assertSame('urgent', $data['priority']);
        $this->assertSame(1, (int) $data['contact_id']);
        $this->assertSame(1, (int) $data['account_id']);
        $this->cleanup('support', $data['id']);
    }

    public function test_create_without_subject_returns_400(): void
    {
        $r = $this->req('POST', '/support', ['priority' => 'high']);
        $this->assertBadRequest($r);
    }

    public function test_create_linked_to_lead(): void
    {
        $r = $this->req('POST', '/support', [
            'subject' => '[TEST] Lead-Linked Ticket',
            'lead_id' => 1, // Michael Scott from seed data
        ]);

        $this->assertCreated($r);
        $this->assertSame(1, (int) $r['body']['data']['lead_id']);
        $this->cleanup('support', $r['body']['data']['id']);
    }

    public function test_show_returns_ticket(): void
    {
        $created = $this->req('POST', '/support', ['subject' => '[TEST] Show Ticket']);
        $id = $created['body']['data']['id'];
        $this->cleanup('support', $id);

        $r = $this->req('GET', "/support/{$id}");
        $this->assertOk($r);
        $this->assertSame($id, $r['body']['data']['id']);
    }

    public function test_show_nonexistent_returns_404(): void
    {
        $r = $this->req('GET', '/support/999999');
        $this->assertNotFound($r);
    }

    public function test_update_status_to_in_progress(): void
    {
        $created = $this->req('POST', '/support', [
            'subject' => '[TEST] Progress Ticket',
            'status'  => 'open',
        ]);
        $id = $created['body']['data']['id'];
        $this->cleanup('support', $id);

        $r = $this->req('PUT', "/support/{$id}", ['status' => 'in_progress']);
        $this->assertOk($r);
        $this->assertSame('in_progress', $r['body']['data']['status']);
    }

    public function test_resolve_ticket_with_resolution_text(): void
    {
        $created = $this->req('POST', '/support', [
            'subject' => '[TEST] Resolve Ticket',
        ]);
        $id = $created['body']['data']['id'];
        $this->cleanup('support', $id);

        $r = $this->req('PUT', "/support/{$id}", [
            'status'     => 'resolved',
            'resolution' => 'Issue fixed by clearing the cache',
        ]);

        $this->assertOk($r);
        $this->assertSame('resolved', $r['body']['data']['status']);
        $this->assertSame('Issue fixed by clearing the cache', $r['body']['data']['resolution']);
    }

    public function test_close_resolved_ticket(): void
    {
        $created = $this->req('POST', '/support', [
            'subject' => '[TEST] Close Ticket',
            'status'  => 'resolved',
        ]);
        $id = $created['body']['data']['id'];
        $this->cleanup('support', $id);

        $r = $this->req('PUT', "/support/{$id}", ['status' => 'closed']);
        $this->assertOk($r);
        $this->assertSame('closed', $r['body']['data']['status']);
    }

    public function test_update_priority(): void
    {
        $created = $this->req('POST', '/support', [
            'subject'  => '[TEST] Priority Ticket',
            'priority' => 'low',
        ]);
        $id = $created['body']['data']['id'];
        $this->cleanup('support', $id);

        $r = $this->req('PUT', "/support/{$id}", ['priority' => 'urgent']);
        $this->assertOk($r);
        $this->assertSame('urgent', $r['body']['data']['priority']);
    }

    public function test_update_nonexistent_returns_404(): void
    {
        $r = $this->req('PUT', '/support/999999', ['status' => 'closed']);
        $this->assertNotFound($r);
    }

    public function test_delete_removes_ticket(): void
    {
        $created = $this->req('POST', '/support', ['subject' => '[TEST] Delete Ticket']);
        $id = $created['body']['data']['id'];

        $del = $this->req('DELETE', "/support/{$id}");
        $this->assertOk($del);

        $check = $this->req('GET', "/support/{$id}");
        $this->assertNotFound($check);
    }

    public function test_delete_nonexistent_returns_404(): void
    {
        $r = $this->req('DELETE', '/support/999999');
        $this->assertNotFound($r);
    }

    public function test_response_contains_expected_fields(): void
    {
        $r = $this->req('POST', '/support', ['subject' => '[TEST] Fields Ticket']);
        $this->assertCreated($r);
        $data = $r['body']['data'];
        foreach (['id', 'subject', 'status', 'priority', 'lead_id', 'contact_id',
                  'account_id', 'assigned_to', 'description', 'resolution', 'created_at', 'updated_at'] as $field) {
            $this->assertArrayHasKey($field, $data, "Missing field: {$field}");
        }
        $this->cleanup('support', $data['id']);
    }
}
