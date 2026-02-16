<?php
// router.php — PHP built-in server router
// Usage: php -S 0.0.0.0:$PORT router.php
//
// Replicates .htaccess routing for php -S (Render free tier, local dev).
// Apache/Docker uses .htaccess instead — this file is ignored there.

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = '/' . ltrim(rawurldecode($uri), '/');

// ── Security blocks ───────────────────────────────────────
if (preg_match('#^/includes(/|$)#', $uri)) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo '{"error":"Forbidden"}';
    exit;
}
if (in_array($uri, ['/.env', '/.env.example', '/.dockerignore'])) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

// ── Static / existing files pass through ────────────────────
$filePath = __DIR__ . $uri;
if ($uri !== '/' && file_exists($filePath) && !is_dir($filePath)) {
    // Let built-in server handle static files (css, js, images)
    // For PHP files, require them so $_SERVER is set correctly
    if (pathinfo($filePath, PATHINFO_EXTENSION) === 'php') {
        require $filePath;
        exit;
    }
    return false; // serve as-is
}

// ── Route /api/*.php ─────────────────────────────────────────
if (preg_match('#^/api/([a-z_]+\.php)#i', $uri, $m)) {
    $f = __DIR__ . '/api/' . $m[1];
    if (file_exists($f)) { require $f; exit; }
}

// ── Route /pages/*.php ───────────────────────────────────────
if (preg_match('#^/pages/([a-z_]+\.php)$#i', $uri, $m)) {
    $f = __DIR__ . '/pages/' . $m[1];
    if (file_exists($f)) { require $f; exit; }
}

// ── Everything else → index.php ─────────────────────────────
require __DIR__ . '/index.php';
