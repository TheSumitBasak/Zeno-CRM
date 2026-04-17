<?php

namespace App\Models;

use App\Config\Database;

class Meeting
{
    public static function findAll(): array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->query("SELECT * FROM meetings ORDER BY start_date DESC");
        return $stmt->fetchAll();
    }

    public static function findById(int $id): ?array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM meetings WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): ?array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare("
            INSERT INTO meetings (name, parent_type, parent_id, status, start_date, end_date, duration_hours, duration_minutes, description, assigned_to, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $data['name'],
            $data['parent_type'] ?? null,
            $data['parent_id'] ?: null,
            $data['status'] ?? 'planned',
            $data['start_date'] ?: null,
            $data['end_date'] ?: null,
            $data['duration_hours'] ?? 1,
            $data['duration_minutes'] ?? 0,
            $data['description'] ?? null,
            $data['assigned_to'] ?: null,
        ]);
        return self::findById((int)$pdo->lastInsertId());
    }

    public static function update(int $id, array $data): ?array
    {
        $pdo     = Database::getInstance();
        $allowed = ['name', 'parent_type', 'parent_id', 'status', 'start_date', 'end_date', 'duration_hours', 'duration_minutes', 'description', 'assigned_to'];
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
        $stmt = $pdo->prepare("UPDATE meetings SET " . implode(', ', $fields) . " WHERE id = ?");
        $stmt->execute($values);
        return self::findById($id);
    }

    public static function delete(int $id): bool
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare("DELETE FROM meetings WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
}
