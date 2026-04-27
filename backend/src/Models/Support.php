<?php

namespace App\Models;

use App\Config\Database;

class Support
{
    public static function findAll(): array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->query("SELECT * FROM support_tickets ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }

    public static function findById(int $id): ?array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM support_tickets WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function findRecent(int $limit = 5): array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM support_tickets ORDER BY created_at DESC LIMIT ?");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    public static function create(array $data): ?array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare("
            INSERT INTO support_tickets (subject, status, priority, lead_id, contact_id, account_id, assigned_to, description, resolution, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $data['subject'],
            $data['status'] ?? 'open',
            $data['priority'] ?? 'medium',
            $data['lead_id'] ?: null,
            $data['contact_id'] ?: null,
            $data['account_id'] ?: null,
            $data['assigned_to'] ?: null,
            $data['description'] ?? null,
            $data['resolution'] ?? null,
        ]);
        return self::findById((int)$pdo->lastInsertId());
    }

    public static function update(int $id, array $data): ?array
    {
        $pdo     = Database::getInstance();
        $allowed = ['subject', 'status', 'priority', 'lead_id', 'contact_id', 'account_id', 'assigned_to', 'description', 'resolution'];
        $fields  = [];
        $values  = [];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = ?";
                $values[] = $data[$field] !== '' ? $data[$field] : null;
            }
        }
        if (empty($fields)) return self::findById($id);
        $fields[] = "updated_at = NOW()";
        $values[] = $id;
        $stmt = $pdo->prepare("UPDATE support_tickets SET " . implode(', ', $fields) . " WHERE id = ?");
        $stmt->execute($values);
        return self::findById($id);
    }

    public static function delete(int $id): bool
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare("DELETE FROM support_tickets WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public static function count(): int
    {
        $pdo  = Database::getInstance();
        return (int)$pdo->query("SELECT COUNT(*) FROM support_tickets WHERE status NOT IN ('resolved','closed')")->fetchColumn();
    }

    public static function countByStatus(): array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM support_tickets GROUP BY status");
        $rows = $stmt->fetchAll();
        $result = [];
        foreach ($rows as $row) {
            $result[$row['status']] = (int)$row['count'];
        }
        return $result;
    }
}
