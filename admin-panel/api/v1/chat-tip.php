<?php
/**
 * STYLE SHEET AGENT - Dynamic Chat Composer Tip API
 * Endpoint: GET /api/v1/chat-tip.php
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../config/config.php';

try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT text, link_url
        FROM chat_tips
        WHERE is_active = 1
        ORDER BY id DESC
        LIMIT 1
    ");
    $tip = $stmt->fetch();

    if ($tip) {
        $part = ['text' => $tip['text']];
        if (!empty($tip['link_url'])) {
            $part['url'] = $tip['link_url'];
        }
        echo json_encode([
            'version' => 1,
            'enabled' => true,
            'parts' => [$part]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    } else {
        echo json_encode([
            'version' => 1,
            'enabled' => false,
            'parts' => []
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['version' => 1, 'enabled' => false, 'parts' => []]);
}
