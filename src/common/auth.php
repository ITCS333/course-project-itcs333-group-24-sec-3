<?php
// Handles session bootstrap and common authentication helpers.
function ensureSessionStarted(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function isUserLoggedIn(): bool
{
    ensureSessionStarted();
    return isset($_SESSION['user']);
}

function getCurrentUser(): ?array
{
    ensureSessionStarted();
    return $_SESSION['user'] ?? null;
}

function isAdmin(): bool
{
    ensureSessionStarted();
    return isset($_SESSION['user']) && (int)$_SESSION['user']['is_admin'] === 1;
}

function requireLogin(): void
{
    ensureSessionStarted();
    if (!isset($_SESSION['user'])) {
        header('Location: /src/auth/login.html');
        exit;
    }
}

function requireAdmin(): void
{
    ensureSessionStarted();
    if (!isset($_SESSION['user']) || (int)$_SESSION['user']['is_admin'] !== 1) {
        header('Location: /src/auth/login.html');
        exit;
    }
}

/**
 * Require authentication for API requests
 * Returns JSON error if not authenticated
 */
function requireApiAuthentication(): void
{
    ensureSessionStarted();
    if (!isset($_SESSION['user'])) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Authentication required. Please log in.',
            'error' => 'UNAUTHORIZED'
        ]);
        exit;
    }
}

/**
 * Require admin role for API requests
 * Returns JSON error if not admin
 */
function requireApiAdmin(): void
{
    ensureSessionStarted();
    if (!isset($_SESSION['user'])) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Authentication required. Please log in.',
            'error' => 'UNAUTHORIZED'
        ]);
        exit;
    }
    
    if ((int)$_SESSION['user']['is_admin'] !== 1) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Admin privileges required.',
            'error' => 'FORBIDDEN'
        ]);
        exit;
    }
}

function attemptLogin(string $email, string $password): bool
{
    require_once __DIR__ . '/db.php';
    ensureSessionStarted();

    $db = getDatabaseConnection();
    $stmt = $db->prepare('SELECT id, name, email, password, is_admin FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        return false;
    }

    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'is_admin' => (int) $user['is_admin'],
    ];

    return true;
}

function logout(): void
{
    ensureSessionStarted();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}
