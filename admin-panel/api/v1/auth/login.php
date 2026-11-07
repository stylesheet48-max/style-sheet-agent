<?php
/**
 * STYLE SHEET AGENT - Mobile Client Login API
 * Endpoint: POST /api/v1/auth/login.php
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

    $email = strtolower(trim($input['email'] ?? ''));
    $password = $input['password'] ?? '';

    if (empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Email and password are required.']);
        exit;
    }

    $pdo = getDBConnection();

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        auditLog($pdo, null, 'AUTH_FAILED', "Failed login attempt for: {$email}");
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Invalid email or password.']);
        exit;
    }

    if ($user['status'] === 'suspended') {
        auditLog($pdo, $user['id'], 'AUTH_SUSPENDED', "Suspended account attempted login: {$email}");
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Your account has been suspended by the administrator.']);
        exit;
    }

    $userData = [
        'id' => (int)$user['id'],
        'uid' => $user['uid'],
        'email' => $user['email'],
        'full_name' => $user['full_name'],
        'avatar_url' => $user['avatar_url'],
        'plan_id' => $user['plan_id'],
        'plan_expires_at' => $user['plan_expires_at'],
        'status' => $user['status'],
        'created_at' => $user['created_at']
    ];

    $token = generateUserToken($userData);
    auditLog($pdo, $user['id'], 'AUTH_SUCCESS', "User logged in: {$email}");

    echo json_encode([
        'status' => 'success',
        'message' => 'Login successful',
        'token' => $token,
        'user' => $userData
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
}
