<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class User
{
    public static function findAll(): array
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->query("SELECT id, name, email, role, team, is_active, last_login, created_at FROM users ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }

    public static function findById(int $id): ?array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare("SELECT id, name, email, role, team, is_active, last_login, created_at FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function findByEmail(string $email): ?array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): ?array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, password, role, team, is_active, created_at)
            VALUES (?, ?, ?, ?, ?, 1, NOW())
        ");
        $stmt->execute([
            $data['name'],
            $data['email'],
            password_hash($data['password'], PASSWORD_BCRYPT),
            $data['role'] ?? 'user',
            $data['team'] ?? null,
        ]);
        return self::findById((int)$pdo->lastInsertId());
    }

    public static function update(int $id, array $data): ?array
    {
        $pdo    = Database::getInstance();
        $fields = [];
        $values = [];

        $allowed = ['name', 'email', 'role', 'team', 'is_active'];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = ?";
                $values[] = $data[$field];
            }
        }

        if (!empty($data['password'])) {
            $fields[] = "password = ?";
            $values[] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        if (empty($fields)) return self::findById($id);

        $values[] = $id;
        $sql  = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
        return self::findById($id);
    }

    public static function delete(int $id): bool
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public static function updateLastLogin(int $id): void
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$id]);
    }
}
