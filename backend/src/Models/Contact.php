<?php

namespace App\Models;

use App\Config\Database;

class Contact
{
    public static function findAll(): array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->query("SELECT * FROM contacts ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }

    public static function findById(int $id): ?array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM contacts WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): ?array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare("
            INSERT INTO contacts (account_id, first_name, last_name, email, phone, title, department, address, birthday, description, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $data['account_id'] ?: null,
            $data['first_name'],
            $data['last_name'],
            $data['email'] ?? null,
            $data['phone'] ?? null,
            $data['title'] ?? null,
            $data['department'] ?? null,
            $data['address'] ?? null,
            $data['birthday'] ?: null,
            $data['description'] ?? null,
        ]);
        return self::findById((int)$pdo->lastInsertId());
    }

    public static function update(int $id, array $data): ?array
    {
        $pdo     = Database::getInstance();
        $allowed = ['account_id', 'first_name', 'last_name', 'email', 'phone', 'title', 'department', 'address', 'birthday', 'description'];
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
        $stmt = $pdo->prepare("UPDATE contacts SET " . implode(', ', $fields) . " WHERE id = ?");
        $stmt->execute($values);
        return self::findById($id);
    }

    public static function delete(int $id): bool
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare("DELETE FROM contacts WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public static function count(): int
    {
        $pdo  = Database::getInstance();
        return (int)$pdo->query("SELECT COUNT(*) FROM contacts")->fetchColumn();
    }
}
