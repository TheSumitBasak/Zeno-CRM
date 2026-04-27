<?php

namespace App\Models;

use App\Config\Database;

class Meeting
{
    public static function findAll(): array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->query("
            SELECT m.*, IFNULL(JSON_ARRAYAGG(mc.contact_id), JSON_ARRAY()) as contact_ids
            FROM meetings m
            LEFT JOIN meeting_contacts mc ON m.id = mc.meeting_id
            GROUP BY m.id
            ORDER BY m.start_date DESC
        ");
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['contact_ids'] = json_decode($row['contact_ids'], true) ?? [];
            // Filter out nulls that JSON_ARRAYAGG produces when there are no rows
            $row['contact_ids'] = array_values(array_filter($row['contact_ids'], fn($v) => $v !== null));
        }
        return $rows;
    }

    public static function findById(int $id): ?array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare("
            SELECT m.*, IFNULL(JSON_ARRAYAGG(mc.contact_id), JSON_ARRAY()) as contact_ids
            FROM meetings m
            LEFT JOIN meeting_contacts mc ON m.id = mc.meeting_id
            WHERE m.id = ?
            GROUP BY m.id
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) return null;
        $row['contact_ids'] = json_decode($row['contact_ids'], true) ?? [];
        $row['contact_ids'] = array_values(array_filter($row['contact_ids'], fn($v) => $v !== null));
        return $row;
    }

    public static function setContacts(int $meetingId, array $contactIds): void
    {
        $pdo = Database::getInstance();
        // Delete existing attendees
        $stmt = $pdo->prepare("DELETE FROM meeting_contacts WHERE meeting_id = ?");
        $stmt->execute([$meetingId]);
        // Insert new attendees
        if (!empty($contactIds)) {
            $stmt = $pdo->prepare("INSERT INTO meeting_contacts (meeting_id, contact_id) VALUES (?, ?)");
            foreach ($contactIds as $contactId) {
                $cid = (int)$contactId;
                if ($cid > 0) {
                    $stmt->execute([$meetingId, $cid]);
                }
            }
        }
    }

    public static function create(array $data): ?array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare("
            INSERT INTO meetings (name, parent_type, parent_id, status, start_date, end_date, duration_hours, duration_minutes, description, meeting_link, assigned_to, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
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
            $data['meeting_link'] ?? null,
            $data['assigned_to'] ?: null,
        ]);
        $newId = (int)$pdo->lastInsertId();
        self::setContacts($newId, $data['contact_ids'] ?? []);
        return self::findById($newId);
    }

    public static function update(int $id, array $data): ?array
    {
        $pdo     = Database::getInstance();
        $allowed = ['name', 'parent_type', 'parent_id', 'status', 'start_date', 'end_date', 'duration_hours', 'duration_minutes', 'description', 'meeting_link', 'assigned_to'];
        $fields  = [];
        $values  = [];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = ?";
                $values[] = $data[$field] !== '' ? $data[$field] : null;
            }
        }
        if (!empty($fields)) {
            $fields[] = "updated_at = NOW()";
            $values[] = $id;
            $stmt = $pdo->prepare("UPDATE meetings SET " . implode(', ', $fields) . " WHERE id = ?");
            $stmt->execute($values);
        }
        if (array_key_exists('contact_ids', $data)) {
            self::setContacts($id, $data['contact_ids']);
        }
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
