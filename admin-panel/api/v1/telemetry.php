<?php
/**
 * STYLE SHEET AGENT - Telemetry & Device Ping API
 * Endpoint: POST /api/v1/telemetry.php
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../config/config.php';

try {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $deviceId = trim($input['device_id'] ?? '');

    if (empty($deviceId)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'device_id required']);
        exit;
    }

    $platform = $input['platform'] ?? 'android';
    $osVersion = $input['os_version'] ?? 'unknown';
    $appVersion = $input['app_version'] ?? APP_VERSION;
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';

    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        INSERT INTO device_telemetry (device_id, platform, os_version, app_version, ip_address, last_seen)
        VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
    ");
    $stmt->execute([$deviceId, $platform, $osVersion, $appVersion, $ipAddress]);

    echo json_encode(['status' => 'success', 'acknowledged' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
