<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Tests for GET/POST/PUT/DELETE /leads[/:id]
 * and POST /leads/:id/convert + /leads/:id/promote_support
 */
class LeadTest extends TestCase
{
    public function test_list_requires_auth(): void
    {
        $r = $this->reqNoAuth('GET', '/leads');
        $this->assertUnauthorized($r);
    }

    public function test_list_returns_array_with_seed_data(): void
    {
        $r = $this->req('GET', '/leads');
        $this->assertOk($r);
        $this->assertIsArray($r['body']['data']);
        $this->assertGreaterThan(0, count($r['body']['data']));
    }

    public function test_create_with_required_fields(): void
    {
        $r = $this->req('POST', '/leads', [
            'first_name' => 'Test',
            'last_name'  => 'Lead',
        ]);

        $this->assertCreated($r);
        $data = $r['body']['data'];
        $this->assertSame('Test', $data['first_name']);
        $this->assertSame('new', $data['status']); // default
        $this->cleanup('leads', $data['id']);
    }

    public function test_create_with_all_fields(): void
    {
        $r = $this->req('POST', '/leads', [
            'first_name'  => 'Full',
            'last_name'   => 'Lead',
            'email'       => 'full.lead@test.com',
            'phone'       => '+1-555-0010',
            'company'     => 'Test Corp',
            'title'       => 'Manager',
            'status'      => 'in_process',
            'source'      => 'Web',
            'description' => 'Test lead',
        ]);

        $this->assertCreated($r);
        $data = $r['body']['data'];
        $this->assertSame('Test Corp', $data['company']);
        $this->assertSame('in_process', $data['status']);
        $this->assertSame('Web', $data['source']);
        $this->cleanup('leads', $data['id']);
    }

    public function test_create_without_names_returns_400(): void
    {
        $r = $this->req('POST', '/leads', ['email' => 'noop@test.com']);
        $this->assertBadRequest($r);
    }

    public function test_show_returns_lead(): void
    {
        $created = $this->req('POST', '/leads', [
            'first_name' => 'Show',
            'last_name'  => 'Lead',
        ]);
        $id = $created['body']['data']['id'];
        $this->cleanup('leads', $id);

        $r = $this->req('GET', "/leads/{$id}");
        $this->assertOk($r);
        $this->assertSame($id, $r['body']['data']['id']);
    }

    public function test_show_nonexistent_returns_404(): void
    {
        $r = $this->req('GET', '/leads/999999');
        $this->assertNotFound($r);
    }

    public function test_update_status(): void
    {
        $created = $this->req('POST', '/leads', [
            'first_name' => 'Update',
            'last_name'  => 'Lead',
            'status'     => 'new',
        ]);
        $id = $created['body']['data']['id'];
        $this->cleanup('leads', $id);

        $r = $this->req('PUT', "/leads/{$id}", ['status' => 'assigned']);
        $this->assertOk($r);
        $this->assertSame('assigned', $r['body']['data']['status']);
    }

    public function test_update_nonexistent_returns_404(): void
    {
        $r = $this->req('PUT', '/leads/999999', ['status' => 'new']);
        $this->assertNotFound($r);
    }

    public function test_delete_removes_lead(): void
    {
        $created = $this->req('POST', '/leads', [
            'first_name' => 'Delete',
            'last_name'  => 'Lead',
        ]);
        $id = $created['body']['data']['id'];

        $del = $this->req('DELETE', "/leads/{$id}");
        $this->assertOk($del);

        $check = $this->req('GET', "/leads/{$id}");
        $this->assertNotFound($check);
    }

    public function test_delete_nonexistent_returns_404(): void
    {
        $r = $this->req('DELETE', '/leads/999999');
        $this->assertNotFound($r);
    }

    public function test_convert_creates_contact_and_account(): void
    {
        $created = $this->req('POST', '/leads', [
            'first_name' => 'Convert',
            'last_name'  => 'Lead',
            'company'    => 'Convert Corp',
            'email'      => 'convert.lead@test.com',
            'phone'      => '+1-555-0020',
        ]);
        $leadId = $created['body']['data']['id'];

        $r = $this->req('POST', "/leads/{$leadId}/convert", [
            'create_account'     => true,
            'account_name'       => '[TEST] Convert Corp',
            'create_opportunity' => false,
        ]);

        $this->assertOk($r);
        $result = $r['body']['data'];
        $this->assertNotNull($result['contact_id']);
        $this->assertNotNull($result['account_id']);
        $this->assertSame('converted', $result['lead']['status']);

        // Cleanup in dependency order
        if ($result['contact_id']) {
            $this->cleanup('contacts', $result['contact_id']);
        }
        if ($result['account_id']) {
            $this->cleanup('accounts', $result['account_id']);
        }
        $this->cleanup('leads', $leadId);
    }

    public function test_convert_with_opportunity_creates_three_records(): void
    {
        $created = $this->req('POST', '/leads', [
            'first_name' => 'FullConvert',
            'last_name'  => 'Lead',
            'company'    => 'FullConvert Corp',
            'source'     => 'Referral',
        ]);
        $leadId = $created['body']['data']['id'];

        $r = $this->req('POST', "/leads/{$leadId}/convert", [
            'create_account'     => true,
            'account_name'       => '[TEST] FullConvert Corp',
            'create_opportunity' => true,
            'opportunity_name'   => '[TEST] FullConvert Corp Deal',
            'opportunity_stage'  => 'prospecting',
        ]);

        $this->assertOk($r);
        $result = $r['body']['data'];
        $this->assertNotNull($result['contact_id']);
        $this->assertNotNull($result['account_id']);
        $this->assertNotNull($result['opportunity_id']);
        $this->assertSame('converted', $result['lead']['status']);

        if ($result['opportunity_id']) {
            $this->cleanup('opportunities', $result['opportunity_id']);
        }
        if ($result['contact_id']) {
            $this->cleanup('contacts', $result['contact_id']);
        }
        if ($result['account_id']) {
            $this->cleanup('accounts', $result['account_id']);
        }
        $this->cleanup('leads', $leadId);
    }

    public function test_convert_existing_account_without_creating_new(): void
    {
        $created = $this->req('POST', '/leads', [
            'first_name' => 'UseExisting',
            'last_name'  => 'Lead',
        ]);
        $leadId = $created['body']['data']['id'];

        // Use existing seed account (id=1, Acme Corporation)
        $r = $this->req('POST', "/leads/{$leadId}/convert", [
            'account_id' => 1,
        ]);

        $this->assertOk($r);
        $result = $r['body']['data'];
        $this->assertSame(1, $result['account_id']);
        $this->assertNotNull($result['contact_id']);

        if ($result['contact_id']) {
            $this->cleanup('contacts', $result['contact_id']);
        }
        $this->cleanup('leads', $leadId);
    }

    public function test_convert_already_converted_lead_returns_400(): void
    {
        // Lead ID 4 (Pam Beesly) is status='converted' in seed data
        $r = $this->req('POST', '/leads/4/convert', []);
        $this->assertBadRequest($r);
    }

    public function test_convert_nonexistent_lead_returns_404(): void
    {
        $r = $this->req('POST', '/leads/999999/convert', []);
        $this->assertNotFound($r);
    }

    public function test_promote_support_creates_ticket(): void
    {
        $created = $this->req('POST', '/leads', [
            'first_name' => 'Support',
            'last_name'  => 'Lead',
            'company'    => 'Support Corp',
        ]);
        $leadId = $created['body']['data']['id'];

        $r = $this->req('POST', "/leads/{$leadId}/promote_support", [
            'subject'  => '[TEST] Support Request from Lead',
            'priority' => 'high',
        ]);

        $this->assertOk($r);
        $result = $r['body']['data'];
        $this->assertArrayHasKey('ticket_id', $result);
        $this->assertNotNull($result['ticket_id']);
        $this->assertNotNull($result['ticket']);
        $this->assertSame('[TEST] Support Request from Lead', $result['ticket']['subject']);

        $this->cleanup('support', $result['ticket_id']);
        $this->cleanup('leads', $leadId);
    }

    public function test_promote_support_uses_company_as_default_subject(): void
    {
        $created = $this->req('POST', '/leads', [
            'first_name' => 'AutoSubject',
            'last_name'  => 'Lead',
            'company'    => 'AutoSubject Corp',
        ]);
        $leadId = $created['body']['data']['id'];

        $r = $this->req('POST', "/leads/{$leadId}/promote_support", []);

        $this->assertOk($r);
        $result = $r['body']['data'];
        $this->assertStringContainsString('AutoSubject Corp', $result['ticket']['subject']);

        $this->cleanup('support', $result['ticket_id']);
        $this->cleanup('leads', $leadId);
    }

    public function test_promote_support_nonexistent_lead_returns_404(): void
    {
        $r = $this->req('POST', '/leads/999999/promote_support', []);
        $this->assertNotFound($r);
    }
}
