<?php
// backend/api/providers.php
// ── Providers API: list | detail ─────────────────────────────

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/db.php';

$action = $_GET['action'] ?? 'list';
$db     = getDB();

// Categories map (no separate table — keep it simple)
$CATEGORIES = [
    1 => ['name' => 'Plumbing',   'icon' => 'fa-faucet',        'color' => 'ic-blue'],
    2 => ['name' => 'Electrical', 'icon' => 'fa-bolt',           'color' => 'ic-yellow'],
    3 => ['name' => 'Cleaning',   'icon' => 'fa-broom',          'color' => 'ic-green'],
    4 => ['name' => 'Painting',   'icon' => 'fa-paint-roller',   'color' => 'ic-purple'],
    5 => ['name' => 'Tutoring',   'icon' => 'fa-graduation-cap', 'color' => 'ic-red'],
];

// ── LIST all providers (with optional search/category filter) ─
if ($action === 'list') {
    $cat = (int)($_GET['cat'] ?? 0);
    $q   = trim($_GET['q']   ?? '');

    $sql  = 'SELECT p.id, p.biz_name, p.category_id, p.experience, p.price, p.rating,
                    u.name AS owner_name
             FROM providers p
             JOIN users u ON u.id = p.user_id
             WHERE 1=1';
    $params = [];
    $types  = '';

    if ($cat > 0) {
        $sql .= ' AND p.category_id = ?';
        $params[] = $cat; $types .= 'i';
    }
    if ($q !== '') {
        $like = "%$q%";
        $sql .= ' AND (p.biz_name LIKE ? OR u.name LIKE ?)';
        $params[] = $like; $params[] = $like;
        $types .= 'ss';
    }

    $stmt = $db->prepare($sql);
    if ($types) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Attach services + category info
    foreach ($rows as &$row) {
        $pid  = $row['id'];
        $s    = $db->prepare('SELECT name, price FROM services WHERE provider_id = ?');
        $s->bind_param('i', $pid);
        $s->execute();
        $row['services']  = $s->get_result()->fetch_all(MYSQLI_ASSOC);
        $row['category']  = $CATEGORIES[$row['category_id']] ?? ['name' => 'Other', 'icon' => 'fa-tools', 'color' => 'ic-blue'];
        $s->close();
    }

    echo json_encode(['success' => true, 'categories' => $CATEGORIES, 'providers' => $rows]);
    exit;
}

// ── DETAIL single provider ────────────────────────────────────
if ($action === 'detail') {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) { echo json_encode(['success' => false, 'message' => 'Provider ID required.']); exit; }

    $stmt = $db->prepare('SELECT p.id, p.biz_name, p.category_id, p.experience, p.price, p.rating,
                                 u.name AS owner_name
                          FROM providers p JOIN users u ON u.id = p.user_id
                          WHERE p.id = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) { echo json_encode(['success' => false, 'message' => 'Provider not found.']); exit; }

    $s = $db->prepare('SELECT name, price FROM services WHERE provider_id = ?');
    $s->bind_param('i', $id);
    $s->execute();
    $row['services'] = $s->get_result()->fetch_all(MYSQLI_ASSOC);
    $row['category'] = $CATEGORIES[$row['category_id']] ?? ['name' => 'Other'];
    $s->close();

    echo json_encode(['success' => true, 'provider' => $row]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action.']);
