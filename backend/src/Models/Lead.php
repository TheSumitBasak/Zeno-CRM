<?php

namespace App\Models;

use App\Config\Database;

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
        $allowed = ['first_name', 'last_name', 'email', 'phone', 'company', 'title', 'status', 'source', 'assigned_to', 'description'];
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
}
