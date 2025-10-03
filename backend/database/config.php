<?php
// config.php

class Config
{
    private static $instance = null;
    private $pdo;

    private function __construct()
    {
        // Use environment variables when available, otherwise sensible defaults.
        // Note: 'localhost::3306' is invalid as a host. Use host and port separately.
        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $port = getenv('DB_PORT') ?: '3306';
        $dbname = getenv('DB_NAME') ?: 'task_manager';
        $username = getenv('DB_USER') ?: 'root';
        $password = getenv('DB_PASS') ?: '';

        try {
            $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8";
            $this->pdo = new PDO($dsn, $username, $password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Provide the DSN in the error only in development to help diagnosis.
            $this->sendErrorResponse('Database connection failed: ' . $e->getMessage());
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Config();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->pdo;
    }

    private function sendErrorResponse($message)
    {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['error' => $message]);
        exit;
    }
}
