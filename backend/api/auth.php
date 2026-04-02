<?php
// backend/api/auth.php
// ── Auth API: login | register | logout | me ──────────────────

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/session.php';

$action = $_GET['action'] ?? '';

// ── ME (check session) ────────────────────────────────────────
if ($action === 'me') {
    $user = getSessionUser();
    echo json_encode($user ? ['success' => true, 'user' => $user] : ['success' => false]);
    exit;
}

// ── LOGOUT ────────────────────────────────────────────────────
if ($action === 'logout') {
    session_destroy();
    echo json_encode(['success' => true]);
    exit;
}

// ── POST actions only below ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?? [];

// ── LOGIN ─────────────────────────────────────────────────────
if ($action === 'login') {
    $email = trim($data['email'] ?? '');
    $pass  = $data['password'] ?? '';

    if (!$email || !$pass) {
        echo json_encode(['success' => false, 'message' => 'Email and password required.']);
        exit;
    }

    $db   = getDB();
    $stmt = $db->prepare('SELECT id, name, email, password, type FROM users WHERE email = ? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row || !password_verify($pass, $row['password'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
        exit;
    }

    $_SESSION['user_id']    = $row['id'];
    $_SESSION['user_name']  = $row['name'];
    $_SESSION['user_email'] = $row['email'];
    $_SESSION['user_type']  = $row['type'];

    echo json_encode(['success' => true, 'user' => [
        'id'   => $row['id'],
        'name' => $row['name'],
        'type' => $row['type'],
    ]]);
    exit;
}

// ── REGISTER ──────────────────────────────────────────────────
if ($action === 'register') {
    $name    = trim($data['name']     ?? '');
    $email   = trim($data['email']    ?? '');
    $pass    = $data['password']      ?? '';
    $type    = $data['type']          ?? 'customer';   // customer | provider
    $phone   = trim($data['phone']    ?? '');
    $bizName = trim($data['biz_name'] ?? '');
    $catId   = (int)($data['category_id'] ?? 1);
    $exp     = (int)($data['experience']  ?? 1);
    $price   = (float)($data['price']     ?? 0);

    // Basic validation
    if (!$name || !$email || strlen($pass) < 6) {
        echo json_encode(['success' => false, 'message' => 'Fill all required fields (password min 6 chars).']);
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
        exit;
    }
    if (!in_array($type, ['customer', 'provider'])) $type = 'customer';

    $db = getDB();

    // Check duplicate email
    $stmt = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already registered.']);
        $stmt->close(); exit;
    }
    $stmt->close();

    // Insert user
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $stmt = $db->prepare('INSERT INTO users (name, email, password, phone, type) VALUES (?,?,?,?,?)');
    $stmt->bind_param('sssss', $name, $email, $hash, $phone, $type);
    $stmt->execute();
    $userId = $db->insert_id;
    $stmt->close();

    // If provider, insert provider row
    if ($type === 'provider') {
        if (!$bizName) $bizName = $name . "'s Services";
        $stmt = $db->prepare('INSERT INTO providers (user_id, biz_name, category_id, experience, price) VALUES (?,?,?,?,?)');
        $stmt->bind_param('iiids', $userId, $catId, $exp, $price, $bizName);
        $stmt->execute();
        $stmt->close();
    }

    echo json_encode(['success' => true, 'message' => 'Account created! Please login.']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action.']);
