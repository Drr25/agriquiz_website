<?php
header('Content-Type: application/json');
session_start();

// ── DB connection
$db = new mysqli('sql306.infinityfree.com', 'if0_42619600', 'MnbvcxzQwerty', 'if0_42619600_agriquiz');
if ($db->connect_error) {
    http_response_code(500);
    die(json_encode(['success' => false, 'message' => 'DB error: ' . $db->connect_error]));
}

// ── Ensure the settings table exists ──────
$db->query("
    CREATE TABLE IF NOT EXISTS app_settings (
        setting_key   VARCHAR(100) PRIMARY KEY,
        setting_value TEXT NOT NULL,
        updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )
");

$action = $_GET['action'] ?? 'get';

// ── GET ────────────────────
if ($action === 'get') {
    $res = $db->query("SELECT setting_value FROM app_settings WHERE setting_key = 'passing_rate'");
    $row = $res ? $res->fetch_assoc() : null;
    $rate = $row ? (int)$row['setting_value'] : 75; // default 75
    echo json_encode(['success' => true, 'passing_rate' => $rate]);
    $db->close();
    exit;
}

// ── SAVE (admin only) ─────────────────────────────────────────────────────────
if ($action === 'save') {
    // Only admins may change this
    if (!isset($_SESSION['email'])) {
        http_response_code(403);
        die(json_encode(['success' => false, 'message' => 'Unauthorized']));
    }

    $body = json_decode(file_get_contents('php://input'), true);
    $rate = isset($body['passing_rate']) ? intval($body['passing_rate']) : null;

    if ($rate === null || $rate < 1 || $rate > 100) {
        http_response_code(400);
        die(json_encode(['success' => false, 'message' => 'passing_rate must be 1–100']));
    }

    $stmt = $db->prepare("
        INSERT INTO app_settings (setting_key, setting_value)
        VALUES ('passing_rate', ?)
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
    ");
    $rateStr = (string)$rate;
    $stmt->bind_param('s', $rateStr);
    $ok = $stmt->execute();
    $stmt->close();
    $db->close();

    echo json_encode(['success' => $ok]);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Unknown action']);
$db->close();