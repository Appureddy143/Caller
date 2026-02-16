<?php
// includes/config.php
// Neon PostgreSQL Connection — supports .env (local) or Render env vars

// ── Load .env file (Local Only) ─────────────────────────────────────────
$envFile = dirname(__DIR__) . '/.env';

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#')) continue;
        if (!str_contains($line, '=')) continue;

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        if (getenv($key) === false) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

// ── Database Configuration ─────────────────────────────────────────────
define('DB_HOST', getenv('DB_HOST') ?: '');
define('DB_PORT', getenv('DB_PORT') ?: '5432');
define('DB_NAME', getenv('DB_NAME') ?: 'neondb');
define('DB_USER', getenv('DB_USER') ?: '');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_SSL', true);

// ── Application ────────────────────────────────────────────────────────
define('APP_NAME', getenv('APP_NAME') ?: 'AdmissionConnect');
define('APP_URL', getenv('APP_URL') ?: 'http://localhost');

// ── Database Connection (Singleton) ────────────────────────────────────
function getDB() {
    static $pdo = null;

    if ($pdo === null) {

        if (!DB_HOST || !DB_USER) {
            http_response_code(500);
            exit('Database environment variables not configured.');
        }

        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s;sslmode=require',
            DB_HOST,
            DB_PORT,
            DB_NAME
        );

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {

            // Hide detailed error in production
            if (getenv('APP_ENV') === 'production') {
                http_response_code(500);
                exit('Database connection failed.');
            }

            http_response_code(500);
            exit('Database connection failed: ' . $e->getMessage());
        }
    }

    return $pdo;
}

// ── JSON Response Helper ───────────────────────────────────────────────
function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// ── Authentication Middleware ──────────────────────────────────────────
function authRequired() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['user_id'])) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }

    return $_SESSION;
}

function adminRequired() {
    $session = authRequired();

    if (($session['role'] ?? '') !== 'admin') {
        jsonResponse(['error' => 'Forbidden'], 403);
    }

    return $session;
}

// ── Input Sanitizer ────────────────────────────────────────────────────
function sanitize($val) {
    return htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
}
