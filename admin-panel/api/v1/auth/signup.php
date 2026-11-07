<?php
/**
 * STYLE SHEET AGENT - Mobile Client Sign Up API
 * Endpoint: POST /api/v1/auth/signup.php
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

    $fullName = trim($input['full_name'] ?? '');
    $email = strtolower(trim($input['email'] ?? ''));
    $password = $input['password'] ?? '';
    $termsAccepted = !empty($input['terms_accepted']);

    if (empty($fullName) || empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Full name, email, and password are required.']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid email address format.']);
        exit;
    }

    if (strlen($password) < 6) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Password must be at least 6 characters long.']);
        exit;
    }

    if (!$termsAccepted) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'You must accept the Terms of Service to create an account.']);
        exit;
    }

    $pdo = getDBConnection();

    // Check if email already registered
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $checkStmt->execute([$email]);
    if ($checkStmt->fetch()) {
        http_response_code(409);
        echo json_encode(['status' => 'error', 'message' => 'An account with this email already exists.']);
        exit;
    }

    $uid = 'usr_' . bin2hex(random_bytes(8));
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
    $planExpiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));

    $stmt = $pdo->prepare("
        INSERT INTO users (uid, email, password_hash, full_name, plan_id, plan_expires_at, status, email_verified)
        VALUES (?, ?, ?, ?, 'free', ?, 'active', 1)
    ");
    $stmt->execute([$uid, $email, $passwordHash, $fullName, $planExpiresAt]);
    $userId = (int)$pdo->lastInsertId();

    $userData = [
        'id' => $userId,
        'uid' => $uid,
        'email' => $email,
        'full_name' => $fullName,
        'avatar_url' => null,
        'plan_id' => 'free',
        'plan_expires_at' => $planExpiresAt,
        'status' => 'active',
        'created_at' => date('Y-m-d H:i:s')
    ];

    $token = generateUserToken($userData);
    auditLog($pdo, $userId, 'USER_SIGNUP', "New user registered: {$email}");

    echo json_encode([
        'status' => 'success',
        'message' => 'Account created successfully!',
        'token' => $token,
        'user' => $userData
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
}
