<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Tests for GET/POST/PUT/DELETE /opportunities[/:id]
 */
class OpportunityTest extends TestCase
{
    public function test_list_requires_auth(): void
    {
        $r = $this->reqNoAuth('GET', '/opportunities');
        $this->assertUnauthorized($r);
    }

    public function test_list_returns_array_with_seed_data(): void
    {
        $r = $this->req('GET', '/opportunities');
        $this->assertOk($r);
        $this->assertIsArray($r['body']['data']);
        $this->assertGreaterThan(0, count($r['body']['data']));
    }

    public function test_create_with_required_fields(): void
    {
        $r = $this->req('POST', '/opportunities', ['name' => '[TEST] Basic Opportunity']);

        $this->assertCreated($r);
        $data = $r['body']['data'];
        $this->assertSame('[TEST] Basic Opportunity', $data['name']);
        $this->assertSame('prospecting', $data['stage']); // default
        $this->cleanup('opportunities', $data['id']);
    }

    public function test_create_with_all_fields(): void
    {
        $r = $this->req('POST', '/opportunities', [
            'name'        => '[TEST] Full Opportunity',
            'account_id'  => 1,
            'contact_id'  => 1,
            'stage'       => 'qualification',
            'amount'      => 75000.00,
            'probability' => 40,
            'close_date'  => '2025-12-31',
            'lead_source' => 'Web',
            'description' => 'Full opportunity record',
        ]);

        $this->assertCreated($r);
        $data = $r['body']['data'];
        $this->assertSame('qualification', $data['stage']);
        $this->assertSame('75000.00', $data['amount']);
        $this->assertSame(40, (int) $data['probability']);
        $this->assertSame('2025-12-31', $data['close_date']);
        $this->cleanup('opportunities', $data['id']);
    }

    public function test_create_without_name_returns_400(): void
    {
        $r = $this->req('POST', '/opportunities', ['stage' => 'prospecting']);
        $this->assertBadRequest($r);
    }

    public function test_create_linked_to_lead(): void
    {
        $r = $this->req('POST', '/opportunities', [
            'name'    => '[TEST] Lead-Linked Opportunity',
            'lead_id' => 1, // Michael Scott from seed data
        ]);

        $this->assertCreated($r);
        $this->assertSame(1, (int) $r['body']['data']['lead_id']);
        $this->cleanup('opportunities', $r['body']['data']['id']);
    }

    public function test_show_returns_opportunity(): void
    {
        $created = $this->req('POST', '/opportunities', ['name' => '[TEST] Show Opportunity']);
        $id = $created['body']['data']['id'];
        $this->cleanup('opportunities', $id);

        $r = $this->req('GET', "/opportunities/{$id}");
        $this->assertOk($r);
        $this->assertSame($id, $r['body']['data']['id']);
    }

    public function test_show_nonexistent_returns_404(): void
    {
        $r = $this->req('GET', '/opportunities/999999');
        $this->assertNotFound($r);
    }

    public function test_update_stage_advances_pipeline(): void
    {
        $created = $this->req('POST', '/opportunities', [
            'name'  => '[TEST] Pipeline Opportunity',
            'stage' => 'prospecting',
        ]);
        $id = $created['body']['data']['id'];
        $this->cleanup('opportunities', $id);

        $r = $this->req('PUT', "/opportunities/{$id}", [
            'stage'       => 'proposal',
            'probability' => 60,
        ]);

        $this->assertOk($r);
        $this->assertSame('proposal', $r['body']['data']['stage']);
        $this->assertSame(60, (int) $r['body']['data']['probability']);
    }

    public function test_update_close_to_won(): void
    {
        $created = $this->req('POST', '/opportunities', [
            'name'  => '[TEST] Won Opportunity',
            'stage' => 'negotiation',
        ]);
        $id = $created['body']['data']['id'];
        $this->cleanup('opportunities', $id);

        $r = $this->req('PUT', "/opportunities/{$id}", [
            'stage'       => 'closed_won',
            'probability' => 100,
        ]);

        $this->assertOk($r);
        $this->assertSame('closed_won', $r['body']['data']['stage']);
    }

    public function test_update_nonexistent_returns_404(): void
    {
        $r = $this->req('PUT', '/opportunities/999999', ['name' => 'Nope']);
        $this->assertNotFound($r);
    }

    public function test_delete_removes_opportunity(): void
    {
        $created = $this->req('POST', '/opportunities', ['name' => '[TEST] Delete Opportunity']);
        $id = $created['body']['data']['id'];

        $del = $this->req('DELETE', "/opportunities/{$id}");
        $this->assertOk($del);

        $check = $this->req('GET', "/opportunities/{$id}");
        $this->assertNotFound($check);
    }

    public function test_delete_nonexistent_returns_404(): void
    {
        $r = $this->req('DELETE', '/opportunities/999999');
        $this->assertNotFound($r);
    }

    public function test_response_contains_expected_fields(): void
    {
        $r = $this->req('POST', '/opportunities', ['name' => '[TEST] Fields Check']);
        $this->assertCreated($r);
        $data = $r['body']['data'];
        foreach (['id', 'name', 'account_id', 'contact_id', 'lead_id', 'stage',
                  'amount', 'probability', 'close_date', 'lead_source', 'description',
                  'assigned_to', 'created_at', 'updated_at'] as $field) {
            $this->assertArrayHasKey($field, $data, "Missing field: {$field}");
        }
        $this->cleanup('opportunities', $data['id']);
    }
}
