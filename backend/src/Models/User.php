<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class User
{
    private static function decodePermissions(array $row): array
    {
        if (isset($row['page_permissions']) && is_string($row['page_permissions'])) {
            $row['page_permissions'] = json_decode($row['page_permissions'], true) ?? [];
        } elseif (!isset($row['page_permissions'])) {
            $row['page_permissions'] = [];
        }
        return $row;
    }

    public static function findAll(): array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->query("SELECT id, name, email, role, team, is_active, page_permissions, last_login, created_at FROM users ORDER BY created_at DESC");
        return array_map([self::class, 'decodePermissions'], $stmt->fetchAll());
    }

    public static function findById(int $id): ?array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare("SELECT id, name, email, role, team, is_active, page_permissions, last_login, created_at FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $row  = $stmt->fetch();
        return $row ? self::decodePermissions($row) : null;
    }

    public static function findByEmail(string $email): ?array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $row  = $stmt->fetch();
        return $row ? self::decodePermissions($row) : null;
    }

    public static function create(array $data): ?array
    {
        $pdo         = Database::getInstance();
        $permissions = isset($data['page_permissions']) ? json_encode($data['page_permissions']) : null;
        $stmt        = $pdo->prepare("
            INSERT INTO users (name, email, password, role, team, is_active, page_permissions, created_at)
            VALUES (?, ?, ?, ?, ?, 1, ?, NOW())
        ");
        $stmt->execute([
            $data['name'],
            $data['email'],
            password_hash($data['password'], PASSWORD_BCRYPT),
            $data['role'] ?? 'user',
            $data['team'] ?? null,
            $permissions,
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

        if (array_key_exists('page_permissions', $data)) {
            $fields[] = "page_permissions = ?";
            $values[] = is_array($data['page_permissions']) ? json_encode($data['page_permissions']) : null;
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
