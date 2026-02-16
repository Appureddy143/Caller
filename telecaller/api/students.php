<?php
// api/students.php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

require_once __DIR__ . '/../includes/config.php';

if (empty($_SESSION['user_id'])) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'add':          addStudent(); break;
    case 'list':         listStudents(); break;
    case 'my_list':      myStudentList(); break;
    case 'detail':       studentDetail(); break;
    case 'assign':       assignStudent(); break;
    case 'auto_assign':  autoAssign(); break;
    case 'export':       exportExcel(); break;
    case 'summary':      summaryStats(); break;
    default: jsonResponse(['error' => 'Unknown action'], 400);
}

function addStudent() {
    if (!in_array($_SESSION['role'], ['admin','office'])) {
        jsonResponse(['error' => 'Forbidden'], 403);
    }
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['name']) || empty($data['mobile'])) {
        jsonResponse(['error' => 'Name and mobile required'], 400);
    }

    $db = getDB();

    // Auto-assign to telecaller with fewest students
    $stmt = $db->query(
        "SELECT u.id FROM users u
         LEFT JOIN students s ON s.assigned_to=u.id
         WHERE u.role='telecaller'
         GROUP BY u.id ORDER BY COUNT(s.id) ASC LIMIT 1"
    );
    $tc = $stmt->fetch();
    $assigned_to = $tc ? $tc['id'] : null;

    $stmt2 = $db->prepare(
        "INSERT INTO students (name,mobile,present_college,college_type,address,assigned_to,created_by)
         VALUES (?,?,?,?,?,?,?) RETURNING id"
    );
    $stmt2->execute([
        sanitize($data['name']),
        sanitize($data['mobile']),
        sanitize($data['present_college'] ?? ''),
        $data['college_type'] ?? 'Other',
        sanitize($data['address'] ?? ''),
        $assigned_to,
        $_SESSION['user_id']
    ]);
    $row = $stmt2->fetch();
    jsonResponse(['success' => true, 'student_id' => $row['id'], 'assigned_to' => $assigned_to]);
}

function listStudents() {
    if ($_SESSION['role'] !== 'admin') jsonResponse(['error' => 'Forbidden'], 403);
    $db = getDB();

    $where = '1=1';
    $params = [];
    if (!empty($_GET['status'])) {
        $where .= ' AND s.status=?';
        $params[] = $_GET['status'];
    }
    if (!empty($_GET['assigned_to'])) {
        $where .= ' AND s.assigned_to=?';
        $params[] = intval($_GET['assigned_to']);
    }
    if (!empty($_GET['search'])) {
        $where .= " AND (s.name ILIKE ? OR s.mobile ILIKE ?)";
        $params[] = '%'.$_GET['search'].'%';
        $params[] = '%'.$_GET['search'].'%';
    }

    $stmt = $db->prepare(
        "SELECT s.*, u.name as assigned_name,
            (SELECT f.call_status FROM feedback f WHERE f.student_id=s.id ORDER BY f.created_at DESC LIMIT 1) as last_feedback
         FROM students s
         LEFT JOIN users u ON u.id=s.assigned_to
         WHERE $where
         ORDER BY s.created_at DESC"
    );
    $stmt->execute($params);
    jsonResponse($stmt->fetchAll());
}

function myStudentList() {
    $uid = $_SESSION['user_id'];
    $db  = getDB();

    // Get reminders for today
    $today = date('Y-m-d');
    $remStmt = $db->prepare(
        "SELECT r.student_id FROM reminders r
         WHERE r.telecaller_id=? AND r.reminder_date<=? AND r.is_notified=FALSE"
    );
    $remStmt->execute([$uid, $today]);
    $reminderIds = array_column($remStmt->fetchAll(), 'student_id');

    // Mark reminders as notified
    if ($reminderIds) {
        $placeholders = implode(',', array_fill(0, count($reminderIds), '?'));
        $markStmt = $db->prepare(
            "UPDATE reminders SET is_notified=TRUE WHERE telecaller_id=? AND student_id IN ($placeholders)"
        );
        $markStmt->execute(array_merge([$uid], $reminderIds));
    }

    $stmt = $db->prepare(
        "SELECT s.*,
            (SELECT f.call_status FROM feedback f WHERE f.student_id=s.id AND f.telecaller_id=? ORDER BY f.created_at DESC LIMIT 1) as last_feedback,
            (SELECT f.created_at FROM feedback f WHERE f.student_id=s.id AND f.telecaller_id=? ORDER BY f.created_at DESC LIMIT 1) as last_call,
            (SELECT r.reminder_date FROM reminders r WHERE r.student_id=s.id AND r.telecaller_id=? ORDER BY r.reminder_date DESC LIMIT 1) as reminder_date
         FROM students s
         WHERE s.assigned_to=?
         ORDER BY s.created_at DESC"
    );
    $stmt->execute([$uid, $uid, $uid, $uid]);
    $students = $stmt->fetchAll();

    // Tag reminder students
    foreach ($students as &$s) {
        $s['has_reminder_today'] = in_array($s['id'], $reminderIds);
    }

    jsonResponse(['students' => $students, 'reminder_count' => count($reminderIds)]);
}

function studentDetail() {
    $id = intval($_GET['id'] ?? 0);
    if (!$id) jsonResponse(['error' => 'ID required'], 400);
    $db = getDB();

    $stmt = $db->prepare(
        "SELECT s.*, u.name as assigned_name FROM students s
         LEFT JOIN users u ON u.id=s.assigned_to WHERE s.id=?"
    );
    $stmt->execute([$id]);
    $student = $stmt->fetch();
    if (!$student) jsonResponse(['error' => 'Not found'], 404);

    $fStmt = $db->prepare(
        "SELECT f.*, u.name as caller_name FROM feedback f
         LEFT JOIN users u ON u.id=f.telecaller_id
         WHERE f.student_id=? ORDER BY f.created_at DESC"
    );
    $fStmt->execute([$id]);
    $student['feedback_history'] = $fStmt->fetchAll();

    jsonResponse($student);
}

function assignStudent() {
    if ($_SESSION['role'] !== 'admin') jsonResponse(['error' => 'Forbidden'], 403);
    $data = json_decode(file_get_contents('php://input'), true);
    $student_id = intval($data['student_id'] ?? 0);
    $user_id    = intval($data['user_id'] ?? 0);
    if (!$student_id || !$user_id) jsonResponse(['error' => 'Required fields missing'], 400);

    $db = getDB();
    $stmt = $db->prepare("UPDATE students SET assigned_to=?, updated_at=NOW() WHERE id=?");
    $stmt->execute([$user_id, $student_id]);
    jsonResponse(['success' => true]);
}

function autoAssign() {
    if ($_SESSION['role'] !== 'admin') jsonResponse(['error' => 'Forbidden'], 403);
    $db = getDB();

    // Get unassigned students
    $stmt = $db->query("SELECT id FROM students WHERE assigned_to IS NULL ORDER BY created_at ASC");
    $unassigned = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (!$unassigned) jsonResponse(['success' => true, 'assigned' => 0]);

    // Get telecallers ordered by fewest assigned
    $tcStmt = $db->query(
        "SELECT u.id FROM users u LEFT JOIN students s ON s.assigned_to=u.id
         WHERE u.role='telecaller' GROUP BY u.id ORDER BY COUNT(s.id) ASC"
    );
    $telecallers = $tcStmt->fetchAll(PDO::FETCH_COLUMN);
    if (!$telecallers) jsonResponse(['error' => 'No telecallers available'], 400);

    $upd = $db->prepare("UPDATE students SET assigned_to=?, updated_at=NOW() WHERE id=?");
    $i = 0;
    foreach ($unassigned as $sid) {
        $tc = $telecallers[$i % count($telecallers)];
        $upd->execute([$tc, $sid]);
        $i++;
    }
    jsonResponse(['success' => true, 'assigned' => $i]);
}

function exportExcel() {
    if ($_SESSION['role'] !== 'admin') jsonResponse(['error' => 'Forbidden'], 403);
    $db = getDB();

    $stmt = $db->query(
        "SELECT s.name,s.mobile,s.present_college,s.college_type,s.address,s.status,
            u.name as assigned_to,s.created_at,
            (SELECT f.call_status FROM feedback f WHERE f.student_id=s.id ORDER BY f.created_at DESC LIMIT 1) as last_feedback
         FROM students s LEFT JOIN users u ON u.id=s.assigned_to
         ORDER BY s.created_at DESC"
    );
    $rows = $stmt->fetchAll();

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="students_export_'.date('Ymd').'.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Name','Mobile','College','Type','Address','Status','Assigned To','Created At','Last Feedback']);
    foreach ($rows as $r) {
        fputcsv($out, array_values($r));
    }
    fclose($out);
    exit;
}

function summaryStats() {
    if ($_SESSION['role'] !== 'admin') jsonResponse(['error' => 'Forbidden'], 403);
    $db = getDB();
    $stmt = $db->query(
        "SELECT
            COUNT(*) as total,
            SUM(CASE WHEN status='accepted' THEN 1 ELSE 0 END) as accepted,
            SUM(CASE WHEN status='rejected' THEN 1 ELSE 0 END) as rejected,
            SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status='callback' THEN 1 ELSE 0 END) as callback,
            SUM(CASE WHEN assigned_to IS NULL THEN 1 ELSE 0 END) as unassigned
         FROM students"
    );
    jsonResponse($stmt->fetch());
}
