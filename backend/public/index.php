<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\AccountController;
use App\Controllers\ContactController;
use App\Controllers\LeadController;
use App\Controllers\OpportunityController;
use App\Controllers\MeetingController;
use App\Controllers\TaskController;
use App\Controllers\UserController;
use App\Controllers\DashboardController;
use App\Helpers\Response;

// Autoloader
spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $base = __DIR__ . '/../src/';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = $base . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

// CORS Headers
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header("Access-Control-Allow-Origin: {$origin}");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Parse URI
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Strip /api prefix if present
$path = preg_replace('#^/api#', '', $requestUri);
$path = rtrim($path, '/') ?: '/';

// Route matching
$segments = array_values(array_filter(explode('/', $path)));
$resource = $segments[0] ?? '';
$id = isset($segments[1]) && is_numeric($segments[1]) ? (int) $segments[1] : null;
$subPath = $segments[1] ?? null;

try {
    switch ($resource) {
        case 'auth':
            $controller = new AuthController();
            if ($subPath === 'login' && $requestMethod === 'POST') {
                $controller->login();
            } elseif ($subPath === 'logout' && $requestMethod === 'POST') {
                $controller->logout();
            } else {
                Response::error('Route not found', 404);
            }
            break;

        case 'dashboard':
            $controller = new DashboardController();
            if ($requestMethod === 'GET')
                $controller->index();
            else
                Response::error('Method not allowed', 405);
            break;

        case 'accounts':
            $controller = new AccountController();
            if ($requestMethod === 'GET' && $id === null)
                $controller->index();
            elseif ($requestMethod === 'GET' && $id !== null)
                $controller->show($id);
            elseif ($requestMethod === 'POST' && $id === null)
                $controller->store();
            elseif ($requestMethod === 'PUT' && $id !== null)
                $controller->update($id);
            elseif ($requestMethod === 'DELETE' && $id !== null)
                $controller->destroy($id);
            else
                Response::error('Method not allowed', 405);
            break;

        case 'contacts':
            $controller = new ContactController();
            if ($requestMethod === 'GET' && $id === null)
                $controller->index();
            elseif ($requestMethod === 'GET' && $id !== null)
                $controller->show($id);
            elseif ($requestMethod === 'POST' && $id === null)
                $controller->store();
            elseif ($requestMethod === 'PUT' && $id !== null)
                $controller->update($id);
            elseif ($requestMethod === 'DELETE' && $id !== null)
                $controller->destroy($id);
            else
                Response::error('Method not allowed', 405);
            break;

        case 'leads':
            $controller = new LeadController();
            if ($requestMethod === 'GET' && $id === null)
                $controller->index();
            elseif ($requestMethod === 'GET' && $id !== null)
                $controller->show($id);
            elseif ($requestMethod === 'POST' && $id === null)
                $controller->store();
            elseif ($requestMethod === 'PUT' && $id !== null)
                $controller->update($id);
            elseif ($requestMethod === 'DELETE' && $id !== null)
                $controller->destroy($id);
            else
                Response::error('Method not allowed', 405);
            break;

        case 'opportunities':
            $controller = new OpportunityController();
            if ($requestMethod === 'GET' && $id === null)
                $controller->index();
            elseif ($requestMethod === 'GET' && $id !== null)
                $controller->show($id);
            elseif ($requestMethod === 'POST' && $id === null)
                $controller->store();
            elseif ($requestMethod === 'PUT' && $id !== null)
                $controller->update($id);
            elseif ($requestMethod === 'DELETE' && $id !== null)
                $controller->destroy($id);
            else
                Response::error('Method not allowed', 405);
            break;

        case 'meetings':
            $controller = new MeetingController();
            if ($requestMethod === 'GET' && $id === null)
                $controller->index();
            elseif ($requestMethod === 'GET' && $id !== null)
                $controller->show($id);
            elseif ($requestMethod === 'POST' && $id === null)
                $controller->store();
            elseif ($requestMethod === 'PUT' && $id !== null)
                $controller->update($id);
            elseif ($requestMethod === 'DELETE' && $id !== null)
                $controller->destroy($id);
            else
                Response::error('Method not allowed', 405);
            break;

        case 'tasks':
            $controller = new TaskController();
            if ($requestMethod === 'GET' && $id === null)
                $controller->index();
            elseif ($requestMethod === 'GET' && $id !== null)
                $controller->show($id);
            elseif ($requestMethod === 'POST' && $id === null)
                $controller->store();
            elseif ($requestMethod === 'PUT' && $id !== null)
                $controller->update($id);
            elseif ($requestMethod === 'DELETE' && $id !== null)
                $controller->destroy($id);
            else
                Response::error('Method not allowed', 405);
            break;

        case 'users':
            $controller = new UserController();
            if ($requestMethod === 'GET' && $id === null)
                $controller->index();
            elseif ($requestMethod === 'GET' && $id !== null)
                $controller->show($id);
            elseif ($requestMethod === 'POST' && $id === null)
                $controller->store();
            elseif ($requestMethod === 'PUT' && $id !== null)
                $controller->update($id);
            elseif ($requestMethod === 'DELETE' && $id !== null)
                $controller->destroy($id);
            else
                Response::error('Method not allowed', 405);
            break;

        default:
            Response::error('Route not found', 404);
    }
} catch (Throwable $e) {
    Response::error('Internal server error', 500);
}
