<?php
// api/auth.php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

require_once __DIR__ . '/../includes/config.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'login':       handleLogin(); break;
    case 'logout':      handleLogout(); break;
    case 'set_password': handleSetPassword(); break;
    case 'forgot_password': handleForgotPassword(); break;
    case 'check_session': handleCheckSession(); break;
    default: jsonResponse(['error' => 'Unknown action'], 400);
}

function handleLogin() {
    $data = json_decode(file_get_contents('php://input'), true);
    $email = trim($data['email'] ?? '');
    $password = trim($data['password'] ?? '');

    if (!$email || !$password) {
        jsonResponse(['error' => 'Email and password required'], 400);
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        jsonResponse(['error' => 'Invalid credentials'], 401);
    }

    // Check if using system-generated password
    if ($user['is_first_login'] && $password === $user['system_password']) {
        $_SESSION['temp_user_id'] = $user['id'];
        $_SESSION['temp_email']   = $user['email'];
        jsonResponse(['require_set_password' => true, 'user_id' => $user['id']]);
    }

    if (!password_verify($password, $user['password_hash'])) {
        jsonResponse(['error' => 'Invalid credentials'], 401);
    }

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['name']    = $user['name'];
    $_SESSION['email']   = $user['email'];
    $_SESSION['role']    = $user['role'];

    jsonResponse([
        'success' => true,
        'user' => [
            'id'   => $user['id'],
            'name' => $user['name'],
            'email'=> $user['email'],
            'role' => $user['role'],
        ]
    ]);
}

function handleSetPassword() {
    $data       = json_decode(file_get_contents('php://input'), true);
    $user_id    = intval($data['user_id'] ?? 0);
    $password   = $data['password'] ?? '';
    $confirm    = $data['confirm_password'] ?? '';

    if (!$user_id || !$password || !$confirm) {
        jsonResponse(['error' => 'All fields required'], 400);
    }
    if ($password !== $confirm) {
        jsonResponse(['error' => 'Passwords do not match'], 400);
    }
    if (strlen($password) < 6) {
        jsonResponse(['error' => 'Password must be at least 6 characters'], 400);
    }

    $db   = getDB();
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare("UPDATE users SET password_hash=?, is_first_login=FALSE, updated_at=NOW() WHERE id=?");
    $stmt->execute([$hash, $user_id]);

    $stmt2 = $db->prepare("SELECT * FROM users WHERE id=?");
    $stmt2->execute([$user_id]);
    $user = $stmt2->fetch();

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['name']    = $user['name'];
    $_SESSION['email']   = $user['email'];
    $_SESSION['role']    = $user['role'];

    jsonResponse(['success' => true, 'role' => $user['role']]);
}

function handleForgotPassword() {
    $data     = json_decode(file_get_contents('php://input'), true);
    $email    = trim($data['email'] ?? '');
    $dob      = trim($data['dob'] ?? '');
    $password = $data['password'] ?? '';
    $confirm  = $data['confirm_password'] ?? '';

    if (!$email || !$dob || !$password || !$confirm) {
        jsonResponse(['error' => 'All fields required'], 400);
    }
    if ($password !== $confirm) {
        jsonResponse(['error' => 'Passwords do not match'], 400);
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE email=? AND dob=?");
    $stmt->execute([$email, $dob]);
    $user = $stmt->fetch();

    if (!$user) {
        jsonResponse(['error' => 'Email and date of birth do not match'], 401);
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt2 = $db->prepare("UPDATE users SET password_hash=?, is_first_login=FALSE, updated_at=NOW() WHERE id=?");
    $stmt2->execute([$hash, $user['id']]);

    jsonResponse(['success' => true]);
}

function handleLogout() {
    session_destroy();
    jsonResponse(['success' => true]);
}

function handleCheckSession() {
    if (!empty($_SESSION['user_id'])) {
        jsonResponse([
            'logged_in' => true,
            'user' => [
                'id'   => $_SESSION['user_id'],
                'name' => $_SESSION['name'],
                'email'=> $_SESSION['email'],
                'role' => $_SESSION['role'],
            ]
        ]);
    }
    jsonResponse(['logged_in' => false]);
}
