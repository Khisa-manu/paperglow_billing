<?php
/**
 * PaperGlow Billing System - Built-in Server Router
 */

declare(strict_types=1);

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Serve static assets directly if they exist
$filePath = __DIR__ . $uri;
if ($uri !== '/' && file_exists($filePath) && !is_dir($filePath)) {
    $ext = pathinfo($filePath, PATHINFO_EXTENSION);
    if ($ext !== 'php') {
        return false;
    }
}

// Root redirect
if ($uri === '/' || $uri === '' || $uri === '/index.html') {
    require_once __DIR__ . '/includes/auth.php';
    if (isAuthenticated()) {
        header('Location: /dashboard/index.php');
    } else {
        header('Location: /auth/login.php');
    }
    exit;
}

// Check if PHP script exists
if (file_exists($filePath) && !is_dir($filePath) && str_ends_with($filePath, '.php')) {
    require $filePath;
    exit;
}

// Check with .php extension
if (file_exists($filePath . '.php')) {
    require $filePath . '.php';
    exit;
}

// Check directory index
if (is_dir($filePath) && file_exists($filePath . '/index.php')) {
    require $filePath . '/index.php';
    exit;
}

// Not found
http_response_code(404);
echo "404 Not Found: " . htmlspecialchars($uri);
