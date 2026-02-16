<?php
// includes/config.php
// Neon PostgreSQL Connection — supports .env file (local) or Render env vars (production)

// ── Load .env file if it exists (for local development) ──────────────────────
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments and section headers
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        // Only process valid KEY=VALUE lines
        if (strpos($line, '=') === false) continue;
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        // Don't override real environment variables already set by Render
        if (!getenv($key)) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

// ── Database Configuration ────────────────────────────────────────────────────
define('DB_HOST', getenv('DB_HOST') ?: 'your-neon-endpoint.neon.tech');
define('DB_PORT', getenv('DB_PORT') ?: '5432');
define('DB_NAME', getenv('DB_NAME') ?: 'neondb');
define('DB_USER', getenv('DB_USER') ?: 'neondb_owner');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_SSL',  true); // Always required for Neon

// ── Application ───────────────────────────────────────────────────────────────
define('APP_NAME', getenv('APP_NAME') ?: 'AdmissionConnect');
define('APP_URL',  getenv('APP_URL')  ?: 'http://localhost');

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s;sslmode=require',
            DB_HOST, DB_PORT, DB_NAME
        );
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
            exit;
        }
    }
    return $pdo;
}

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function authRequired() {
    session_start();
    if (empty($_SESSION['user_id'])) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }
    return $_SESSION;
}

function adminRequired() {
    $session = authRequired();
    if ($session['role'] !== 'admin') {
        jsonResponse(['error' => 'Forbidden'], 403);
    }
    return $session;
}

function sanitize($val) {
    return htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
}
