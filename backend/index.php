<?php
// index.php - Front controller: load API routes

// If a static file exists, let the built-in server serve it
if (php_sapi_name() === 'cli-server') {
    $url  = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__ . $url['path'];
    if (is_file($file)) {
        return false; // serve the requested resource as-is.
    }
}

// Delegate to the routes file which bootstraps the MVC and handles requests
require_once __DIR__ . '/routes/api.php';
