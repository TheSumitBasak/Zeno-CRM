<?php

namespace App\Models;

use App\Config\Database;

class Account
{
    public static function findAll(): array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->query("SELECT * FROM accounts ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }

    public static function findById(int $id): ?array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM accounts WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): ?array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare("
            INSERT INTO accounts (name, email, phone, industry, type, website, billing_address, shipping_address, description, created_by, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $data['name'],
            $data['email'] ?? null,
            $data['phone'] ?? null,
            $data['industry'] ?? null,
            $data['type'] ?? null,
            $data['website'] ?? null,
            $data['billing_address'] ?? null,
            $data['shipping_address'] ?? null,
            $data['description'] ?? null,
            $data['created_by'] ?? 1,
        ]);
        return self::findById((int)$pdo->lastInsertId());
    }

    public static function update(int $id, array $data): ?array
    {
        $pdo     = Database::getInstance();
        $allowed = ['name', 'email', 'phone', 'industry', 'type', 'website', 'billing_address', 'shipping_address', 'description'];
        $fields  = [];
        $values  = [];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = ?";
                $values[] = $data[$field];
            }
        }
        if (empty($fields)) return self::findById($id);
        $fields[] = "updated_at = NOW()";
        $values[] = $id;
        $stmt = $pdo->prepare("UPDATE accounts SET " . implode(', ', $fields) . " WHERE id = ?");
        $stmt->execute($values);
        return self::findById($id);
    }

    public static function delete(int $id): bool
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare("DELETE FROM accounts WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public static function count(): int
    {
        $pdo  = Database::getInstance();
        return (int)$pdo->query("SELECT COUNT(*) FROM accounts")->fetchColumn();
    }
}
