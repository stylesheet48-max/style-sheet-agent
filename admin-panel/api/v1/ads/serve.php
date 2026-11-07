<?php
/**
 * STYLE SHEET AGENT - Ads Delivery API
 * Endpoint: GET /api/v1/ads/serve.php?placement=chat_input|chat_feed|marketplace
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../../config/config.php';

try {
    $pdo = getDBConnection();

    // Check user plan from Bearer token
    $userPlan = 'free';
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $tokenPayload = verifyUserToken($authHeader);

    if ($tokenPayload) {
        $uId = (int)$tokenPayload['sub'];
        $uRow = $pdo->query("SELECT plan_id FROM users WHERE id = {$uId}")->fetch();
        if ($uRow) {
            $userPlan = $uRow['plan_id'];
        }
    }

    $placement = trim($_GET['placement'] ?? 'chat_input');

    // Fetch active campaigns matching the placement
    $stmt = $pdo->prepare("
        SELECT id, campaign_id, title, description, image_url, destination_url, placements_json, eligible_plans_json, frequency_cap
        FROM ad_campaigns
        WHERE status = 'active'
          AND (start_date IS NULL OR start_date <= date('now'))
          AND (end_date IS NULL OR end_date >= date('now'))
        ORDER BY RANDOM()
        LIMIT 1
    ");
    $stmt->execute();
    $ad = $stmt->fetch();

    if (!$ad) {
        echo json_encode(['status' => 'empty', 'ad' => null]);
        exit;
    }

    $placements = json_decode($ad['placements_json'] ?? '[]', true) ?: [];
    $plans = json_decode($ad['eligible_plans_json'] ?? '[]', true) ?: [];

    if (!in_array($placement, $placements) || !in_array($userPlan, $plans)) {
        echo json_encode(['status' => 'empty', 'ad' => null]);
        exit;
    }

    // Auto increment impression counter
    $pdo->prepare("UPDATE ad_campaigns SET impressions_count = impressions_count + 1 WHERE id = ?")->execute([$ad['id']]);

    echo json_encode([
        'status' => 'success',
        'ad' => [
            'id' => (int)$ad['id'],
            'campaign_id' => $ad['campaign_id'],
            'title' => $ad['title'],
            'description' => $ad['description'],
            'image_url' => $ad['image_url'],
            'destination_url' => $ad['destination_url'],
            'placement' => $placement,
            'sponsored_label' => 'Sponsored'
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
