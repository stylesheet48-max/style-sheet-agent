<?php
/**
 * STYLE SHEET AGENT - Marketplace Packages API
 * Endpoint: GET /api/v1/marketplace/packages.php
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

    // Determine current user plan from Bearer token
    $userPlan = 'free';
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $tokenPayload = verifyUserToken($authHeader);

    if ($tokenPayload) {
        $uId = (int)$tokenPayload['sub'];
        $uRow = $pdo->query("SELECT plan_id, plan_expires_at FROM users WHERE id = {$uId}")->fetch();
        if ($uRow) {
            $userPlan = $uRow['plan_id'];
            if ($uRow['plan_expires_at'] && strtotime($uRow['plan_expires_at']) < time() && $userPlan !== 'free') {
                $userPlan = 'free';
            }
        }
    }

    $category = trim($_GET['category'] ?? '');
    $search = trim($_GET['q'] ?? '');
    $planFilter = trim($_GET['plan'] ?? '');

    $sql = "SELECT * FROM marketplace_packages WHERE is_published = 1";
    $params = [];

    if (!empty($category) && $category !== 'All') {
        $sql .= " AND category = ?";
        $params[] = $category;
    }

    if (!empty($search)) {
        $sql .= " AND (title LIKE ? OR description LIKE ? OR author LIKE ?)";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
    }

    $sql .= " ORDER BY id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $packages = $stmt->fetchAll();

    $output = [];
    foreach ($packages as $pkg) {
        $eligiblePlans = json_decode($pkg['eligible_plans_json'] ?? '["free"]', true) ?: ['free'];
        $isEligible = in_array($userPlan, $eligiblePlans) || $userPlan === 'ultra';

        if (!empty($planFilter) && $planFilter !== 'all' && !in_array($planFilter, $eligiblePlans)) {
            continue;
        }

        $output[] = [
            'id' => (int)$pkg['id'],
            'package_id' => $pkg['package_id'],
            'category' => $pkg['category'],
            'title' => $pkg['title'],
            'description' => $pkg['description'],
            'version' => $pkg['version'],
            'author' => $pkg['author'],
            'icon_name' => $pkg['icon_name'],
            'preview_image_url' => $pkg['preview_image_url'],
            'eligible_plans' => $eligiblePlans,
            'is_eligible' => $isEligible,
            'permissions' => json_decode($pkg['permissions_json'] ?? '[]', true),
            'dependencies' => json_decode($pkg['dependencies_json'] ?? '[]', true),
            'download_url' => $pkg['download_url'],
            'install_count' => (int)$pkg['install_count'],
            'published_at' => $pkg['created_at']
        ];
    }

    echo json_encode([
        'status' => 'success',
        'user_plan' => $userPlan,
        'count' => count($output),
        'packages' => $output
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
