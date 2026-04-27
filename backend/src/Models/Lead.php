<?php

namespace App\Models;

use App\Config\Database;
use App\Models\Support;

class Lead
{
    public static function findAll(): array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->query("SELECT * FROM leads ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }

    public static function findById(int $id): ?array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM leads WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function findRecent(int $limit = 5): array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM leads ORDER BY created_at DESC LIMIT ?");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    public static function create(array $data): ?array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare("
            INSERT INTO leads (first_name, last_name, email, phone, company, title, status, source, assigned_to, description, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $data['first_name'],
            $data['last_name'],
            $data['email'] ?? null,
            $data['phone'] ?? null,
            $data['company'] ?? null,
            $data['title'] ?? null,
            $data['status'] ?? 'new',
            $data['source'] ?? null,
            $data['assigned_to'] ?: null,
            $data['description'] ?? null,
        ]);
        return self::findById((int)$pdo->lastInsertId());
    }

    public static function update(int $id, array $data): ?array
    {
        $pdo     = Database::getInstance();
        $allowed = [
            'first_name', 'last_name', 'email', 'phone', 'company', 'title',
            'status', 'source', 'assigned_to', 'description',
            'converted_contact_id', 'converted_account_id', 'converted_opportunity_id', 'converted_support_id', 'converted_at',
        ];
        $fields  = [];
        $values  = [];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = ?";
                $values[] = $data[$field] ?: null;
            }
        }
        if (empty($fields)) return self::findById($id);
        $fields[] = "updated_at = NOW()";
        $values[] = $id;
        $stmt = $pdo->prepare("UPDATE leads SET " . implode(', ', $fields) . " WHERE id = ?");
        $stmt->execute($values);
        return self::findById($id);
    }

    public static function delete(int $id): bool
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare("DELETE FROM leads WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public static function count(): int
    {
        $pdo  = Database::getInstance();
        return (int)$pdo->query("SELECT COUNT(*) FROM leads")->fetchColumn();
    }

    public static function convert(int $leadId, array $data): array
    {
        $pdo = Database::getInstance();

        // 1. Fetch the lead
        $lead = self::findById($leadId);
        if (!$lead) {
            throw new \RuntimeException("Lead not found: {$leadId}");
        }

        // 2. Resolve or create Account
        $accountId = null;
        if (!empty($data['create_account'])) {
            $accountName = !empty($data['account_name']) ? $data['account_name'] : ($lead['company'] ?? 'Unknown Company');
            $stmt = $pdo->prepare("
                INSERT INTO accounts (name, phone, created_at, updated_at)
                VALUES (?, ?, NOW(), NOW())
            ");
            $stmt->execute([$accountName, $lead['phone'] ?? null]);
            $accountId = (int)$pdo->lastInsertId();
        } elseif (!empty($data['account_id'])) {
            $accountId = (int)$data['account_id'];
        }

        // 3. Create Contact from lead fields
        $stmt = $pdo->prepare("
            INSERT INTO contacts (account_id, first_name, last_name, email, phone, title, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $accountId,
            $lead['first_name'],
            $lead['last_name'],
            $lead['email'] ?? null,
            $lead['phone'] ?? null,
            $lead['title'] ?? null,
        ]);
        $contactId = (int)$pdo->lastInsertId();

        // 4. Optionally create Opportunity
        $oppId = null;
        if (!empty($data['create_opportunity'])) {
            $oppName = !empty($data['opportunity_name'])
                ? $data['opportunity_name']
                : (($lead['company'] ?? 'Deal') . ' - Deal');
            $stage = $data['opportunity_stage'] ?? 'prospecting';
            $stmt = $pdo->prepare("
                INSERT INTO opportunities (name, account_id, contact_id, lead_id, stage, lead_source, assigned_to, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([
                $oppName,
                $accountId,
                $contactId,
                $leadId,
                $stage,
                $lead['source'] ?? null,
                $lead['assigned_to'] ?? null,
            ]);
            $oppId = (int)$pdo->lastInsertId();
        }

        // 5. Update lead to converted
        $stmt = $pdo->prepare("
            UPDATE leads
            SET status = 'converted',
                converted_contact_id = ?,
                converted_account_id = ?,
                converted_opportunity_id = ?,
                converted_at = NOW(),
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$contactId, $accountId, $oppId, $leadId]);

        // 6. Return result
        $updatedLead = self::findById($leadId);

        return [
            'lead'           => $updatedLead,
            'contact_id'     => $contactId,
            'account_id'     => $accountId,
            'opportunity_id' => $oppId,
        ];
    }

    public static function promoteSupport(int $leadId, array $data): array
    {
        $pdo  = Database::getInstance();
        $lead = self::findById($leadId);
        if (!$lead) {
            throw new \RuntimeException("Lead not found: {$leadId}");
        }

        $subject = !empty($data['subject'])
            ? $data['subject']
            : (($lead['company'] ?? $lead['first_name']) . ' - Support Request');

        $ticket = Support::create([
            'subject'     => $subject,
            'status'      => $data['status'] ?? 'open',
            'priority'    => $data['priority'] ?? 'medium',
            'lead_id'     => $leadId,
            'contact_id'  => $data['contact_id'] ?: null,
            'account_id'  => $data['account_id'] ?: null,
            'assigned_to' => $data['assigned_to'] ?: ($lead['assigned_to'] ?? null),
            'description' => $data['description'] ?? $lead['description'] ?? null,
        ]);

        // Track support ticket on lead without overwriting opportunity conversion
        $stmt = $pdo->prepare("
            UPDATE leads SET converted_support_id = ?, updated_at = NOW() WHERE id = ?
        ");
        $stmt->execute([$ticket['id'], $leadId]);

        return [
            'lead'       => self::findById($leadId),
            'ticket_id'  => $ticket['id'],
            'ticket'     => $ticket,
        ];
    }
}
