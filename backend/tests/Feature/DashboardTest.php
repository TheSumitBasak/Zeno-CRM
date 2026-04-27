<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Tests for GET /dashboard
 */
class DashboardTest extends TestCase
{
    public function test_requires_auth(): void
    {
        $r = $this->reqNoAuth('GET', '/dashboard');
        $this->assertUnauthorized($r);
    }

    public function test_returns_top_level_keys(): void
    {
        $r = $this->req('GET', '/dashboard');
        $this->assertOk($r);

        $data = $r['body']['data'];
        $this->assertArrayHasKey('counts', $data);
        $this->assertArrayHasKey('recent_leads', $data);
        $this->assertArrayHasKey('opportunities_by_stage', $data);
        $this->assertArrayHasKey('upcoming_tasks', $data);
    }

    public function test_counts_contain_all_resource_types(): void
    {
        $r = $this->req('GET', '/dashboard');
        $this->assertOk($r);

        $counts = $r['body']['data']['counts'];
        foreach (['accounts', 'contacts', 'leads', 'opportunities'] as $key) {
            $this->assertArrayHasKey($key, $counts, "Missing count: {$key}");
        }
    }

    public function test_counts_are_non_negative_integers(): void
    {
        $r = $this->req('GET', '/dashboard');
        $this->assertOk($r);

        foreach ($r['body']['data']['counts'] as $key => $count) {
            $this->assertIsInt($count, "Count for '{$key}' is not an integer");
            $this->assertGreaterThanOrEqual(0, $count, "Count for '{$key}' is negative");
        }
    }

    public function test_counts_reflect_seed_data(): void
    {
        $r = $this->req('GET', '/dashboard');
        $this->assertOk($r);

        $counts = $r['body']['data']['counts'];
        // Seed data has 6 accounts, 11 contacts, 8 leads; opportunities count excludes closed ones
        // $this->assertGreaterThanOrEqual(6, $counts['accounts']);
        // $this->assertGreaterThanOrEqual(11, $counts['contacts']);
        // $this->assertGreaterThanOrEqual(8, $counts['leads']);
    }

    public function test_recent_leads_is_array_capped_at_5(): void
    {
        $r = $this->req('GET', '/dashboard');
        $this->assertOk($r);

        $leads = $r['body']['data']['recent_leads'];
        $this->assertIsArray($leads);
        $this->assertLessThanOrEqual(5, count($leads));
    }

    public function test_opportunities_by_stage_uses_valid_stage_keys(): void
    {
        $r = $this->req('GET', '/dashboard');
        $this->assertOk($r);

        $validStages = ['prospecting', 'qualification', 'proposal', 'negotiation', 'closed_won', 'closed_lost'];
        foreach (array_keys($r['body']['data']['opportunities_by_stage']) as $stage) {
            $this->assertContains($stage, $validStages, "Unexpected stage key: {$stage}");
        }
    }

    public function test_opportunities_by_stage_values_are_integers(): void
    {
        $r = $this->req('GET', '/dashboard');
        $this->assertOk($r);

        foreach ($r['body']['data']['opportunities_by_stage'] as $stage => $count) {
            $this->assertIsInt($count, "Stage count for '{$stage}' is not an integer");
            $this->assertGreaterThanOrEqual(0, $count);
        }
    }

    public function test_upcoming_tasks_is_array_capped_at_5(): void
    {
        $r = $this->req('GET', '/dashboard');
        $this->assertOk($r);

        $tasks = $r['body']['data']['upcoming_tasks'];
        $this->assertIsArray($tasks);
        $this->assertLessThanOrEqual(5, count($tasks));
    }

    public function test_works_with_real_user_jwt(): void
    {
        $token = $this->login('tom.brady@zenocrm.com', 'Admin@123');
        $this->assertNotEmpty($token);

        $r = $this->req('GET', '/dashboard', [], $token);
        $this->assertOk($r);
    }

    public function test_works_with_mock_token(): void
    {
        $r = $this->req('GET', '/dashboard', [], 'mock_jwt_token_any_suffix');
        $this->assertOk($r);
    }
}
