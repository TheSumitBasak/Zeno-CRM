<?php

declare(strict_types=1);

// Autoloader
spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $base   = __DIR__ . '/../src/';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file     = $base . str_replace('\\', '/', $relative) . '.php';
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

// Parse URI
$requestUri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Strip /api prefix if present
$path = preg_replace('#^/api#', '', $requestUri);
$path = rtrim($path, '/') ?: '/';

// Route matching
$segments = array_values(array_filter(explode('/', $path)));
$resource = $segments[0] ?? '';
$id       = isset($segments[1]) ? (int)$segments[1] : null;
$subPath  = $segments[1] ?? null;

try {
    switch ($resource) {
        case 'auth':
            $controller = new AuthController();
            match (true) {
                $subPath === 'login'  && $requestMethod === 'POST' => $controller->login(),
                $subPath === 'logout' && $requestMethod === 'POST' => $controller->logout(),
                default => Response::error('Route not found', 404),
            };
            break;

        case 'dashboard':
            $controller = new DashboardController();
            if ($requestMethod === 'GET') $controller->index();
            else Response::error('Method not allowed', 405);
            break;

        case 'accounts':
            $controller = new AccountController();
            match (true) {
                $requestMethod === 'GET'    && $id === null => $controller->index(),
                $requestMethod === 'GET'    && $id !== null => $controller->show($id),
                $requestMethod === 'POST'   && $id === null => $controller->store(),
                $requestMethod === 'PUT'    && $id !== null => $controller->update($id),
                $requestMethod === 'DELETE' && $id !== null => $controller->destroy($id),
                default => Response::error('Method not allowed', 405),
            };
            break;

        case 'contacts':
            $controller = new ContactController();
            match (true) {
                $requestMethod === 'GET'    && $id === null => $controller->index(),
                $requestMethod === 'GET'    && $id !== null => $controller->show($id),
                $requestMethod === 'POST'   && $id === null => $controller->store(),
                $requestMethod === 'PUT'    && $id !== null => $controller->update($id),
                $requestMethod === 'DELETE' && $id !== null => $controller->destroy($id),
                default => Response::error('Method not allowed', 405),
            };
            break;

        case 'leads':
            $controller = new LeadController();
            match (true) {
                $requestMethod === 'GET'    && $id === null => $controller->index(),
                $requestMethod === 'GET'    && $id !== null => $controller->show($id),
                $requestMethod === 'POST'   && $id === null => $controller->store(),
                $requestMethod === 'PUT'    && $id !== null => $controller->update($id),
                $requestMethod === 'DELETE' && $id !== null => $controller->destroy($id),
                default => Response::error('Method not allowed', 405),
            };
            break;

        case 'opportunities':
            $controller = new OpportunityController();
            match (true) {
                $requestMethod === 'GET'    && $id === null => $controller->index(),
                $requestMethod === 'GET'    && $id !== null => $controller->show($id),
                $requestMethod === 'POST'   && $id === null => $controller->store(),
                $requestMethod === 'PUT'    && $id !== null => $controller->update($id),
                $requestMethod === 'DELETE' && $id !== null => $controller->destroy($id),
                default => Response::error('Method not allowed', 405),
            };
            break;

        case 'meetings':
            $controller = new MeetingController();
            match (true) {
                $requestMethod === 'GET'    && $id === null => $controller->index(),
                $requestMethod === 'GET'    && $id !== null => $controller->show($id),
                $requestMethod === 'POST'   && $id === null => $controller->store(),
                $requestMethod === 'PUT'    && $id !== null => $controller->update($id),
                $requestMethod === 'DELETE' && $id !== null => $controller->destroy($id),
                default => Response::error('Method not allowed', 405),
            };
            break;

        case 'tasks':
            $controller = new TaskController();
            match (true) {
                $requestMethod === 'GET'    && $id === null => $controller->index(),
                $requestMethod === 'GET'    && $id !== null => $controller->show($id),
                $requestMethod === 'POST'   && $id === null => $controller->store(),
                $requestMethod === 'PUT'    && $id !== null => $controller->update($id),
                $requestMethod === 'DELETE' && $id !== null => $controller->destroy($id),
                default => Response::error('Method not allowed', 405),
            };
            break;

        case 'users':
            $controller = new UserController();
            match (true) {
                $requestMethod === 'GET'    && $id === null => $controller->index(),
                $requestMethod === 'GET'    && $id !== null => $controller->show($id),
                $requestMethod === 'POST'   && $id === null => $controller->store(),
                $requestMethod === 'PUT'    && $id !== null => $controller->update($id),
                $requestMethod === 'DELETE' && $id !== null => $controller->destroy($id),
                default => Response::error('Method not allowed', 405),
            };
            break;

        default:
            Response::error('Route not found', 404);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error',
        'error'   => $e->getMessage(),
    ]);
}
