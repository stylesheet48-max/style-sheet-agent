<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

$pdo = getDBConnection();

// Fetch summary metrics
$activeRelease = $pdo->query("SELECT * FROM app_releases WHERE is_active = 1 ORDER BY version_code DESC LIMIT 1")->fetch();
$totalReleases = $pdo->query("SELECT COUNT(*) FROM app_releases")->fetchColumn();
$totalPrompts = $pdo->query("SELECT COUNT(*) FROM starter_prompts WHERE is_active = 1")->fetchColumn();
$totalDevices = $pdo->query("SELECT COUNT(DISTINCT device_id) FROM device_telemetry")->fetchColumn();
$recentDevices = $pdo->query("SELECT * FROM device_telemetry ORDER BY id DESC LIMIT 5")->fetchAll();

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost:8080';
$apiUrl = $protocol . $host . '/api/v1/update.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STYLE SHEET AGENT - Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: radial-gradient(circle at 50% 10%, #0d1527 0%, #070B14 100%);
            min-height: 100vh;
        }
        .glass-card {
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(14px);
            border: 1px solid rgba(56, 189, 248, 0.15);
        }
        .glass-card:hover {
            border-color: rgba(56, 189, 248, 0.35);
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
                    <p class="text-xs text-sky-400 font-medium">PHP Admin Command Center</p>
                </div>
            </div>
            <nav class="flex items-center space-x-5 text-sm">
                <a href="index.php" class="text-sky-400 font-semibold border-b-2 border-sky-400 pb-1">Dashboard</a>
                <a href="users.php" class="text-slate-400 hover:text-white transition">Users</a>
                <a href="marketplace.php" class="text-slate-400 hover:text-white transition">Marketplace</a>
                <a href="ads.php" class="text-slate-400 hover:text-white transition">Ads Manager</a>
                <a href="releases.php" class="text-slate-400 hover:text-white transition">Releases</a>
                <a href="prompts.php" class="text-slate-400 hover:text-white transition">Prompts</a>
                <a href="devices.php" class="text-slate-400 hover:text-white transition">Devices</a>
                <a href="logout.php" class="text-red-400 hover:text-red-300 font-medium transition">Logout</a>
            </nav>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-6 py-8 flex-1 w-full space-y-8">
        <!-- Banner -->
        <div class="glass-card p-6 rounded-3xl flex flex-col md:flex-row items-start md:items-center justify-between gap-4 border-l-4 border-l-sky-400">
            <div>
                <span class="inline-block px-3 py-1 bg-sky-500/10 border border-sky-400/30 text-sky-400 text-xs font-semibold rounded-full mb-2">
                    Package: <?= htmlspecialchars(PACKAGE_NAME) ?>
                </span>
                <h2 class="text-2xl font-bold text-white">Central Agent Administration</h2>
                <p class="text-sm text-slate-400 mt-1">Manage remote app versions, APK updates, quick prompts, and client telemetry dynamically.</p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="releases.php" class="px-5 py-2.5 rounded-xl bg-sky-500 hover:bg-sky-400 text-white font-semibold text-sm shadow-lg shadow-sky-500/25 transition">
                    + New Release
                </a>
                <a href="api/v1/update.php" target="_blank" class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 font-semibold text-sm transition">
                    View Live API JSON
                </a>
            </div>
        </div>

        <!-- Metrics Grid -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="glass-card p-6 rounded-2xl shadow-xl">
                <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Active App Version</div>
                <div class="text-3xl font-extrabold text-white flex items-baseline gap-2">
                    <span>v<?= htmlspecialchars($activeRelease['version'] ?? 'N/A') ?></span>
                    <span class="text-xs text-sky-400 font-normal">(Build <?= (int)($activeRelease['version_code'] ?? 0) ?>)</span>
                </div>
                <div class="text-xs text-emerald-400 mt-2 flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 inline-block animate-pulse"></span> Live on devices
                </div>
            </div>

            <div class="glass-card p-6 rounded-2xl shadow-xl">
                <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Total Releases</div>
                <div class="text-3xl font-extrabold text-white"><?= (int)$totalReleases ?></div>
                <div class="text-xs text-slate-400 mt-2">Historical builds stored</div>
            </div>

            <div class="glass-card p-6 rounded-2xl shadow-xl">
                <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Active Starter Prompts</div>
                <div class="text-3xl font-extrabold text-white"><?= (int)$totalPrompts ?></div>
                <div class="text-xs text-sky-400 mt-2">Loaded in app chat</div>
            </div>

            <div class="glass-card p-6 rounded-2xl shadow-xl">
                <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Connected Devices</div>
                <div class="text-3xl font-extrabold text-white"><?= (int)$totalDevices ?></div>
                <div class="text-xs text-slate-400 mt-2">Active telemetry pings</div>
            </div>
        </div>

        <!-- Two Column Content -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left 2 Cols: Active Release & Endpoints -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Active Release Details -->
                <div class="glass-card p-6 rounded-3xl shadow-xl space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold text-white flex items-center gap-2">
                            <svg class="w-5 h-5 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Current Live Release Details
                        </h3>
                        <a href="releases.php" class="text-xs text-sky-400 hover:underline">Manage All</a>
                    </div>
                    <?php if ($activeRelease): ?>
                        <div class="space-y-3 text-sm bg-slate-900/60 p-4 rounded-2xl border border-slate-800">
                            <div class="flex justify-between border-b border-slate-800/80 pb-2">
                                <span class="text-slate-400">Release Version:</span>
                                <span class="font-semibold text-white">v<?= htmlspecialchars($activeRelease['version']) ?> (Code: <?= (int)$activeRelease['version_code'] ?>)</span>
                            </div>
                            <div class="flex justify-between border-b border-slate-800/80 pb-2">
                                <span class="text-slate-400">Min Supported Version:</span>
                                <span class="font-semibold text-white">v<?= htmlspecialchars($activeRelease['min_supported_version']) ?></span>
                            </div>
                            <div class="flex justify-between border-b border-slate-800/80 pb-2">
                                <span class="text-slate-400">Force Update:</span>
                                <span class="<?= $activeRelease['is_force_update'] ? 'text-amber-400 font-bold' : 'text-slate-400' ?>">
                                    <?= $activeRelease['is_force_update'] ? 'YES (Mandatory)' : 'No (Optional)' ?>
                                </span>
                            </div>
                            <div>
                                <span class="text-slate-400 block mb-1">Direct APK Download URL:</span>
                                <a href="<?= htmlspecialchars($activeRelease['apk_url']) ?>" target="_blank" class="text-sky-400 break-all hover:underline text-xs">
                                    <?= htmlspecialchars($activeRelease['apk_url']) ?>
                                </a>
                            </div>
                            <div class="pt-2">
                                <span class="text-slate-400 block mb-1">Release Notes:</span>
                                <p class="text-slate-300 text-xs bg-slate-950/80 p-3 rounded-xl border border-slate-800/60 leading-relaxed">
                                    <?= nl2br(htmlspecialchars($activeRelease['release_notes'])) ?>
                                </p>
                            </div>
                        </div>
                    <?php else: ?>
                        <p class="text-slate-400 text-sm">No active release configured. Please add one in the Releases tab.</p>
                    <?php endif; ?>
                </div>

                <!-- API Endpoints Info -->
                <div class="glass-card p-6 rounded-3xl shadow-xl space-y-4">
                    <h3 class="text-lg font-bold text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        API Endpoints for STYLE SHEET AGENT App
                    </h3>
                    <div class="space-y-3 text-xs">
                        <div class="p-3 bg-slate-900/60 rounded-xl border border-slate-800 flex items-center justify-between">
                            <div>
                                <span class="px-2 py-0.5 bg-emerald-500/20 text-emerald-400 font-bold rounded mr-2">GET</span>
                                <code class="text-slate-200">/api/v1/update.php</code>
                            </div>
                            <a href="api/v1/update.php" target="_blank" class="text-sky-400 hover:underline">Test API &rarr;</a>
                        </div>
                        <div class="p-3 bg-slate-900/60 rounded-xl border border-slate-800 flex items-center justify-between">
                            <div>
                                <span class="px-2 py-0.5 bg-emerald-500/20 text-emerald-400 font-bold rounded mr-2">GET</span>
                                <code class="text-slate-200">/api/v1/starter-prompts.php</code>
                            </div>
                            <a href="api/v1/starter-prompts.php" target="_blank" class="text-sky-400 hover:underline">Test API &rarr;</a>
                        </div>
                        <div class="p-3 bg-slate-900/60 rounded-xl border border-slate-800 flex items-center justify-between">
                            <div>
                                <span class="px-2 py-0.5 bg-emerald-500/20 text-emerald-400 font-bold rounded mr-2">GET</span>
                                <code class="text-slate-200">/api/v1/chat-tip.php</code>
                            </div>
                            <a href="api/v1/chat-tip.php" target="_blank" class="text-sky-400 hover:underline">Test API &rarr;</a>
                        </div>
                        <div class="p-3 bg-slate-900/60 rounded-xl border border-slate-800 flex items-center justify-between">
                            <div>
                                <span class="px-2 py-0.5 bg-blue-500/20 text-blue-400 font-bold rounded mr-2">POST</span>
                                <code class="text-slate-200">/api/v1/telemetry.php</code>
                            </div>
                            <span class="text-slate-500">Device Ping / Registration</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right 1 Col: Quick Device Ping / Activity -->
            <div class="space-y-6">
                <div class="glass-card p-6 rounded-3xl shadow-xl space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold text-white flex items-center gap-2">
                            <svg class="w-5 h-5 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            Recent Client Pings
                        </h3>
                        <a href="devices.php" class="text-xs text-sky-400 hover:underline">View All</a>
                    </div>
                    <?php if (!empty($recentDevices)): ?>
                        <div class="space-y-3">
                            <?php foreach ($recentDevices as $dev): ?>
                                <div class="p-3 bg-slate-900/60 rounded-xl border border-slate-800 text-xs space-y-1">
                                    <div class="flex justify-between font-semibold text-white">
                                        <span class="truncate max-w-[150px]"><?= htmlspecialchars($dev['device_id']) ?></span>
                                        <span class="text-sky-400">v<?= htmlspecialchars($dev['app_version']) ?></span>
                                    </div>
                                    <div class="flex justify-between text-slate-400">
                                        <span><?= htmlspecialchars($dev['platform']) ?> (<?= htmlspecialchars($dev['os_version']) ?>)</span>
                                        <span><?= htmlspecialchars(date('M d, H:i', strtotime($dev['last_seen']))) ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8 text-slate-500 text-sm">
                            No devices recorded yet. Telemetry endpoint is listening at <code>/api/v1/telemetry.php</code>.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Server Info Card -->
                <div class="glass-card p-6 rounded-3xl shadow-xl space-y-3 text-xs">
                    <h3 class="font-bold text-white text-sm">Admin Environment</h3>
                    <div class="flex justify-between text-slate-400 border-b border-slate-800 pb-2">
                        <span>PHP Version:</span>
                        <span class="text-slate-200"><?= phpversion() ?></span>
                    </div>
                    <div class="flex justify-between text-slate-400 border-b border-slate-800 pb-2">
                        <span>Database Driver:</span>
                        <span class="text-slate-200">SQLite 3 (PDO)</span>
                    </div>
                    <div class="flex justify-between text-slate-400">
                        <span>App Package Name:</span>
                        <span class="text-sky-400 font-mono"><?= htmlspecialchars(PACKAGE_NAME) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="border-t border-slate-800 py-6 text-center text-xs text-slate-500">
        &copy; <?= date('Y') ?> STYLE SHEET AGENT. All rights reserved. Built with Glassmorphism Visual Engine.
    </footer>
</body>
</html>
