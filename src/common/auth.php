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

function requireAdmin(): void
{
    ensureSessionStarted();
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        header('Location: /src/auth/login.php');
        exit;
    }
}

function attemptLogin(string $email, string $password): bool
{
    require_once __DIR__ . '/db.php';
    ensureSessionStarted();

    $db = getDatabaseConnection();
    $stmt = $db->prepare('SELECT id, name, email, role, password_hash FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
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
