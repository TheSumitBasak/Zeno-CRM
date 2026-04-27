<?php

namespace App\Config;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?? '127.0.0.1';
            $name = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?? 'zeno_crm';
            $user = $_ENV['DB_USER'] ?? getenv('DB_USER') ?? 'zeno_user';
            $pass = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?? 'zeno_pass';

            try {
                $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";
                self::$instance = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                http_response_code(500);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
                exit;
            }
        }

        return self::$instance;
    }
}
