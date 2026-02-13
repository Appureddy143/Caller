<?php
// includes/config.php
// Neon PostgreSQL Connection Configuration

define('DB_HOST', getenv('DB_HOST') ?: 'your-neon-host.neon.tech');
define('DB_PORT', getenv('DB_PORT') ?: '5432');
define('DB_NAME', getenv('DB_NAME') ?: 'telecaller_db');
define('DB_USER', getenv('DB_USER') ?: 'your_db_user');
define('DB_PASS', getenv('DB_PASS') ?: 'your_db_password');
define('DB_SSL',  true);

define('APP_NAME', 'AdmissionConnect');
define('APP_URL', getenv('APP_URL') ?: 'http://localhost');

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
