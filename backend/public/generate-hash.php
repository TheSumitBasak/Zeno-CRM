<?php
// Utility script to generate a bcrypt hash for a password
// Usage: php generate-hash.php <password>
// Or access via browser: http://localhost:8080/generate-hash.php?password=Admin@123

$password = $_GET['password'] ?? ($argv[1] ?? 'Admin@123');
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
echo json_encode([
    'password' => $password,
    'hash' => $hash,
    'verify' => password_verify($password, $hash),
]);
