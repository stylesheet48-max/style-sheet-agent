<?php
/**
 * STYLE SHEET AGENT - Dynamic Starter Prompts API
 * Endpoint: GET /api/v1/starter-prompts.php
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../config/config.php';

try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT label, prompt
        FROM starter_prompts
        WHERE is_active = 1
        ORDER BY sort_order ASC, id ASC
        LIMIT 6
    ");
    $rows = $stmt->fetchAll();

    $prompts = [];
    foreach ($rows as $row) {
        $prompts[] = [
            'label' => $row['label'],
            'prompt' => $row['prompt']
        ];
    }

    echo json_encode([
        'version' => 1,
        'enabled' => !empty($prompts),
        'prompts' => $prompts
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['version' => 1, 'enabled' => false, 'prompts' => []]);
}
