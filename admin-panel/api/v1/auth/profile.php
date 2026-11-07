<?php
/**
 * STYLE SHEET AGENT - Mobile Client Profile & Subscription Verification API
 * Endpoint: GET / POST /api/v1/auth/profile.php
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
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
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized: Invalid or expired Bearer token.']);
        exit;
    }

    $pdo = getDBConnection();
    $userId = (int)$tokenPayload['sub'];

    // Handle Profile Update (POST)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $fullName = trim($input['full_name'] ?? '');
        $avatarUrl = trim($input['avatar_url'] ?? '');
        $newPassword = $input['new_password'] ?? '';
        $currentPassword = $input['current_password'] ?? '';

        if (!empty($newPassword)) {
            $userRow = $pdo->query("SELECT password_hash FROM users WHERE id = {$userId}")->fetch();
            if (!$userRow || !password_verify($currentPassword, $userRow['password_hash'])) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Current password incorrect.']);
                exit;
            }
            $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
            $pdo->prepare("UPDATE users SET password_hash = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$newHash, $userId]);
            auditLog($pdo, $userId, 'PASSWORD_CHANGE', 'User changed their password.');
        }

        if (!empty($fullName)) {
            $pdo->prepare("UPDATE users SET full_name = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$fullName, $userId]);
        }
        if (!empty($avatarUrl)) {
            $pdo->prepare("UPDATE users SET avatar_url = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$avatarUrl, $userId]);
        }
    }

    // Fetch live user data and subscription plan from DB
    $stmt = $pdo->prepare("
        SELECT u.id, u.uid, u.email, u.full_name, u.avatar_url, u.plan_id, u.plan_expires_at, u.status, u.created_at,
               p.name AS plan_name, p.description AS plan_description, p.ai_models_json, p.usage_limits_json
        FROM users u
        LEFT JOIN subscription_plans p ON u.plan_id = p.plan_key
        WHERE u.id = ?
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'User account not found.']);
        exit;
    }

    // Verify subscription expiry
    $isExpired = false;
    if ($user['plan_expires_at'] && strtotime($user['plan_expires_at']) < time() && $user['plan_id'] !== 'free') {
        $isExpired = true;
        // Downgrade to free if expired
        $pdo->prepare("UPDATE users SET plan_id = 'free' WHERE id = ?")->execute([$userId]);
        $user['plan_id'] = 'free';
        auditLog($pdo, $userId, 'PLAN_EXPIRED', 'Subscription expired; downgraded to free plan.');
    }

    echo json_encode([
        'status' => 'success',
        'user' => [
            'id' => (int)$user['id'],
            'uid' => $user['uid'],
            'email' => $user['email'],
            'full_name' => $user['full_name'],
            'avatar_url' => $user['avatar_url'],
            'plan_id' => $user['plan_id'],
            'plan_name' => $user['plan_name'] ?? ucfirst($user['plan_id']),
            'plan_expires_at' => $user['plan_expires_at'],
            'is_expired' => $isExpired,
            'status' => $user['status'],
            'created_at' => $user['created_at'],
            'ai_models' => json_decode($user['ai_models_json'] ?? '[]', true),
            'usage_limits' => json_decode($user['usage_limits_json'] ?? '{}', true)
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
}
