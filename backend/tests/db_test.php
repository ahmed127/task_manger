<?php
require_once __DIR__ . '/../database/config.php';

// Simple test that attempts to get a DB connection and prints result in JSON.
try {
    $config = Config::getInstance();
    $pdo = $config->getConnection();
    if ($pdo) {
        echo json_encode(['ok' => true, 'message' => 'Connected to database']);
    } else {
        echo json_encode(['ok' => false, 'message' => 'No PDO instance returned']);
    }
} catch (Throwable $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => 'Test failed: ' . $e->getMessage()]);
}
