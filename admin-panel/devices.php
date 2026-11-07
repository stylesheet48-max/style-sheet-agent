<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

$pdo = getDBConnection();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'clear_telemetry') {
    $pdo->exec("DELETE FROM device_telemetry");
    $message = 'Device telemetry logs cleared.';
}

$devices = $pdo->query("SELECT * FROM device_telemetry ORDER BY id DESC LIMIT 100")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STYLE SHEET AGENT - Connected Devices & Telemetry</title>
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
                    <p class="text-xs text-sky-400 font-medium">Device Fleet Telemetry</p>
                </div>
            </div>
            <nav class="flex items-center space-x-6 text-sm">
                <a href="index.php" class="text-slate-400 hover:text-white transition">Dashboard</a>
                <a href="releases.php" class="text-slate-400 hover:text-white transition">Releases & APKs</a>
                <a href="prompts.php" class="text-slate-400 hover:text-white transition">Prompts & Tips</a>
                <a href="devices.php" class="text-sky-400 font-semibold border-b-2 border-sky-400 pb-1">Devices</a>
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        Connected Client Fleet & Telemetry
                    </h2>
                    <p class="text-xs text-slate-400 mt-1">Live pings from STYLE SHEET AGENT apps via <code>POST /api/v1/telemetry.php</code>.</p>
                </div>
                <form method="POST" onsubmit="return confirm('Clear all telemetry logs?');">
                    <input type="hidden" name="action" value="clear_telemetry">
                    <button type="submit" class="px-4 py-2 bg-red-500/20 hover:bg-red-500/30 text-red-400 border border-red-500/30 rounded-xl text-xs font-semibold">
                        Clear Logs
                    </button>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="text-slate-400 uppercase tracking-wider border-b border-slate-800">
                        <tr>
                            <th class="py-3 px-4">Device ID</th>
                            <th class="py-3 px-4">Platform</th>
                            <th class="py-3 px-4">OS Version</th>
                            <th class="py-3 px-4">App Version</th>
                            <th class="py-3 px-4">IP Address</th>
                            <th class="py-3 px-4">Last Seen</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/80">
                        <?php if (empty($devices)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-8 text-slate-500">
                                    No client pings received yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($devices as $dev): ?>
                                <tr class="hover:bg-slate-900/40 transition">
                                    <td class="py-4 px-4 font-mono text-white">
                                        <?= htmlspecialchars($dev['device_id']) ?>
                                    </td>
                                    <td class="py-4 px-4 uppercase text-slate-300 font-semibold">
                                        <?= htmlspecialchars($dev['platform']) ?>
                                    </td>
                                    <td class="py-4 px-4 text-slate-400">
                                        <?= htmlspecialchars($dev['os_version']) ?>
                                    </td>
                                    <td class="py-4 px-4">
                                        <span class="px-2 py-0.5 rounded bg-sky-500/10 text-sky-400 border border-sky-400/20 font-bold">
                                            v<?= htmlspecialchars($dev['app_version']) ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 font-mono text-slate-400">
                                        <?= htmlspecialchars($dev['ip_address']) ?>
                                    </td>
                                    <td class="py-4 px-4 text-slate-400">
                                        <?= htmlspecialchars(date('M d, Y H:i:s', strtotime($dev['last_seen']))) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
