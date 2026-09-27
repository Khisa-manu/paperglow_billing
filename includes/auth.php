<?php
/**
 * PaperGlow Billing System - Authentication & Session Helper
 */

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

function startAuthSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        // Enforce secure session cookie settings
        session_set_cookie_params([
            'lifetime' => 86400 * 7,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        session_start();
    }
}

/**
 * Checks if a user is currently logged in.
 */
function isAuthenticated(): bool
{
    startAuthSession();
    return !empty($_SESSION['user']) && !empty($_SESSION['user']['id']);
}

/**
 * Get current authenticated user payload.
 */
function getCurrentUser(): ?array
{
    startAuthSession();
    return $_SESSION['user'] ?? null;
}

/**
 * Get current authenticated user ID.
 */
function getCurrentUserId(): int
{
    $user = getCurrentUser();
    return $user ? (int)$user['id'] : 0;
}

/**
 * Guard for protected pages. Redirects to login if not logged in.
 */
function requireAuth(): void
{
    if (!isAuthenticated()) {
        setFlash('danger', 'Please log in to access this page.');
        header('Location: /auth/login.php');
        exit;
    }
}

/**
 * Guard for guest-only pages (login, register). Redirects to dashboard if already logged in.
 */
function requireGuest(): void
{
    if (isAuthenticated()) {
        header('Location: /dashboard/index.php');
        exit;
    }
}

/**
 * Login user and regenerate session ID for security.
 */
function loginUser(array $user): void
{
    startAuthSession();
    session_regenerate_id(true);

    $_SESSION['user'] = [
        'id'    => (int)$user['id'],
        'name'  => $user['name'],
        'email' => $user['email'],
        'role'  => $user['role'] ?? 'admin',
    ];
}

/**
 * Destroy user session and logout.
 */
function logoutUser(): void
{
    startAuthSession();
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();
}
