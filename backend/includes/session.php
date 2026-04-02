<?php
// backend/includes/session.php
// ── Session Helper ────────────────────────────────────────────

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function getSessionUser(): ?array {
    if (!isLoggedIn()) return null;
    return [
        'id'   => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'],
        'type' => $_SESSION['user_type'],
        'email'=> $_SESSION['user_email'],
    ];
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Not logged in.', 'redirect' => 'login.html']);
        exit;
    }
}

function requireRole(string $role): void {
    requireLogin();
    if ($_SESSION['user_type'] !== $role) {
        echo json_encode(['success' => false, 'message' => 'Access denied.']);
        exit;
    }
}
