<?php
// api/users.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

require_once '../includes/config.php';
session_start();

if (empty($_SESSION['user_id'])) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

$action = $_GET['action'] ?? '';
$role   = $_SESSION['role'];

switch ($action) {
    case 'list':        listUsers(); break;
    case 'add':         addUser(); break;
    case 'delete':      deleteUser(); break;
    case 'profile':     getProfile(); break;
    case 'telecallers': getTelecallers(); break;
    case 'stats':       getUserStats(); break;
    default: jsonResponse(['error' => 'Unknown action'], 400);
}

function listUsers() {
    if ($_SESSION['role'] !== 'admin') jsonResponse(['error' => 'Forbidden'], 403);
    $db = getDB();
    $stmt = $db->query("SELECT id,name,email,phone,gender,dob,role,created_at FROM users ORDER BY created_at DESC");
    jsonResponse($stmt->fetchAll());
}

function addUser() {
    if ($_SESSION['role'] !== 'admin') jsonResponse(['error' => 'Forbidden'], 403);
    $data = json_decode(file_get_contents('php://input'), true);

    $required = ['name','email','phone','gender','dob','role'];
    foreach ($required as $field) {
        if (empty($data[$field])) jsonResponse(['error' => "Field '$field' required"], 400);
    }

    if (!in_array($data['role'], ['admin','telecaller','office'])) {
        jsonResponse(['error' => 'Invalid role'], 400);
    }

    // Generate system password
    $sysPass = 'TC' . strtoupper(substr($data['name'],0,3)) . rand(100,999) . '!';
    $hash    = password_hash($sysPass, PASSWORD_DEFAULT);

    $db = getDB();
    try {
        $stmt = $db->prepare(
            "INSERT INTO users (name,email,phone,gender,dob,role,password_hash,system_password,is_first_login)
             VALUES (?,?,?,?,?,?,?,?,TRUE) RETURNING id"
        );
        $stmt->execute([
            sanitize($data['name']),
            sanitize($data['email']),
            sanitize($data['phone']),
            sanitize($data['gender']),
            $data['dob'],
            $data['role'],
            $hash,
            $sysPass
        ]);
        $row = $stmt->fetch();
        jsonResponse(['success' => true, 'user_id' => $row['id'], 'system_password' => $sysPass]);
    } catch (PDOException $e) {
        if (str_contains($e->getMessage(), 'unique') || str_contains($e->getMessage(), 'duplicate')) {
            jsonResponse(['error' => 'Email already exists'], 409);
        }
        jsonResponse(['error' => $e->getMessage()], 500);
    }
}

function deleteUser() {
    if ($_SESSION['role'] !== 'admin') jsonResponse(['error' => 'Forbidden'], 403);
    $id = intval($_GET['id'] ?? 0);
    if (!$id) jsonResponse(['error' => 'User ID required'], 400);
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM users WHERE id=? AND id != ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    jsonResponse(['success' => true]);
}

function getProfile() {
    $db = getDB();
    $stmt = $db->prepare("SELECT id,name,email,phone,gender,dob,role FROM users WHERE id=?");
    $stmt->execute([$_SESSION['user_id']]);
    jsonResponse($stmt->fetch());
}

function getTelecallers() {
    if ($_SESSION['role'] !== 'admin') jsonResponse(['error' => 'Forbidden'], 403);
    $db = getDB();
    $stmt = $db->query("SELECT id,name,email,phone FROM users WHERE role='telecaller' ORDER BY name");
    jsonResponse($stmt->fetchAll());
}

function getUserStats() {
    if ($_SESSION['role'] !== 'admin') jsonResponse(['error' => 'Forbidden'], 403);
    $db = getDB();

    $stmt = $db->query("
        SELECT u.id, u.name, u.email, u.role,
            COUNT(s.id) AS total_assigned,
            SUM(CASE WHEN s.status='accepted' THEN 1 ELSE 0 END) AS accepted,
            SUM(CASE WHEN s.status='rejected' THEN 1 ELSE 0 END) AS rejected,
            SUM(CASE WHEN s.status='pending' OR s.status='in_progress' OR s.status='callback' THEN 1 ELSE 0 END) AS pending
        FROM users u
        LEFT JOIN students s ON s.assigned_to = u.id
        WHERE u.role='telecaller'
        GROUP BY u.id, u.name, u.email, u.role
        ORDER BY total_assigned DESC
    ");
    jsonResponse($stmt->fetchAll());
}
