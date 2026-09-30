<?php
/**
 * Centralized session, authentication, authorization and CSRF helpers.
 */
function startSecureSession(): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');

    $secureEnv = getenv('SESSION_SECURE_COOKIE');
    $secure = $secureEnv !== false
        ? filter_var($secureEnv, FILTER_VALIDATE_BOOLEAN)
        : (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

function destroySession(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        startSecureSession();
    }

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, [
            'expires' => time() - 42000,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax',
        ]);
    }

    session_destroy();
}

function isSessionValid(): bool {
    if (!isset($_SESSION['username'], $_SESSION['login_time_stamp'])) {
        return false;
    }

    $now = time();
    $lastActivity = (int) ($_SESSION['last_activity'] ?? $_SESSION['login_time_stamp']);

    if ($now - $lastActivity > 600) {
        return false;
    }

    $_SESSION['last_activity'] = $now;
    return true;
}

function requireLogin(bool $staffOnly = false): void {
    startSecureSession();
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');

    if (!isSessionValid()) {
        destroySession();
        header('Location: /login.php');
        exit();
    }

    if ($staffOnly && (int) ($_SESSION['is_staff'] ?? 0) !== 1) {
        http_response_code(403);
        echo 'Forbidden';
        exit();
    }
}

function requireApiLogin(bool $staffOnly = false): void {
    startSecureSession();
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');

    if (!isSessionValid()) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Authentication required']);
        exit();
    }

    if ($staffOnly && (int) ($_SESSION['is_staff'] ?? 0) !== 1) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Forbidden']);
        exit();
    }
}

function csrfToken(): string {
    startSecureSession();

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function requireCsrf(): void {
    startSecureSession();

    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

    if (!is_string($token) || empty($_SESSION['csrf_token'])
        || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        if (str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')
            || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Invalid CSRF token']);
        } else {
            echo 'Invalid CSRF token';
        }
        exit();
    }
}
?>