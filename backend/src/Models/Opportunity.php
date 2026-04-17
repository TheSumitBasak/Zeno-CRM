<?php

namespace App\Models;

use App\Config\Database;

class Opportunity
{
    public static function findAll(): array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->query("SELECT * FROM opportunities ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }

    public static function findById(int $id): ?array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM opportunities WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function countByStage(): array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->query("SELECT stage, COUNT(*) as count FROM opportunities GROUP BY stage");
        $rows = $stmt->fetchAll();
        $result = [];
        foreach ($rows as $row) {
            $result[$row['stage']] = (int)$row['count'];
        }
        return $result;
    }

    public static function create(array $data): ?array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare("
            INSERT INTO opportunities (name, account_id, contact_id, stage, amount, probability, close_date, lead_source, description, assigned_to, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $data['name'],
            $data['account_id'] ?: null,
            $data['contact_id'] ?: null,
            $data['stage'] ?? 'prospecting',
            $data['amount'] ?: null,
            $data['probability'] ?? 0,
            $data['close_date'] ?: null,
            $data['lead_source'] ?? null,
            $data['description'] ?? null,
            $data['assigned_to'] ?: null,
        ]);
        return self::findById((int)$pdo->lastInsertId());
    }

    public static function update(int $id, array $data): ?array
    {
        $pdo     = Database::getInstance();
        $allowed = ['name', 'account_id', 'contact_id', 'stage', 'amount', 'probability', 'close_date', 'lead_source', 'description', 'assigned_to'];
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
        $stmt = $pdo->prepare("UPDATE opportunities SET " . implode(', ', $fields) . " WHERE id = ?");
        $stmt->execute($values);
        return self::findById($id);
    }

    public static function delete(int $id): bool
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare("DELETE FROM opportunities WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public static function count(): int
    {
        $pdo  = Database::getInstance();
        return (int)$pdo->query("SELECT COUNT(*) FROM opportunities WHERE stage NOT IN ('closed_won','closed_lost')")->fetchColumn();
    }
}
