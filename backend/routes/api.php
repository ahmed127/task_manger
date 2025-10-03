<?php

require_once __DIR__ . '/../database/config.php';
require_once __DIR__ . '/../app/controllers/TaskController.php';
require_once __DIR__ . '/../app/views/JsonView.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$config = Config::getInstance();
$pdo = $config->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Normalize: strip script directory if needed
$scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
if ($scriptDir !== '' && strpos($path, $scriptDir) === 0) {
    $path = substr($path, strlen($scriptDir));
}

$path = '/' . trim($path, '/');

try {
    // Load structured route map
    $routes = require __DIR__ . '/links_map.php';

    foreach ($routes as $route) {
        if (!isset($route['method']) || !isset($route['path']) || !isset($route['action'])) continue;
        if (strtoupper($route['method']) !== strtoupper($method)) continue;

        $pattern = $route['path'];
        // Convert {param} to named capture groups
        $regex = preg_replace_callback('#\{([a-zA-Z_][a-zA-Z0-9_-]*)\}#', function ($m) {
            return '(?P<' . $m[1] . '>[^/]+)';
        }, $pattern);
        $regex = '#^' . str_replace('/', '\/', $regex) . '$#';

        if (preg_match($regex, $path, $matches)) {
            // extract named params
            $params = [];
            foreach ($matches as $k => $v) {
                if (!is_int($k)) $params[$k] = $v;
            }

            // Resolve controller and action
            list($controllerName, $action) = explode('@', $route['action']);
            $controllerFile = __DIR__ . '/../app/controllers/' . $controllerName . '.php';
            if (!class_exists($controllerName)) {
                if (file_exists($controllerFile)) require_once $controllerFile;
            }
            if (!class_exists($controllerName)) {
                JsonView::render(['success' => false, 'error' => 'Controller not found: ' . $controllerName], 500);
            }

            $controllerInstance = new $controllerName($pdo);
            if (!method_exists($controllerInstance, $action)) {
                JsonView::render(['success' => false, 'error' => 'Action not found: ' . $action], 500);
            }

            // Call with positional params
            call_user_func_array([$controllerInstance, $action], array_values($params));
            // controllers are expected to render responses via JsonView and exit
            return;
        }
    }

    JsonView::render(['success' => false, 'error' => 'Endpoint not found'], 404);
} catch (Throwable $e) {
    JsonView::render(['success' => false, 'error' => $e->getMessage()], 500);
}
