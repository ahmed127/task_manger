<?php

require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../views/JsonView.php';

class TaskController
{
    private $model;

    public function __construct(PDO $pdo)
    {
        $this->model = new Task($pdo);
    }

    public function index()
    {
        $tasks = $this->model->all();
        $resources = array_map([$this, 'buildTaskResource'], $tasks);
        JsonView::render(['success' => true, 'data' => $resources]);
    }

    public function store()
    {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $title = isset($input['title']) ? trim($input['title']) : '';
        $body = isset($input['body']) ? trim($input['body']) : '';
        $status = isset($input['status']) && in_array($input['status'], [1, 2, 3]) ? $input['status'] : 1;

        if ($title === '') {
            JsonView::render(['success' => false, 'error' => 'Title is required'], 400);
        }

        $task = $this->model->create($title, $body, $status);
        JsonView::render(['success' => true, 'message' => 'Task created', 'data' => $this->buildTaskResource($task)], 201);
    }

    public function show($id)
    {
        $tasks = $this->model->all();
        if (!ctype_digit((string)$id) || (int)$id <= 0) {
            JsonView::render(['success' => false, 'error' => 'Invalid ID'], 400);
        }
        $task = $this->model->find((int)$id);
        if (!$task) {
            JsonView::render(['success' => false, 'error' => 'Task not found'], 404);
        }
        $resources = $this->buildTaskResource($task);
        JsonView::render(['success' => true, 'data' => $resources]);
    }

    public function update($id)
    {
        if (!ctype_digit((string)$id) || (int)$id <= 0) {
            JsonView::render(['success' => false, 'error' => 'Invalid ID'], 400);
        }
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        if (empty($input)) JsonView::render(['success' => false, 'error' => 'No data provided'], 400);
        if (isset($input['status']) && !in_array($input['status'], ['pending', 'done'])) {
            JsonView::render(['success' => false, 'error' => 'Invalid status'], 400);
        }
        $updated = $this->model->update((int)$id, $input);
        if ($updated === null) JsonView::render(['success' => false, 'error' => 'Nothing to update'], 400);
        JsonView::render(['success' => true, 'message' => 'Task updated', 'data' => $this->buildTaskResource($updated)]);
    }
    public function changeStatus($id)
    {
        if (!ctype_digit((string)$id) || (int)$id <= 0) {
            JsonView::render(['success' => false, 'error' => 'Invalid ID'], 400);
        }
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        if (empty($input)) JsonView::render(['success' => false, 'error' => 'No data provided'], 400);
        if (isset($input['status']) && !in_array($input['status'], [1, 2, 3])) {
            JsonView::render(['success' => false, 'error' => 'Invalid status'], 400);
        }
        $updated = $this->model->update((int)$id, $input);
        if ($updated === null) JsonView::render(['success' => false, 'error' => 'Nothing to update'], 400);
        JsonView::render(['success' => true, 'message' => 'Task updated', 'data' => $this->buildTaskResource($updated)]);
    }

    public function destroy($id)
    {
        if (!ctype_digit((string)$id) || (int)$id <= 0) {
            JsonView::render(['success' => false, 'error' => 'Invalid ID'], 400);
        }
        $this->model->delete((int)$id);
        JsonView::render(['success' => true, 'message' => 'Task deleted']);
    }

    // Build HATEOAS-style links for a single task record
    private function buildTaskResource($task)
    {
        if (!$task) return $task;
        $base = $this->getBaseUrl();
        $id = $task['id'] ?? null;
        $task['links'] = [
            'self' => $base . '/tasks/' . $id,
            'update' => $base . '/tasks/' . $id,
            'delete' => $base . '/tasks/' . $id,
        ];
        return $task;
    }

    private function getBaseUrl()
    {
        $scheme = (!empty($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http'));
        $host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');
        return $scheme . '://' . $host;
    }
}
