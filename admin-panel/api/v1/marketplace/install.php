<?php
/**
 * STYLE SHEET AGENT - Marketplace Package Install API
 * Endpoint: POST /api/v1/marketplace/install.php
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
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $tokenPayload = verifyUserToken($authHeader);

    if (!$tokenPayload) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Authentication required to import packages.']);
        exit;
    }

    $pdo = getDBConnection();
    $userId = (int)$tokenPayload['sub'];

    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $packageId = trim($input['package_id'] ?? '');

    if (empty($packageId)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'package_id is required.']);
        exit;
    }

    // Fetch user and package
    $user = $pdo->query("SELECT * FROM users WHERE id = {$userId}")->fetch();
    $stmt = $pdo->prepare("SELECT * FROM marketplace_packages WHERE package_id = ? AND is_published = 1");
    $stmt->execute([$packageId]);
    $package = $stmt->fetch();

    if (!$package) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Package not found or inactive.']);
        exit;
    }

    // Verify Plan Entitlement on the server
    $userPlan = $user['plan_id'] ?? 'free';
    if ($user['plan_expires_at'] && strtotime($user['plan_expires_at']) < time() && $userPlan !== 'free') {
        $userPlan = 'free';
    }

    $eligiblePlans = json_decode($package['eligible_plans_json'] ?? '["free"]', true) ?: ['free'];
    $isEligible = in_array($userPlan, $eligiblePlans) || $userPlan === 'ultra';

    if (!$isEligible) {
        auditLog($pdo, $userId, 'PACKAGE_IMPORT_DENIED', "User on {$userPlan} denied access to {$packageId}");
        http_response_code(403);
        echo json_encode([
            'status' => 'error',
            'message' => "This package requires a " . implode(' or ', $eligiblePlans) . " plan. Your current plan is " . ucfirst($userPlan) . ".",
            'required_plans' => $eligiblePlans,
            'current_plan' => $userPlan
        ]);
        exit;
    }

    // Record installation
    $instStmt = $pdo->prepare("
        INSERT INTO package_installations (user_id, package_id, installed_version, status, backup_manifest)
        VALUES (?, ?, ?, 'active', ?)
    ");
    $instStmt->execute([$userId, $packageId, $package['version'], $package['manifest_json'] ?? '']);

    // Increment download count
    $pdo->prepare("UPDATE marketplace_packages SET install_count = install_count + 1 WHERE id = ?")->execute([$package['id']]);

    auditLog($pdo, $userId, 'PACKAGE_INSTALLED', "Installed package: {$package['title']} (v{$package['version']})");

    echo json_encode([
        'status' => 'success',
        'message' => "Package {$package['title']} imported successfully!",
        'package' => [
            'package_id' => $package['package_id'],
            'title' => $package['title'],
            'category' => $package['category'],
            'version' => $package['version'],
            'download_url' => $package['download_url'],
            'manifest' => json_decode($package['manifest_json'] ?? '{}', true)
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
