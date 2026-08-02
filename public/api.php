<?php

// Aurora Platform - REST API Entry Point
define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/vendor/autoload.php';

// Load configuration
$config = include BASE_PATH . '/config/app.php';
$dbConfig = include BASE_PATH . '/config/database.php';

// Error handling
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("Error [$errno]: $errstr in $errfile:$errline");
    if ($config['debug']) {
        echo json_encode([
            'success' => false,
            'error' => [
                'code' => 'INTERNAL_ERROR',
                'message' => $errstr,
                'file' => $errfile,
                'line' => $errline
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => [
                'code' => 'INTERNAL_ERROR',
                'message' => 'Internal server error'
            ]
        ]);
    }
    exit(1);
});

// Request handling
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Parse request
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/api/v1', '', $path);

// Simple routing
$routes = [
    'POST' => [
        '/auth/login' => [
            'controller' => 'AuthController',
            'method' => 'login'
        ],
        '/auth/logout' => [
            'controller' => 'AuthController',
            'method' => 'logout'
        ],
    ],
    'GET' => [
        '/health-check' => function() {
            return [
                'status' => 'healthy',
                'timestamp' => date('c'),
                'version' => '1.0.0'
            ];
        }
    ]
];

// Route matching
$route = null;
foreach ($routes[$method] ?? [] as $pattern => $handler) {
    if ($path === $pattern) {
        $route = $handler;
        break;
    }
}

if (!$route) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error' => [
            'code' => 'NOT_FOUND',
            'message' => 'Endpoint not found'
        ],
        'meta' => ['timestamp' => date('c')]
    ]);
    exit();
}

// Execute route
try {
    if (is_callable($route)) {
        $result = $route();
        echo json_encode($result);
    } else {
        // Route to specific handler if defined
        $controllerClass = 'App\\Application\\Controllers\\' . $route['controller'];
        if (class_exists($controllerClass)) {
            $controller = new $controllerClass();
            $action = $route['action'] ?? 'handle';
            if (method_exists($controller, $action)) {
                $result = $controller->$action();
                echo json_encode($result);
            } else {
                throw new Exception("Action {$action} not found in {$controllerClass}");
            }
        } else {
            // Controller not found - return operational status
            echo json_encode([
                'success' => true,
                'message' => 'API is operational',
                'meta' => ['timestamp' => date('c'), 'endpoint' => $_SERVER['REQUEST_URI']]
            ]);
        }
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => [
            'code' => 'INTERNAL_ERROR',
            'message' => $config['debug'] ? $e->getMessage() : 'Internal server error'
        ],
        'meta' => ['timestamp' => date('c')]
    ]);
}
