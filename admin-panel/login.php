<?php
require_once __DIR__ . '/config/config.php';

$error = '';
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user'] = $user['username'];
        header('Location: index.php');
        exit;
    } else {
        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STYLE SHEET AGENT - Admin Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: radial-gradient(circle at 50% 20%, #0F172A 0%, #070B14 100%);
            min-height: 100vh;
        }
        .glass-panel {
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(56, 189, 248, 0.2);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6);
        }
    </style>
</head>
<body class="text-slate-100 flex items-center justify-center p-4">
    <div class="glass-panel w-full max-w-md p-8 rounded-3xl relative overflow-hidden">
        <div class="absolute -top-12 -left-12 w-36 h-36 bg-sky-500/10 rounded-full blur-2xl pointer-events-none"></div>
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-sky-500/10 border border-sky-400/30 mb-4 shadow-lg shadow-sky-500/20">
                <svg class="w-8 h-8 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </div>
            <h1 class="text-2xl font-bold tracking-wider text-white">STYLE SHEET AGENT</h1>
            <p class="text-xs uppercase tracking-widest text-sky-400 mt-1 font-semibold">Admin Command Center</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="bg-red-500/20 border border-red-500/50 text-red-200 text-sm px-4 py-3 rounded-xl mb-6">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-5">
            <div>
                <label class="block text-xs uppercase tracking-wider text-slate-400 mb-2 font-medium">Username</label>
                <input type="text" name="username" required value="admin"
                    class="w-full bg-slate-900/80 border border-slate-700/80 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-sky-400 focus:ring-1 focus:ring-sky-400 transition">
            </div>
            <div>
                <label class="block text-xs uppercase tracking-wider text-slate-400 mb-2 font-medium">Password</label>
                <input type="password" name="password" required placeholder="Default: admin123"
                    class="w-full bg-slate-900/80 border border-slate-700/80 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-sky-400 focus:ring-1 focus:ring-sky-400 transition">
            </div>
            <button type="submit"
                class="w-full bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-400 hover:to-blue-500 text-white font-semibold py-3.5 px-4 rounded-xl shadow-lg shadow-sky-500/25 transition duration-200">
                Sign In to Dashboard
            </button>
        </form>

        <p class="text-center text-xs text-slate-500 mt-6">
            Default Login: <code class="text-sky-400">admin</code> / <code class="text-sky-400">admin123</code>
        </p>
    </div>
</body>
</html>
