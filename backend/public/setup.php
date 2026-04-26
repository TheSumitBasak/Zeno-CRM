<?php
/**
 * One-time setup script to fix password hashes in the database.
 * Run once after initial Docker setup if login fails.
 * Access: http://localhost:8080/setup.php
 */

spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $base   = __DIR__ . '/../src/';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) return;
    $relative = substr($class, strlen($prefix));
    $file     = $base . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($file)) require $file;
});

header('Content-Type: application/json');

try {
    $pdo = \App\Config\Database::getInstance();

    $hash = password_hash('Admin@123', PASSWORD_BCRYPT, ['cost' => 10]);

    $pdo->prepare("UPDATE users SET password = ? WHERE email = 'admin@zenocrm.com'")->execute([$hash]);
    $pdo->prepare("UPDATE users SET password = ? WHERE email != 'admin@zenocrm.com'")->execute([$hash]);

    echo json_encode([
        'success' => true,
        'message' => 'Passwords updated. All users now have password: Admin@123',
        'hash' => $hash,
        'verify' => password_verify('Admin@123', $hash),
    ]);
} catch (\Throwable $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
