<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

$pdo = getDBConnection();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = (int)($_POST['user_id'] ?? 0);

    if ($action === 'change_plan') {
        $newPlan = $_POST['plan_id'] ?? 'free';
        $durationDays = ($newPlan === 'ultra') ? 30 : (($newPlan === 'premium') ? 30 : 365);
        $expiry = date('Y-m-d H:i:s', strtotime("+{$durationDays} days"));
        $stmt = $pdo->prepare("UPDATE users SET plan_id = ?, plan_expires_at = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$newPlan, $expiry, $userId]);
        auditLog($pdo, $userId, 'ADMIN_PLAN_CHANGE', "Admin changed user #{$userId} plan to {$newPlan}");
        $message = "User #{$userId} plan updated to " . ucfirst($newPlan) . "!";
    } elseif ($action === 'toggle_status') {
        $newStatus = $_POST['status'] ?? 'active';
        $stmt = $pdo->prepare("UPDATE users SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$newStatus, $userId]);
        auditLog($pdo, $userId, 'ADMIN_STATUS_CHANGE', "Admin set user #{$userId} status to {$newStatus}");
        $message = "User status updated to {$newStatus}.";
    }
}

$search = trim($_GET['q'] ?? '');
if (!empty($search)) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email LIKE ? OR full_name LIKE ? OR uid LIKE ? ORDER BY id DESC");
    $stmt->execute(["%{$search}%", "%{$search}%", "%{$search}%"]);
    $users = $stmt->fetchAll();
} else {
    $users = $pdo->query("SELECT * FROM users ORDER BY id DESC LIMIT 50")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STYLE SHEET AGENT - User Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: radial-gradient(circle at 50% 10%, #0d1527 0%, #070B14 100%);
            min-height: 100vh;
        }
        .glass-card {
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(14px);
            border: 1px solid rgba(56, 189, 248, 0.15);
        }
    </style>
</head>
<body class="text-slate-100 flex flex-col min-h-screen">
    <!-- Top Nav -->
    <header class="border-b border-slate-800 bg-slate-950/80 backdrop-blur-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-sky-500/10 border border-sky-400/40 flex items-center justify-center text-sky-400 shadow-md shadow-sky-500/20 font-bold">
                    SS
                </div>
                <div>
                    <h1 class="font-bold text-lg text-white leading-tight">STYLE SHEET AGENT</h1>
                    <p class="text-xs text-sky-400 font-medium">User & Subscription Administration</p>
                </div>
            </div>
            <nav class="flex items-center space-x-6 text-sm">
                <a href="index.php" class="text-slate-400 hover:text-white transition">Dashboard</a>
                <a href="users.php" class="text-sky-400 font-semibold border-b-2 border-sky-400 pb-1">Users</a>
                <a href="marketplace.php" class="text-slate-400 hover:text-white transition">Marketplace</a>
                <a href="releases.php" class="text-slate-400 hover:text-white transition">Releases</a>
                <a href="prompts.php" class="text-slate-400 hover:text-white transition">Prompts</a>
                <a href="devices.php" class="text-slate-400 hover:text-white transition">Devices</a>
                <a href="logout.php" class="text-red-400 hover:text-red-300 font-medium transition">Logout</a>
            </nav>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-6 py-8 flex-1 w-full space-y-6">
        <?php if (!empty($message)): ?>
            <div class="bg-emerald-500/20 border border-emerald-500/50 text-emerald-200 text-sm px-4 py-3 rounded-2xl">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="glass-card p-6 md:p-8 rounded-3xl shadow-xl space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-white flex items-center gap-2">
                        <svg class="w-6 h-6 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        User Accounts & Plan Entitlements (<?= count($users) ?>)
                    </h2>
                    <p class="text-xs text-slate-400 mt-1">Manage user subscription tiers (Free, Premium, Ultra) and account status.</p>
                </div>
                <form method="GET" class="flex gap-2">
                    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search name, email, UID..."
                        class="bg-slate-900/80 border border-slate-700/80 rounded-xl px-4 py-2 text-white text-xs focus:outline-none focus:border-sky-400">
                    <button type="submit" class="px-4 py-2 bg-sky-500 hover:bg-sky-400 text-white rounded-xl text-xs font-semibold">
                        Search
                    </button>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="text-slate-400 uppercase tracking-wider border-b border-slate-800">
                        <tr>
                            <th class="py-3 px-4">User</th>
                            <th class="py-3 px-4">UID</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4">Current Plan</th>
                            <th class="py-3 px-4">Expires</th>
                            <th class="py-3 px-4">Registered</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/80">
                        <?php foreach ($users as $u): ?>
                            <tr class="hover:bg-slate-900/40 transition">
                                <td class="py-4 px-4">
                                    <div class="font-bold text-white text-sm"><?= htmlspecialchars($u['full_name']) ?></div>
                                    <div class="text-slate-400 text-xs"><?= htmlspecialchars($u['email']) ?></div>
                                </td>
                                <td class="py-4 px-4 font-mono text-slate-400 text-[11px]">
                                    <?= htmlspecialchars($u['uid']) ?>
                                </td>
                                <td class="py-4 px-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold <?= $u['status'] === 'active' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-red-500/20 text-red-400 border border-red-500/30' ?>">
                                        <?= strtoupper($u['status']) ?>
                                    </span>
                                </td>
                                <td class="py-4 px-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold <?= $u['plan_id'] === 'ultra' ? 'bg-purple-500/20 text-purple-300 border border-purple-500/30' : ($u['plan_id'] === 'premium' ? 'bg-sky-500/20 text-sky-400 border border-sky-400/30' : 'bg-slate-800 text-slate-400') ?>">
                                        <?= strtoupper($u['plan_id']) ?>
                                    </span>
                                </td>
                                <td class="py-4 px-4 text-slate-400">
                                    <?= $u['plan_expires_at'] ? htmlspecialchars(date('M d, Y', strtotime($u['plan_expires_at']))) : 'Never' ?>
                                </td>
                                <td class="py-4 px-4 text-slate-400">
                                    <?= htmlspecialchars(date('M d, Y', strtotime($u['created_at']))) ?>
                                </td>
                                <td class="py-4 px-4 text-right space-x-2">
                                    <!-- Change Plan Form -->
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="change_plan">
                                        <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                        <select name="plan_id" onchange="this.form.submit()" class="bg-slate-900 border border-slate-700 rounded-lg px-2 py-1 text-white text-xs">
                                            <option value="free" <?= $u['plan_id'] === 'free' ? 'selected' : '' ?>>Free</option>
                                            <option value="premium" <?= $u['plan_id'] === 'premium' ? 'selected' : '' ?>>Premium</option>
                                            <option value="ultra" <?= $u['plan_id'] === 'ultra' ? 'selected' : '' ?>>Ultra</option>
                                        </select>
                                    </form>

                                    <!-- Suspend / Activate Form -->
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                        <input type="hidden" name="status" value="<?= $u['status'] === 'active' ? 'suspended' : 'active' ?>">
                                        <button type="submit" class="px-2.5 py-1 rounded-lg text-xs <?= $u['status'] === 'active' ? 'bg-red-500/20 hover:bg-red-500/30 text-red-400 border border-red-500/30' : 'bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-400 border border-emerald-500/30' ?>">
                                            <?= $u['status'] === 'active' ? 'Suspend' : 'Activate' ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
