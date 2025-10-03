<?php
// tasks.php

// Require the shared database config (file is in ../database/config.php)
require_once __DIR__ . '/../database/config.php';

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
    private $pdo;

    public function __construct()
    {
        $config = Config::getInstance();
        $this->pdo = $config->getConnection();
    }

    // Helper function to get request data
    private function getRequestData()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST' || $_SERVER['REQUEST_METHOD'] == 'PUT') {
            $input = file_get_contents('php://input');
            return json_decode($input, true);
        }
        return [];
    }

    // Helper function to send JSON response
    private function sendResponse($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }

    // Helper function to send error response
    private function sendError($message, $statusCode = 400)
    {
        $this->sendResponse(['error' => $message], $statusCode);
    }

    // GET /tasks - Retrieve all tasks
    public function getTasks()
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM tasks ORDER BY created_at DESC");
            $stmt->execute();
            $tasks = $stmt->fetchAll();

            $this->sendResponse([
                'success' => true,
                'data' => $tasks,
                'count' => count($tasks)
            ]);
        } catch (PDOException $e) {
            $this->sendError('Failed to retrieve tasks: ' . $e->getMessage(), 500);
        }
    }

    // POST /tasks - Add a new task
    public function createTask()
    {
        $data = $this->getRequestData();

        // Validate input
        if (!isset($data['title']) || empty(trim($data['title']))) {
            $this->sendError('Title is required');
        }

        $title = trim($data['title']);
        $status = isset($data['status']) && in_array($data['status'], ['pending', 'done'])
            ? $data['status']
            : 'pending';

        try {
            $stmt = $this->pdo->prepare("INSERT INTO tasks (title, status) VALUES (?, ?)");
            $stmt->execute([$title, $status]);

            $taskId = $this->pdo->lastInsertId();

            // Fetch the created task
            $stmt = $this->pdo->prepare("SELECT * FROM tasks WHERE id = ?");
            $stmt->execute([$taskId]);
            $task = $stmt->fetch();

            $this->sendResponse([
                'success' => true,
                'message' => 'Task created successfully',
                'data' => $task
            ], 201);
        } catch (PDOException $e) {
            $this->sendError('Failed to create task: ' . $e->getMessage(), 500);
        }
    }

    // PUT /tasks/{id} - Update an existing task
    public function updateTask($id)
    {
        if (!is_numeric($id) || $id <= 0) {
            $this->sendError('Invalid task ID');
        }

        $data = $this->getRequestData();

        // Check if at least one field is provided
        if (!isset($data['title']) && !isset($data['status'])) {
            $this->sendError('Either title or status must be provided for update');
        }

        try {
            // First, check if task exists
            $stmt = $this->pdo->prepare("SELECT * FROM tasks WHERE id = ?");
            $stmt->execute([$id]);
            $existingTask = $stmt->fetch();

            if (!$existingTask) {
                $this->sendError('Task not found', 404);
            }

            // Build dynamic update query
            $updateFields = [];
            $params = [];

            if (isset($data['title'])) {
                if (empty(trim($data['title']))) {
                    $this->sendError('Title cannot be empty');
                }
                $updateFields[] = "title = ?";
                $params[] = trim($data['title']);
            }

            if (isset($data['status'])) {
                if (!in_array($data['status'], ['pending', 'done'])) {
                    $this->sendError('Status must be either "pending" or "done"');
                }
                $updateFields[] = "status = ?";
                $params[] = $data['status'];
            }

            $params[] = $id; // For WHERE clause

            $sql = "UPDATE tasks SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            // Fetch updated task
            $stmt = $this->pdo->prepare("SELECT * FROM tasks WHERE id = ?");
            $stmt->execute([$id]);
            $updatedTask = $stmt->fetch();

            $this->sendResponse([
                'success' => true,
                'message' => 'Task updated successfully',
                'data' => $updatedTask
            ]);
        } catch (PDOException $e) {
            $this->sendError('Failed to update task: ' . $e->getMessage(), 500);
        }
    }

    // DELETE /tasks/{id} - Delete a task
    public function deleteTask($id)
    {
        if (!is_numeric($id) || $id <= 0) {
            $this->sendError('Invalid task ID');
        }

        try {
            // First, check if task exists
            $stmt = $this->pdo->prepare("SELECT * FROM tasks WHERE id = ?");
            $stmt->execute([$id]);
            $existingTask = $stmt->fetch();

            if (!$existingTask) {
                $this->sendError('Task not found', 404);
            }

            $stmt = $this->pdo->prepare("DELETE FROM tasks WHERE id = ?");
            $stmt->execute([$id]);

            $this->sendResponse([
                'success' => true,
                'message' => 'Task deleted successfully'
            ]);
        } catch (PDOException $e) {
            $this->sendError('Failed to delete task: ' . $e->getMessage(), 500);
        }
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

        // Simple routing using normalized $path
        if ($method == 'GET' && $path == '/tasks') {
            $this->getTasks();
            return;
        }

        if ($method == 'POST' && $path == '/tasks') {
            $this->createTask();
            return;
        }

        if ($method == 'PUT' && preg_match('#^/tasks/(\d+)$#', $path, $matches)) {
            $this->updateTask($matches[1]);
            return;
        }

        if ($method == "DELETE" && preg_match('#^/tasks/(\d+)$#', $path, $matches)) {
            $this->deleteTask($matches[1]);
            return;
        }

        $this->sendError('Endpoint not found', 404);
    }
}

// Initialize and handle the request
$api = new TaskAPI();
$api->handleRequest();
