<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Tests for GET/POST/PUT/DELETE /tasks[/:id]
 */
class TaskTest extends TestCase
{
    public function test_list_requires_auth(): void
    {
        $r = $this->reqNoAuth('GET', '/tasks');
        $this->assertUnauthorized($r);
    }

    public function test_list_returns_array_with_seed_data(): void
    {
        $r = $this->req('GET', '/tasks');
        $this->assertOk($r);
        $this->assertIsArray($r['body']['data']);
        $this->assertGreaterThan(0, count($r['body']['data']));
    }

    public function test_create_with_required_fields(): void
    {
        $r = $this->req('POST', '/tasks', ['name' => '[TEST] Basic Task']);

        $this->assertCreated($r);
        $data = $r['body']['data'];
        $this->assertSame('[TEST] Basic Task', $data['name']);
        $this->assertSame('not_started', $data['status']);  // default
        $this->assertSame('medium', $data['priority']);     // default
        $this->cleanup('tasks', $data['id']);
    }

    public function test_create_with_all_fields(): void
    {
        $r = $this->req('POST', '/tasks', [
            'name'        => '[TEST] Full Task',
            'status'      => 'in_progress',
            'priority'    => 'urgent',
            'start_date'  => '2025-07-01',
            'due_date'    => '2025-07-15',
            'parent_type' => 'Opportunity',
            'parent_id'   => 1,
            'contact_id'  => 1,
            'assigned_to' => 1,
            'description' => 'Full task record',
        ]);

        $this->assertCreated($r);
        $data = $r['body']['data'];
        $this->assertSame('in_progress', $data['status']);
        $this->assertSame('urgent', $data['priority']);
        $this->assertSame('Opportunity', $data['parent_type']);
        $this->assertSame(1, (int) $data['parent_id']);
        $this->cleanup('tasks', $data['id']);
    }

    public function test_create_without_name_returns_400(): void
    {
        $r = $this->req('POST', '/tasks', ['priority' => 'high']);
        $this->assertBadRequest($r);
    }

    public function test_create_with_lead_parent(): void
    {
        $r = $this->req('POST', '/tasks', [
            'name'        => '[TEST] Lead Task',
            'parent_type' => 'Lead',
            'parent_id'   => 1,
        ]);

        $this->assertCreated($r);
        $this->assertSame('Lead', $r['body']['data']['parent_type']);
        $this->cleanup('tasks', $r['body']['data']['id']);
    }

    public function test_show_returns_task(): void
    {
        $created = $this->req('POST', '/tasks', ['name' => '[TEST] Show Task']);
        $id = $created['body']['data']['id'];
        $this->cleanup('tasks', $id);

        $r = $this->req('GET', "/tasks/{$id}");
        $this->assertOk($r);
        $this->assertSame($id, $r['body']['data']['id']);
    }

    public function test_show_nonexistent_returns_404(): void
    {
        $r = $this->req('GET', '/tasks/999999');
        $this->assertNotFound($r);
    }

    public function test_update_status_to_in_progress(): void
    {
        $created = $this->req('POST', '/tasks', [
            'name'   => '[TEST] Progress Task',
            'status' => 'not_started',
        ]);
        $id = $created['body']['data']['id'];
        $this->cleanup('tasks', $id);

        $r = $this->req('PUT', "/tasks/{$id}", ['status' => 'in_progress']);
        $this->assertOk($r);
        $this->assertSame('in_progress', $r['body']['data']['status']);
    }

    public function test_update_status_to_completed(): void
    {
        $created = $this->req('POST', '/tasks', [
            'name'   => '[TEST] Complete Task',
            'status' => 'in_progress',
        ]);
        $id = $created['body']['data']['id'];
        $this->cleanup('tasks', $id);

        $r = $this->req('PUT', "/tasks/{$id}", ['status' => 'completed']);
        $this->assertOk($r);
        $this->assertSame('completed', $r['body']['data']['status']);
    }

    public function test_update_priority_and_due_date(): void
    {
        $created = $this->req('POST', '/tasks', [
            'name'     => '[TEST] Priority Task',
            'priority' => 'low',
        ]);
        $id = $created['body']['data']['id'];
        $this->cleanup('tasks', $id);

        $r = $this->req('PUT', "/tasks/{$id}", [
            'priority' => 'urgent',
            'due_date' => '2025-09-30',
        ]);

        $this->assertOk($r);
        $this->assertSame('urgent', $r['body']['data']['priority']);
        $this->assertSame('2025-09-30', $r['body']['data']['due_date']);
    }

    public function test_update_nonexistent_returns_404(): void
    {
        $r = $this->req('PUT', '/tasks/999999', ['status' => 'completed']);
        $this->assertNotFound($r);
    }

    public function test_delete_removes_task(): void
    {
        $created = $this->req('POST', '/tasks', ['name' => '[TEST] Delete Task']);
        $id = $created['body']['data']['id'];

        $del = $this->req('DELETE', "/tasks/{$id}");
        $this->assertOk($del);

        $check = $this->req('GET', "/tasks/{$id}");
        $this->assertNotFound($check);
    }

    public function test_delete_nonexistent_returns_404(): void
    {
        $r = $this->req('DELETE', '/tasks/999999');
        $this->assertNotFound($r);
    }

    public function test_response_contains_expected_fields(): void
    {
        $r = $this->req('POST', '/tasks', ['name' => '[TEST] Fields Task']);
        $this->assertCreated($r);
        $data = $r['body']['data'];
        foreach (['id', 'name', 'status', 'priority', 'start_date', 'due_date',
                  'parent_type', 'parent_id', 'contact_id', 'assigned_to',
                  'description', 'created_at', 'updated_at'] as $field) {
            $this->assertArrayHasKey($field, $data, "Missing field: {$field}");
        }
        $this->cleanup('tasks', $data['id']);
    }
}
