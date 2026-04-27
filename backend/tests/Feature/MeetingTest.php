<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Tests for GET/POST/PUT/DELETE /meetings[/:id]
 * including contact attendee management via contact_ids.
 */
class MeetingTest extends TestCase
{
    public function test_list_requires_auth(): void
    {
        $r = $this->reqNoAuth('GET', '/meetings');
        $this->assertUnauthorized($r);
    }

    public function test_list_returns_array(): void
    {
        $r = $this->req('GET', '/meetings');
        $this->assertOk($r);
        $this->assertIsArray($r['body']['data']);
    }

    public function test_list_includes_contact_ids_field(): void
    {
        $r = $this->req('GET', '/meetings');
        $this->assertOk($r);
        if (!empty($r['body']['data'])) {
            $this->assertArrayHasKey('contact_ids', $r['body']['data'][0]);
            $this->assertIsArray($r['body']['data'][0]['contact_ids']);
        }
    }

    public function test_create_with_required_fields(): void
    {
        $r = $this->req('POST', '/meetings', ['name' => '[TEST] Basic Meeting']);

        $this->assertCreated($r);
        $data = $r['body']['data'];
        $this->assertSame('[TEST] Basic Meeting', $data['name']);
        $this->assertSame('planned', $data['status']); // default
        $this->assertIsArray($data['contact_ids']);
        $this->assertEmpty($data['contact_ids']);
        $this->cleanup('meetings', $data['id']);
    }

    public function test_create_with_all_fields(): void
    {
        $r = $this->req('POST', '/meetings', [
            'name'             => '[TEST] Full Meeting',
            'status'           => 'planned',
            'start_date'       => '2025-08-01 10:00:00',
            'end_date'         => '2025-08-01 11:30:00',
            'duration_hours'   => 1,
            'duration_minutes' => 30,
            'description'      => 'Test meeting',
            'meeting_link'     => 'https://meet.test/abc123',
            'parent_type'      => 'Account',
            'parent_id'        => 1,
            'assigned_to'      => 1,
        ]);

        $this->assertCreated($r);
        $data = $r['body']['data'];
        $this->assertSame('https://meet.test/abc123', $data['meeting_link']);
        $this->assertSame(30, (int) $data['duration_minutes']);
        $this->cleanup('meetings', $data['id']);
    }

    public function test_create_without_name_returns_400(): void
    {
        $r = $this->req('POST', '/meetings', ['status' => 'planned']);
        $this->assertBadRequest($r);
    }

    public function test_create_with_contact_attendees(): void
    {
        $r = $this->req('POST', '/meetings', [
            'name'        => '[TEST] Meeting with Attendees',
            'contact_ids' => [1, 2], // John Smith, Jane Doe from seed data
        ]);

        $this->assertCreated($r);
        $data = $r['body']['data'];
        $this->assertCount(2, $data['contact_ids']);
        $this->assertContains(1, $data['contact_ids']);
        $this->assertContains(2, $data['contact_ids']);
        $this->cleanup('meetings', $data['id']);
    }

    public function test_show_returns_meeting_with_contact_ids(): void
    {
        $created = $this->req('POST', '/meetings', [
            'name'        => '[TEST] Show Meeting',
            'contact_ids' => [1],
        ]);
        $id = $created['body']['data']['id'];
        $this->cleanup('meetings', $id);

        $r = $this->req('GET', "/meetings/{$id}");
        $this->assertOk($r);
        $this->assertSame($id, $r['body']['data']['id']);
        $this->assertArrayHasKey('contact_ids', $r['body']['data']);
        $this->assertContains(1, $r['body']['data']['contact_ids']);
    }

    public function test_show_nonexistent_returns_404(): void
    {
        $r = $this->req('GET', '/meetings/999999');
        $this->assertNotFound($r);
    }

    public function test_update_status_to_held(): void
    {
        $created = $this->req('POST', '/meetings', [
            'name'   => '[TEST] Status Update Meeting',
            'status' => 'planned',
        ]);
        $id = $created['body']['data']['id'];
        $this->cleanup('meetings', $id);

        $r = $this->req('PUT', "/meetings/{$id}", ['status' => 'held']);
        $this->assertOk($r);
        $this->assertSame('held', $r['body']['data']['status']);
    }

    public function test_update_replaces_contact_attendees(): void
    {
        $created = $this->req('POST', '/meetings', [
            'name'        => '[TEST] Attendee Replace Meeting',
            'contact_ids' => [1],
        ]);
        $id = $created['body']['data']['id'];
        $this->cleanup('meetings', $id);

        // Replace attendee list with contacts 2 and 3
        $r = $this->req('PUT', "/meetings/{$id}", ['contact_ids' => [2, 3]]);
        $this->assertOk($r);
        $this->assertCount(2, $r['body']['data']['contact_ids']);
        $this->assertNotContains(1, $r['body']['data']['contact_ids']);
        $this->assertContains(2, $r['body']['data']['contact_ids']);
    }

    public function test_update_clears_contact_attendees(): void
    {
        $created = $this->req('POST', '/meetings', [
            'name'        => '[TEST] Clear Attendees Meeting',
            'contact_ids' => [1, 2],
        ]);
        $id = $created['body']['data']['id'];
        $this->cleanup('meetings', $id);

        $r = $this->req('PUT', "/meetings/{$id}", ['contact_ids' => []]);
        $this->assertOk($r);
        $this->assertEmpty($r['body']['data']['contact_ids']);
    }

    public function test_update_meeting_link(): void
    {
        $created = $this->req('POST', '/meetings', ['name' => '[TEST] Link Meeting']);
        $id = $created['body']['data']['id'];
        $this->cleanup('meetings', $id);

        $r = $this->req('PUT', "/meetings/{$id}", [
            'meeting_link' => 'https://meet.example.com/xyz',
        ]);
        $this->assertOk($r);
        $this->assertSame('https://meet.example.com/xyz', $r['body']['data']['meeting_link']);
    }

    public function test_update_nonexistent_returns_404(): void
    {
        $r = $this->req('PUT', '/meetings/999999', ['status' => 'held']);
        $this->assertNotFound($r);
    }

    public function test_delete_removes_meeting(): void
    {
        $created = $this->req('POST', '/meetings', ['name' => '[TEST] Delete Meeting']);
        $id = $created['body']['data']['id'];

        $del = $this->req('DELETE', "/meetings/{$id}");
        $this->assertOk($del);

        $check = $this->req('GET', "/meetings/{$id}");
        $this->assertNotFound($check);
    }

    public function test_delete_nonexistent_returns_404(): void
    {
        $r = $this->req('DELETE', '/meetings/999999');
        $this->assertNotFound($r);
    }
}
