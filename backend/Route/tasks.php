<?php
// tasks.php

// Require the shared database config (file is in ../database/config.php)
require_once __DIR__ . '/../database/config.php';
require_once __DIR__ . '/../app/controllers/TaskController.php';
require_once __DIR__ . '/../app/views/JsonView.php';

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

class TaskAPI
{
    private $controller;

    public function __construct()
    {
        $config = Config::getInstance();
        $pdo = $config->getConnection();
        $this->controller = new TaskController($pdo);
    }

    // Route requests
    public function handleRequest()
    {
        $method = $_SERVER['REQUEST_METHOD'];

        // Determine request path reliably whether the script is served from a subdirectory
        // Prefer PATH_INFO (when using php -S with router), otherwise use the REQUEST_URI
        $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

        // Strip the script's directory from the request path so routes like `/tasks` match
        $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        if ($scriptDir !== '' && strpos($requestPath, $scriptDir) === 0) {
            $requestPath = substr($requestPath, strlen($scriptDir));
        }

        // If PATH_INFO exists (built-in server), use it because it's already the trimmed path
        $path = '/' . trim((string)($_SERVER['PATH_INFO'] ?? ltrim($requestPath, '/')), '/');

        // Delegate to controller
        try {
            if ($method == 'GET' && $path == '/tasks') {
                $this->controller->index();
                return;
            }

            if ($method == 'POST' && $path == '/tasks') {
                $this->controller->store();
                return;
            }

            if ($method == 'PUT' && preg_match('#^/tasks/(\d+)$#', $path, $matches)) {
                $this->controller->update($matches[1]);
                return;
            }

            if ($method == 'DELETE' && preg_match('#^/tasks/(\d+)$#', $path, $matches)) {
                $this->controller->destroy($matches[1]);
                return;
            }

            JsonView::render(['success' => false, 'error' => 'Endpoint not found'], 404);
        } catch (Throwable $e) {
            JsonView::render(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}

// Initialize and handle the request
$api = new TaskAPI();
$api->handleRequest();
