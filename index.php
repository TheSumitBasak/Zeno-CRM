<?php
/**
 * Minimal backend-only auth entrypoint for ZenoCRM.
 */

require_once __DIR__ . '/bootstrap.php';

session_start();

const USERS_FILE = __DIR__ . '/data/users.json';

function ensureUsersFileExists(): void
{
    $directory = dirname(USERS_FILE);

    if (!is_dir($directory)) {
        mkdir($directory, 0777, true);
    }

    if (!file_exists(USERS_FILE)) {
        file_put_contents(USERS_FILE, json_encode([], JSON_PRETTY_PRINT));
    }
}

function readUsers(): array
{
    ensureUsersFileExists();

    $raw = file_get_contents(USERS_FILE);

    if ($raw === false || trim($raw) === '') {
        return [];
    }

    $decoded = json_decode($raw, true);

    return is_array($decoded) ? $decoded : [];
}

function writeUsers(array $users): bool
{
    return file_put_contents(
        USERS_FILE,
        json_encode(array_values($users), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    ) !== false;
}

function findUserByUserName(string $userName, array $users): ?array
{
    foreach ($users as $user) {
        if (($user['userName'] ?? '') === $userName) {
            return $user;
        }
    }

    return null;
}

function sendJson(int $status, array $payload): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

function getRequestData(): array
{
    $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));

    if (strpos($contentType, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        $decoded = json_decode((string) $raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    return $_POST;
}

function sanitizePublicUser(array $user): array
{
    return [
        'id' => $user['id'] ?? null,
        'userName' => $user['userName'] ?? null,
        'name' => $user['name'] ?? null,
        'entityType' => $user['entityType'] ?? 'User',
        'createdAt' => $user['createdAt'] ?? null,
    ];
}

function resolveUserEntityType(): string
{
    $userEntityClass = '\\Zeno\\Entities\\User';

    if (class_exists($userEntityClass) && defined($userEntityClass . '::ENTITY_TYPE')) {
        /** @var string $entityType */
        $entityType = constant($userEntityClass . '::ENTITY_TYPE');

        return $entityType;
    }

    return 'User';
}

function addUser(array $data): array
{
    $userName = trim((string) ($data['userName'] ?? ''));
    $name = trim((string) ($data['name'] ?? ''));
    $password = (string) ($data['password'] ?? '');

    if ($userName === '' || $name === '' || $password === '') {
        return [false, 'userName, name and password are required.', null];
    }

    $users = readUsers();

    if (findUserByUserName($userName, $users)) {
        return [false, 'User already exists.', null];
    }

    $user = [
        'id' => bin2hex(random_bytes(8)),
        'entityType' => resolveUserEntityType(),
        'userName' => $userName,
        'name' => $name,
        'passwordHash' => password_hash($password, PASSWORD_DEFAULT),
        'createdAt' => date('c'),
    ];

    $users[] = $user;

    if (!writeUsers($users)) {
        return [false, 'Unable to save user.', null];
    }

    return [true, 'User added successfully.', sanitizePublicUser($user)];
}

function loginUser(array $data): array
{
    $userName = trim((string) ($data['userName'] ?? ''));
    $password = (string) ($data['password'] ?? '');

    if ($userName === '' || $password === '') {
        return [false, 'userName and password are required.', null];
    }

    $users = readUsers();
    $user = findUserByUserName($userName, $users);

    if (!$user || !isset($user['passwordHash']) || !password_verify($password, $user['passwordHash'])) {
        return [false, 'Invalid username or password.', null];
    }

    $publicUser = sanitizePublicUser($user);
    $_SESSION['user'] = $publicUser;

    return [true, 'Login successful.', $publicUser];
}

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$action = (string) ($_GET['action'] ?? '');
$requestData = getRequestData();

if ($action === '') {
    sendJson(200, [
        'ok' => true,
        'service' => 'zenocrm-minimal-auth-backend',
        'endpoints' => [
            ['method' => 'POST', 'action' => 'add-user', 'body' => ['userName', 'name', 'password']],
            ['method' => 'POST', 'action' => 'login', 'body' => ['userName', 'password']],
            ['method' => 'POST', 'action' => 'logout', 'body' => []],
            ['method' => 'GET', 'action' => 'me', 'body' => []],
        ],
    ]);
}

if ($action === 'add-user') {
    if ($method !== 'POST') {
        sendJson(405, ['ok' => false, 'error' => 'Method not allowed. Use POST.']);
    }

    [$ok, $message, $user] = addUser($requestData);

    if (!$ok) {
        sendJson(400, ['ok' => false, 'error' => $message]);
    }

    sendJson(201, ['ok' => true, 'message' => $message, 'user' => $user]);
}

if ($action === 'login') {
    if ($method !== 'POST') {
        sendJson(405, ['ok' => false, 'error' => 'Method not allowed. Use POST.']);
    }

    [$ok, $message, $user] = loginUser($requestData);

    if (!$ok) {
        sendJson(401, ['ok' => false, 'error' => $message]);
    }

    sendJson(200, ['ok' => true, 'message' => $message, 'user' => $user]);
}

if ($action === 'logout') {
    if ($method !== 'POST') {
        sendJson(405, ['ok' => false, 'error' => 'Method not allowed. Use POST.']);
    }

    unset($_SESSION['user']);
    sendJson(200, ['ok' => true, 'message' => 'Logged out.']);
}

if ($action === 'me') {
    if (!isset($_SESSION['user'])) {
        sendJson(401, ['ok' => false, 'error' => 'Not authenticated.']);
    }

    sendJson(200, ['ok' => true, 'user' => $_SESSION['user']]);
}

sendJson(404, ['ok' => false, 'error' => 'Unknown action.']);
