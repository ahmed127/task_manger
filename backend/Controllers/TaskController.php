<?php
// tasks.php - REST API for tasks
// Usage: php -S localhost:8000 tasks.php

require_once __DIR__ . '/database/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

class TaskController
{
    private $pdo;

    public function __construct()
    {
        $config = Config::getInstance();
        $this->pdo = $config->getConnection();
    }

    private function jsonResponse($data, int $status = 200)
    {
        http_response_code($status);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function getInputData(): array
    {
        $input = file_get_contents('php://input');
        if (empty($input)) {
            return [];
        }

        $data = json_decode($input, true);
        return is_array($data) ? $data : [];
    }

    // GET /tasks
    public function getTasks()
    {
        try {
            $stmt = $this->pdo->prepare('SELECT id, title, status, created_at FROM tasks ORDER BY created_at DESC');
            $stmt->execute();
            $tasks = $stmt->fetchAll();
            $this->jsonResponse(['success' => true, 'data' => $tasks, 'count' => count($tasks)]);
        } catch (PDOException $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // POST /tasks
    public function createTask()
    {
        $data = $this->getInputData();
        $title = isset($data['title']) ? trim($data['title']) : '';
        $body = isset($data['body']) ? trim($data['body']) : '';

        if ($title === '') {
            $this->jsonResponse(['success' => false, 'error' => 'Title is required'], 400);
        }
        if ($body === '') {
            $this->jsonResponse(['success' => false, 'error' => 'Body is required'], 400);
        }

        try {
            $stmt = $this->pdo->prepare('INSERT INTO tasks (title, body, status) VALUES (:title, :body, :status)');
            $stmt->execute(['title' => $title, 'body' => $body, 'status' => 1]);
            $id = (int)$this->pdo->lastInsertId();

            $stmt = $this->pdo->prepare('SELECT id, title, status, created_at FROM tasks WHERE id = :id');
            $stmt->execute(['id' => $id]);
            $task = $stmt->fetch();

            $this->jsonResponse(['success' => true, 'message' => 'Task created', 'data' => $task], 201);
        } catch (PDOException $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // PUT /tasks/{id}
    public function updateTask($id)
    {
        if (!ctype_digit((string)$id) || (int)$id <= 0) {
            $this->jsonResponse(['success' => false, 'error' => 'Invalid ID'], 400);
        }

        $data = $this->getInputData();
        if (empty($data)) {
            $this->jsonResponse(['success' => false, 'error' => 'No data provided'], 400);
        }

        $fields = [];
        $params = ['id' => (int)$id];

        if (isset($data['title'])) {
            $title = trim($data['title']);
            if ($title === '') {
                $this->jsonResponse(['success' => false, 'error' => 'Title cannot be empty'], 400);
            }
            $fields[] = 'title = :title';
            $params['title'] = $title;
        }

        if (isset($data['status'])) {
            if (!in_array($data['status'], ['pending', 'done'], true)) {
                $this->jsonResponse(['success' => false, 'error' => 'Invalid status'], 400);
            }
            $fields[] = 'status = :status';
            $params['status'] = $data['status'];
        }

        if (empty($fields)) {
            $this->jsonResponse(['success' => false, 'error' => 'Nothing to update'], 400);
        }

        try {
            // ensure exists
            $check = $this->pdo->prepare('SELECT id FROM tasks WHERE id = :id');
            $check->execute(['id' => (int)$id]);
            if (!$check->fetch()) {
                $this->jsonResponse(['success' => false, 'error' => 'Task not found'], 404);
            }

            $sql = 'UPDATE tasks SET ' . implode(', ', $fields) . ' WHERE id = :id';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            $stmt = $this->pdo->prepare('SELECT id, title, status, created_at FROM tasks WHERE id = :id');
            $stmt->execute(['id' => (int)$id]);
            $task = $stmt->fetch();

            $this->jsonResponse(['success' => true, 'message' => 'Task updated', 'data' => $task]);
        } catch (PDOException $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // DELETE /tasks/{id}
    public function deleteTask($id)
    {
        if (!ctype_digit((string)$id) || (int)$id <= 0) {
            $this->jsonResponse(['success' => false, 'error' => 'Invalid ID'], 400);
        }

        try {
            $stmt = $this->pdo->prepare('SELECT id FROM tasks WHERE id = :id');
            $stmt->execute(['id' => (int)$id]);
            if (!$stmt->fetch()) {
                $this->jsonResponse(['success' => false, 'error' => 'Task not found'], 404);
            }

            $stmt = $this->pdo->prepare('DELETE FROM tasks WHERE id = :id');
            $stmt->execute(['id' => (int)$id]);

            $this->jsonResponse(['success' => true, 'message' => 'Task deleted']);
        } catch (PDOException $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function route()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = '/' . ltrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        if ($path === '/tasks' && $method === 'GET') {
            $this->getTasks();
        }

        if ($path === '/tasks' && $method === 'POST') {
            $this->createTask();
        }

        if (preg_match('#^/tasks/(\d+)$#', $path, $m) && $method === 'PUT') {
            $this->updateTask($m[1]);
        }

        if (preg_match('#^/tasks/(\d+)$#', $path, $m) && $method === 'DELETE') {
            $this->deleteTask($m[1]);
        }

        $this->jsonResponse(['success' => false, 'error' => 'Endpoint not found'], 404);
    }
}

$api = new TaskAPI();
$api->route();
