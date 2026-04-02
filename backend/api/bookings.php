<?php
// backend/api/bookings.php
// ── Bookings API: create | my-bookings | provider-requests | update-status ─

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/session.php';

$action = $_GET['action'] ?? '';
$db     = getDB();

// ── MY BOOKINGS (customer view) ───────────────────────────────
if ($action === 'my-bookings') {
    requireLogin();
    $uid    = $_SESSION['user_id'];
    $status = $_GET['status'] ?? 'all';

    $sql  = 'SELECT b.id, b.service, b.book_date, b.price, b.status, b.created_at,
                    p.biz_name AS provider_name
             FROM bookings b
             JOIN providers p ON p.id = b.provider_id
             WHERE b.customer_id = ?';
    $params = [$uid]; $types = 'i';

    if ($status !== 'all') {
        $sql .= ' AND b.status = ?';
        $params[] = $status; $types .= 's';
    }
    $sql .= ' ORDER BY b.created_at DESC';

    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode(['success' => true, 'bookings' => $rows]);
    exit;
}

// ── PROVIDER REQUESTS (provider view) ────────────────────────
if ($action === 'provider-requests') {
    requireLogin();
    requireRole('provider');
    $uid = $_SESSION['user_id'];
    $status = $_GET['status'] ?? 'all';

    // Get provider_id for this user
    $s = $db->prepare('SELECT id FROM providers WHERE user_id = ? LIMIT 1');
    $s->bind_param('i', $uid);
    $s->execute();
    $prow = $s->get_result()->fetch_assoc();
    $s->close();

    if (!$prow) { echo json_encode(['success' => true, 'requests' => []]); exit; }
    $pid = $prow['id'];

    $sql  = 'SELECT b.id, b.service, b.book_date, b.price, b.status, b.description,
                    u.name AS customer_name
             FROM bookings b
             JOIN users u ON u.id = b.customer_id
             WHERE b.provider_id = ?';
    $params = [$pid]; $types = 'i';

    if ($status !== 'all') {
        $sql .= ' AND b.status = ?';
        $params[] = $status; $types .= 's';
    }
    $sql .= ' ORDER BY b.created_at DESC';

    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Provider stats
    $st = $db->prepare('SELECT
        COUNT(*) AS total,
        SUM(status="pending")   AS pending,
        SUM(status="completed") AS completed,
        COALESCE(SUM(CASE WHEN status="completed" THEN price ELSE 0 END),0) AS earnings
        FROM bookings WHERE provider_id = ?');
    $st->bind_param('i', $pid);
    $st->execute();
    $stats = $st->get_result()->fetch_assoc();
    $st->close();

    echo json_encode(['success' => true, 'requests' => $rows, 'stats' => $stats]);
    exit;
}

// ── CREATE BOOKING ────────────────────────────────────────────
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    $uid  = $_SESSION['user_id'];
    $data = json_decode(file_get_contents('php://input'), true) ?? [];

    $providerId = (int)($data['provider_id'] ?? 0);
    $service    = trim($data['service']      ?? '');
    $bookDate   = trim($data['book_date']    ?? '');
    $desc       = trim($data['description']  ?? '');
    $price      = (float)($data['price']     ?? 0);

    if (!$providerId || !$service || !$bookDate) {
        echo json_encode(['success' => false, 'message' => 'Provider, service and date required.']);
        exit;
    }

    // Validate date is in future
    if ($bookDate < date('Y-m-d')) {
        echo json_encode(['success' => false, 'message' => 'Booking date must be today or later.']);
        exit;
    }

    $stmt = $db->prepare('INSERT INTO bookings (customer_id, provider_id, service, book_date, description, price)
                          VALUES (?,?,?,?,?,?)');
    $stmt->bind_param('iisssd', $uid, $providerId, $service, $bookDate, $desc, $price);
    $stmt->execute();
    $bookingId = $db->insert_id;
    $stmt->close();

    echo json_encode(['success' => true, 'booking_id' => $bookingId, 'message' => 'Booking created!']);
    exit;
}

// ── UPDATE STATUS (provider accepts/rejects/completes) ────────
if ($action === 'update-status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    requireRole('provider');
    $uid  = $_SESSION['user_id'];
    $data = json_decode(file_get_contents('php://input'), true) ?? [];

    $bookingId = (int)($data['booking_id'] ?? 0);
    $newStatus = $data['status'] ?? '';

    if (!in_array($newStatus, ['accepted', 'rejected', 'completed'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid status.']);
        exit;
    }

    // Ensure booking belongs to this provider
    $s = $db->prepare('SELECT b.id FROM bookings b
                       JOIN providers p ON p.id = b.provider_id
                       WHERE b.id = ? AND p.user_id = ? LIMIT 1');
    $s->bind_param('ii', $bookingId, $uid);
    $s->execute();
    if ($s->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Booking not found.']);
        $s->close(); exit;
    }
    $s->close();

    $stmt = $db->prepare('UPDATE bookings SET status = ? WHERE id = ?');
    $stmt->bind_param('si', $newStatus, $bookingId);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true, 'message' => 'Booking ' . $newStatus . '!']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action.']);
