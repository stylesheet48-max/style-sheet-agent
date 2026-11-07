<?php
/**
 * STYLE SHEET AGENT - In-App Update API Endpoint
 * Endpoint: GET /api/v1/update.php
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/../../config/config.php';

try {
    $pdo = getDBConnection();

    // Fetch the latest active release
    $stmt = $pdo->query("
        SELECT id, version, version_code, apk_url, release_notes, min_supported_version, is_force_update, created_at
        FROM app_releases
        WHERE is_active = 1
        ORDER BY version_code DESC, id DESC
        LIMIT 1
    ");
    $release = $stmt->fetch();

    if (!$release) {
        echo json_encode([
            'status' => 'ok',
            'update_available' => false,
            'message' => 'No active release found.'
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }

    $currentVersion = $_GET['current_version'] ?? '0.0.0';
    $currentCode = (int)($_GET['version_code'] ?? 0);

    $updateAvailable = ($currentCode > 0)
        ? ($release['version_code'] > $currentCode)
        : version_compare($release['version'], $currentVersion, '>');

    $forceUpdate = (bool)$release['is_force_update'];
    if (version_compare($currentVersion, $release['min_supported_version'], '<')) {
        $forceUpdate = true;
    }

    echo json_encode([
        'status' => 'success',
        'package_name' => PACKAGE_NAME,
        'app_name' => APP_NAME,
        'update_available' => $updateAvailable,
        'force_update' => $forceUpdate,
        'latest_version' => $release['version'],
        'latest_version_code' => (int)$release['version_code'],
        'download_url' => $release['apk_url'],
        'release_notes' => $release['release_notes'],
        'min_supported_version' => $release['min_supported_version'],
        'published_at' => $release['created_at']
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to query release info: ' . $e->getMessage()
    ]);
}
