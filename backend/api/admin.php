<?php
// backend/api/admin.php
// ── Admin API: dashboard stats | bookings table | providers ───

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/session.php';

requireRole('admin');
$db = getDB();

$action = $_GET['action'] ?? 'stats';

// ── STATS ─────────────────────────────────────────────────────
if ($action === 'stats') {
    $customers = $db->query("SELECT COUNT(*) c FROM users WHERE type='customer'")->fetch_assoc()['c'];
    $providers = $db->query("SELECT COUNT(*) c FROM providers")->fetch_assoc()['c'];
    $bookings  = $db->query("SELECT COUNT(*) c FROM bookings")->fetch_assoc()['c'];
    $pending   = $db->query("SELECT COUNT(*) c FROM bookings WHERE status='pending'")->fetch_assoc()['c'];

    echo json_encode(['success' => true, 'stats' => compact('customers', 'providers', 'bookings', 'pending')]);
    exit;
}

// ── ALL BOOKINGS ──────────────────────────────────────────────
if ($action === 'bookings') {
    $rows = $db->query("SELECT b.id, b.service, b.book_date, b.price, b.status,
                               p.biz_name AS provider_name,
                               u.name     AS customer_name
                        FROM bookings b
                        JOIN providers p ON p.id = b.provider_id
                        JOIN users u ON u.id = b.customer_id
                        ORDER BY b.created_at DESC")
               ->fetch_all(MYSQLI_ASSOC);

    echo json_encode(['success' => true, 'bookings' => $rows]);
    exit;
}

// ── ALL PROVIDERS ─────────────────────────────────────────────
if ($action === 'providers') {
    $CATEGORIES = [1=>'Plumbing',2=>'Electrical',3=>'Cleaning',4=>'Painting',5=>'Tutoring'];
    $rows = $db->query("SELECT p.id, p.biz_name, p.category_id, p.experience, p.rating,
                               u.name AS owner_name, u.email, u.phone
                        FROM providers p JOIN users u ON u.id = p.user_id
                        ORDER BY p.id")
               ->fetch_all(MYSQLI_ASSOC);
    foreach ($rows as &$row) {
        $row['category_name'] = $CATEGORIES[$row['category_id']] ?? 'Other';
    }
    echo json_encode(['success' => true, 'providers' => $rows]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action.']);
