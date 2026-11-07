<?php
/**
 * STYLE SHEET AGENT - Ads Click/Impression Tracking API
 * Endpoint: POST /api/v1/ads/track.php
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../../config/config.php';

try {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $campaignId = trim($input['campaign_id'] ?? '');
    $eventType = trim($input['event'] ?? 'impression');
    $placement = trim($input['placement'] ?? 'unknown');

    if (empty($campaignId)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'campaign_id is required']);
        exit;
    }

    $pdo = getDBConnection();
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $tokenPayload = verifyUserToken($authHeader);
    $userId = $tokenPayload ? (int)$tokenPayload['sub'] : null;

    if ($eventType === 'click') {
        $pdo->prepare("UPDATE ad_campaigns SET clicks_count = clicks_count + 1 WHERE campaign_id = ?")->execute([$campaignId]);
    }

    $stmt = $pdo->prepare("
        INSERT INTO ad_events (campaign_id, user_id, event_type, placement, ip_address)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$campaignId, $userId, $eventType, $placement, $_SERVER['REMOTE_ADDR'] ?? '']);

    echo json_encode(['status' => 'success', 'tracked' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
