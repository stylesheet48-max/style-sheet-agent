<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

$pdo = getDBConnection();
$message = '';
$error = '';

// Handle Actions: Add or Set Active
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $version = trim($_POST['version'] ?? '');
        $versionCode = (int)($_POST['version_code'] ?? 0);
        $apkUrl = trim($_POST['apk_url'] ?? '');
        $minSupported = trim($_POST['min_supported_version'] ?? '2.0.0');
        $releaseNotes = trim($_POST['release_notes'] ?? '');
        $isForceUpdate = isset($_POST['is_force_update']) ? 1 : 0;
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if (empty($version) || $versionCode <= 0 || empty($apkUrl)) {
            $error = 'Version name, Version code, and APK URL are required.';
        } else {
            if ($isActive === 1) {
                $pdo->exec("UPDATE app_releases SET is_active = 0");
            }
            $stmt = $pdo->prepare("
                INSERT INTO app_releases (version, version_code, apk_url, release_notes, min_supported_version, is_force_update, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$version, $versionCode, $apkUrl, $releaseNotes, $minSupported, $isForceUpdate, $isActive]);
            $message = "Release v{$version} published successfully!";
        }
    } elseif ($action === 'set_active') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->exec("UPDATE app_releases SET is_active = 0");
        $stmt = $pdo->prepare("UPDATE app_releases SET is_active = 1 WHERE id = ?");
        $stmt->execute([$id]);
        $message = "Active release updated!";
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM app_releases WHERE id = ?");
        $stmt->execute([$id]);
        $message = "Release deleted.";
    }
}

$releases = $pdo->query("SELECT * FROM app_releases ORDER BY version_code DESC, id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STYLE SHEET AGENT - Manage App Releases</title>
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
                    <p class="text-xs text-sky-400 font-medium">Releases & In-App APK Updates</p>
                </div>
            </div>
            <nav class="flex items-center space-x-6 text-sm">
                <a href="index.php" class="text-slate-400 hover:text-white transition">Dashboard</a>
                <a href="releases.php" class="text-sky-400 font-semibold border-b-2 border-sky-400 pb-1">Releases & APKs</a>
                <a href="prompts.php" class="text-slate-400 hover:text-white transition">Prompts & Tips</a>
                <a href="devices.php" class="text-slate-400 hover:text-white transition">Devices</a>
                <a href="logout.php" class="text-red-400 hover:text-red-300 font-medium transition">Logout</a>
            </nav>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-6 py-8 flex-1 w-full space-y-8">
        <?php if (!empty($message)): ?>
            <div class="bg-emerald-500/20 border border-emerald-500/50 text-emerald-200 text-sm px-4 py-3 rounded-2xl">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="bg-red-500/20 border border-red-500/50 text-red-200 text-sm px-4 py-3 rounded-2xl">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- New Release Form -->
        <div class="glass-card p-6 md:p-8 rounded-3xl shadow-xl">
            <h2 class="text-xl font-bold text-white mb-2 flex items-center gap-2">
                <svg class="w-6 h-6 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Publish New App Release
            </h2>
            <p class="text-xs text-slate-400 mb-6">When published, STYLE SHEET AGENT users will automatically be notified of the new update via the in-app update engine.</p>

            <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <input type="hidden" name="action" value="create">

                <div>
                    <label class="block text-xs uppercase tracking-wider text-slate-400 mb-2 font-medium">Version Name (e.g. 2.1.9)</label>
                    <input type="text" name="version" required placeholder="2.1.9"
                        class="w-full bg-slate-900/80 border border-slate-700/80 rounded-xl px-4 py-3 text-white placeholder-slate-600 focus:outline-none focus:border-sky-400 focus:ring-1 focus:ring-sky-400 transition text-sm">
                </div>

                <div>
                    <label class="block text-xs uppercase tracking-wider text-slate-400 mb-2 font-medium">Version Code (Android Build Number)</label>
                    <input type="number" name="version_code" required placeholder="3"
                        class="w-full bg-slate-900/80 border border-slate-700/80 rounded-xl px-4 py-3 text-white placeholder-slate-600 focus:outline-none focus:border-sky-400 focus:ring-1 focus:ring-sky-400 transition text-sm">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs uppercase tracking-wider text-slate-400 mb-2 font-medium">Direct APK Download URL</label>
                    <input type="url" name="apk_url" required placeholder="https://github.com/stylesheet48-max/style-sheet-agent/releases/download/v2.1.9/STYLE_SHEET_AGENT.apk"
                        class="w-full bg-slate-900/80 border border-slate-700/80 rounded-xl px-4 py-3 text-white placeholder-slate-600 focus:outline-none focus:border-sky-400 focus:ring-1 focus:ring-sky-400 transition text-sm">
                </div>

                <div>
                    <label class="block text-xs uppercase tracking-wider text-slate-400 mb-2 font-medium">Min Supported Version</label>
                    <input type="text" name="min_supported_version" value="2.0.0"
                        class="w-full bg-slate-900/80 border border-slate-700/80 rounded-xl px-4 py-3 text-white placeholder-slate-600 focus:outline-none focus:border-sky-400 focus:ring-1 focus:ring-sky-400 transition text-sm">
                </div>

                <div class="flex items-center space-x-6 pt-6">
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="is_force_update" value="1" class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-500"></div>
                        <span class="ml-3 text-xs font-medium text-slate-300">Force / Mandatory Update</span>
                    </label>

                    <label class="inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" checked class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-sky-500"></div>
                        <span class="ml-3 text-xs font-medium text-slate-300">Set as Active Live Release</span>
                    </label>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs uppercase tracking-wider text-slate-400 mb-2 font-medium">Release Notes (Markdown or Text)</label>
                    <textarea name="release_notes" rows="3" placeholder="- What's new in this build..."
                        class="w-full bg-slate-900/80 border border-slate-700/80 rounded-xl px-4 py-3 text-white placeholder-slate-600 focus:outline-none focus:border-sky-400 focus:ring-1 focus:ring-sky-400 transition text-sm"></textarea>
                </div>

                <div class="md:col-span-2">
                    <button type="submit"
                        class="px-6 py-3.5 rounded-xl bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-400 hover:to-blue-500 text-white font-semibold text-sm shadow-lg shadow-sky-500/25 transition">
                        Publish Release to App
                    </button>
                </div>
            </form>
        </div>

        <!-- Existing Releases List -->
        <div class="glass-card p-6 md:p-8 rounded-3xl shadow-xl space-y-4">
            <h2 class="text-xl font-bold text-white flex items-center gap-2">
                <svg class="w-6 h-6 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                Release History & Status
            </h2>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="text-slate-400 uppercase tracking-wider border-b border-slate-800">
                        <tr>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4">Version</th>
                            <th class="py-3 px-4">Build Code</th>
                            <th class="py-3 px-4">Min Version</th>
                            <th class="py-3 px-4">Force Update</th>
                            <th class="py-3 px-4">Published Date</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/80">
                        <?php foreach ($releases as $rel): ?>
                            <tr class="hover:bg-slate-900/40 transition">
                                <td class="py-4 px-4">
                                    <?php if ($rel['is_active']): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                                            ACTIVE LIVE
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-medium bg-slate-800 text-slate-400">
                                            Archived
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-4 px-4 font-bold text-white text-sm">
                                    v<?= htmlspecialchars($rel['version']) ?>
                                </td>
                                <td class="py-4 px-4 font-mono text-slate-300">
                                    <?= (int)$rel['version_code'] ?>
                                </td>
                                <td class="py-4 px-4 text-slate-400">
                                    v<?= htmlspecialchars($rel['min_supported_version']) ?>
                                </td>
                                <td class="py-4 px-4">
                                    <span class="<?= $rel['is_force_update'] ? 'text-amber-400 font-semibold' : 'text-slate-500' ?>">
                                        <?= $rel['is_force_update'] ? 'Yes' : 'No' ?>
                                    </span>
                                </td>
                                <td class="py-4 px-4 text-slate-400">
                                    <?= htmlspecialchars(date('M d, Y H:i', strtotime($rel['created_at']))) ?>
                                </td>
                                <td class="py-4 px-4 text-right space-x-2">
                                    <a href="<?= htmlspecialchars($rel['apk_url']) ?>" target="_blank"
                                        class="px-3 py-1 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-lg border border-slate-700">
                                        Download APK
                                    </a>
                                    <?php if (!$rel['is_active']): ?>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="action" value="set_active">
                                            <input type="hidden" name="id" value="<?= (int)$rel['id'] ?>">
                                            <button type="submit" class="px-3 py-1 bg-sky-500/20 hover:bg-sky-500/30 text-sky-400 rounded-lg border border-sky-400/30">
                                                Activate
                                            </button>
                                        </form>
                                    <?php endif; ?>
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
