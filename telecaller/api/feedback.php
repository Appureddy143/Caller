<?php
// api/feedback.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

require_once '../includes/config.php';
session_start();

if (empty($_SESSION['user_id'])) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'submit':   submitFeedback(); break;
    case 'history':  getFeedbackHistory(); break;
    case 'student':  getStudentFeedback(); break;
    default: jsonResponse(['error' => 'Unknown action'], 400);
}

function submitFeedback() {
    $data = json_decode(file_get_contents('php://input'), true);
    $student_id  = intval($data['student_id'] ?? 0);
    $call_status = $data['call_status'] ?? '';
    $other_reason= $data['other_reason'] ?? '';
    $callback_date = $data['callback_date'] ?? null;
    $notes       = $data['notes'] ?? '';

    if (!$student_id || !$call_status) {
        jsonResponse(['error' => 'Student ID and call status required'], 400);
    }
    if (!in_array($call_status, ['accepted','rejected','other'])) {
        jsonResponse(['error' => 'Invalid call status'], 400);
    }
    if ($call_status === 'other' && empty($other_reason)) {
        jsonResponse(['error' => 'Reason required for Other status'], 400);
    }

    $db = getDB();

    // Verify student belongs to this telecaller (or admin)
    if ($_SESSION['role'] === 'telecaller') {
        $chk = $db->prepare("SELECT id FROM students WHERE id=? AND assigned_to=?");
        $chk->execute([$student_id, $_SESSION['user_id']]);
        if (!$chk->fetch()) {
            jsonResponse(['error' => 'Student not assigned to you'], 403);
        }
    }

    // Insert feedback
    $stmt = $db->prepare(
        "INSERT INTO feedback (student_id, telecaller_id, call_status, other_reason, callback_date, notes)
         VALUES (?,?,?,?,?,?)"
    );
    $stmt->execute([
        $student_id,
        $_SESSION['user_id'],
        $call_status,
        $other_reason ?: null,
        $callback_date ?: null,
        $notes ?: null
    ]);

    // Update student status
    $statusMap = [
        'accepted' => 'accepted',
        'rejected' => 'rejected',
        'other'    => 'callback'
    ];
    $newStatus = $statusMap[$call_status];

    $updStmt = $db->prepare("UPDATE students SET status=?, updated_at=NOW() WHERE id=?");
    $updStmt->execute([$newStatus, $student_id]);

    // Create reminder if callback date provided
    if ($callback_date && $call_status === 'other') {
        // Remove old reminder
        $delRem = $db->prepare("DELETE FROM reminders WHERE student_id=? AND telecaller_id=?");
        $delRem->execute([$student_id, $_SESSION['user_id']]);

        $remStmt = $db->prepare(
            "INSERT INTO reminders (student_id, telecaller_id, reminder_date) VALUES (?,?,?)"
        );
        $remStmt->execute([$student_id, $_SESSION['user_id'], $callback_date]);
    }

    jsonResponse(['success' => true, 'new_status' => $newStatus]);
}

function getFeedbackHistory() {
    $uid = $_SESSION['user_id'];
    $db  = getDB();
    $stmt = $db->prepare(
        "SELECT f.*, s.name as student_name, s.mobile as student_mobile
         FROM feedback f
         JOIN students s ON s.id=f.student_id
         WHERE f.telecaller_id=?
         ORDER BY f.created_at DESC
         LIMIT 50"
    );
    $stmt->execute([$uid]);
    jsonResponse($stmt->fetchAll());
}

function getStudentFeedback() {
    $student_id = intval($_GET['student_id'] ?? 0);
    if (!$student_id) jsonResponse(['error' => 'Student ID required'], 400);

    $db = getDB();
    $stmt = $db->prepare(
        "SELECT f.*, u.name as caller_name
         FROM feedback f
         JOIN users u ON u.id=f.telecaller_id
         WHERE f.student_id=?
         ORDER BY f.created_at DESC"
    );
    $stmt->execute([$student_id]);
    jsonResponse($stmt->fetchAll());
}
