<?php

class Task
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function all()
    {
        $stmt = $this->pdo->prepare('SELECT id, title, body, status, created_at FROM tasks ORDER BY created_at DESC');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function find(int $id)
    {
        $stmt = $this->pdo->prepare('SELECT id, title, body, status, created_at FROM tasks WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function create(string $title, string $body = '', string $status = 'pending')
    {
        $stmt = $this->pdo->prepare('INSERT INTO tasks (title, body, status) VALUES (:title, :body, :status)');
        $stmt->execute(['title' => $title, 'body' => $body, 'status' => $status]);
        $id = (int)$this->pdo->lastInsertId();
        return $this->find($id);
    }

    public function update(int $id, array $data)
    {
        $fields = [];
        $params = ['id' => $id];
        if (isset($data['title'])) {
            $fields[] = 'title = :title';
            $params['title'] = $data['title'];
        }
        if (isset($data['body'])) {
            $fields[] = 'body = :body';
            $params['body'] = $data['body'];
        }
        if (isset($data['status'])) {
            $fields[] = 'status = :status';
            $params['status'] = $data['status'];
        }
        if (empty($fields)) return null;
        $sql = 'UPDATE tasks SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $this->find($id);
    }

    public function delete(int $id)
    {
        $stmt = $this->pdo->prepare('DELETE FROM tasks WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return true;
    }
}
