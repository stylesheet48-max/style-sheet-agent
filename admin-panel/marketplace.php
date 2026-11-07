<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

$pdo = getDBConnection();
$message = '';
$error = '';

$categories = [
    'Templates', 'Plugins', 'Skills', 'MCP Servers', 'AI Agents',
    'Workflows', 'Code Packages', 'Knowledge Packs', 'Developer Tools'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_package') {
        $packageId = strtolower(trim($_POST['package_id'] ?? ''));
        $title = trim($_POST['title'] ?? '');
        $category = $_POST['category'] ?? 'Templates';
        $version = trim($_POST['version'] ?? '1.0.0');
        $author = trim($_POST['author'] ?? 'STYLE SHEET LABS');
        $description = trim($_POST['description'] ?? '');
        $downloadUrl = trim($_POST['download_url'] ?? '');
        $eligiblePlans = $_POST['eligible_plans'] ?? ['free'];
        $iconName = trim($_POST['icon_name'] ?? 'Layers');

        if (empty($packageId) || empty($title)) {
            $error = 'Package ID and Title are required.';
        } else {
            $stmt = $pdo->prepare("
                INSERT OR REPLACE INTO marketplace_packages
                (package_id, category, title, description, version, author, icon_name, eligible_plans_json, download_url, is_published)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
            ");
            $stmt->execute([
                $packageId, $category, $title, $description, $version, $author, $iconName,
                json_encode(array_values($eligiblePlans)), $downloadUrl
            ]);
            auditLog($pdo, null, 'MARKETPLACE_PUBLISH', "Published package {$packageId} for " . implode(',', $eligiblePlans));
            $message = "Package '{$title}' published successfully!";
        }
    } elseif ($action === 'toggle_publish') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("UPDATE marketplace_packages SET is_published = (1 - is_published) WHERE id = ?")->execute([$id]);
        $message = 'Package visibility toggled.';
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("DELETE FROM marketplace_packages WHERE id = ?")->execute([$id]);
        $message = 'Package deleted from marketplace.';
    }
}

$selectedCategory = $_GET['cat'] ?? 'All';
if ($selectedCategory !== 'All') {
    $stmt = $pdo->prepare("SELECT * FROM marketplace_packages WHERE category = ? ORDER BY id DESC");
    $stmt->execute([$selectedCategory]);
    $packages = $stmt->fetchAll();
} else {
    $packages = $pdo->query("SELECT * FROM marketplace_packages ORDER BY id DESC")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STYLE SHEET AGENT - Marketplace & Package Manager</title>
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
                    <p class="text-xs text-sky-400 font-medium">Marketplace & Package Publishing</p>
                </div>
            </div>
            <nav class="flex items-center space-x-6 text-sm">
                <a href="index.php" class="text-slate-400 hover:text-white transition">Dashboard</a>
                <a href="users.php" class="text-slate-400 hover:text-white transition">Users</a>
                <a href="marketplace.php" class="text-sky-400 font-semibold border-b-2 border-sky-400 pb-1">Marketplace</a>
                <a href="releases.php" class="text-slate-400 hover:text-white transition">Releases</a>
                <a href="prompts.php" class="text-slate-400 hover:text-white transition">Prompts</a>
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

        <!-- Publish New Package Form -->
        <div class="glass-card p-6 md:p-8 rounded-3xl shadow-xl">
            <h2 class="text-xl font-bold text-white mb-2 flex items-center gap-2">
                <svg class="w-6 h-6 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                Publish Marketplace Package
            </h2>
            <p class="text-xs text-slate-400 mb-6">Publish packages across the 9 marketplace categories and configure plan-based visibility (Free, Premium, Ultra).</p>

            <form method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <input type="hidden" name="action" value="create_package">

                <div>
                    <label class="block text-xs uppercase tracking-wider text-slate-400 mb-1 font-medium">Package Identifier (Unique ID)</label>
                    <input type="text" name="package_id" required placeholder="pkg-react-native-glass"
                        class="w-full bg-slate-900/80 border border-slate-700/80 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-sky-400">
                </div>

                <div>
                    <label class="block text-xs uppercase tracking-wider text-slate-400 mb-1 font-medium">Package Title</label>
                    <input type="text" name="title" required placeholder="Glassmorphism Starter Template"
                        class="w-full bg-slate-900/80 border border-slate-700/80 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-sky-400">
                </div>

                <div>
                    <label class="block text-xs uppercase tracking-wider text-slate-400 mb-1 font-medium">Category (9 Categories)</label>
                    <select name="category" class="w-full bg-slate-900/80 border border-slate-700/80 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-sky-400">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs uppercase tracking-wider text-slate-400 mb-1 font-medium">Version (SemVer)</label>
                    <input type="text" name="version" value="1.0.0"
                        class="w-full bg-slate-900/80 border border-slate-700/80 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-sky-400">
                </div>

                <div>
                    <label class="block text-xs uppercase tracking-wider text-slate-400 mb-1 font-medium">Author / Publisher</label>
                    <input type="text" name="author" value="STYLE SHEET LABS"
                        class="w-full bg-slate-900/80 border border-slate-700/80 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-sky-400">
                </div>

                <div>
                    <label class="block text-xs uppercase tracking-wider text-slate-400 mb-1 font-medium">Download / Manifest URL</label>
                    <input type="url" name="download_url" placeholder="https://..."
                        class="w-full bg-slate-900/80 border border-slate-700/80 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-sky-400">
                </div>

                <div class="md:col-span-3">
                    <label class="block text-xs uppercase tracking-wider text-slate-400 mb-2 font-medium">Eligible Subscription Plans</label>
                    <div class="flex items-center space-x-6 text-sm">
                        <label class="inline-flex items-center space-x-2 cursor-pointer">
                            <input type="checkbox" name="eligible_plans[]" value="free" checked class="rounded border-slate-700 bg-slate-900 text-sky-500 focus:ring-sky-400">
                            <span class="text-slate-300 font-semibold">Free</span>
                        </label>
                        <label class="inline-flex items-center space-x-2 cursor-pointer">
                            <input type="checkbox" name="eligible_plans[]" value="premium" checked class="rounded border-slate-700 bg-slate-900 text-sky-500 focus:ring-sky-400">
                            <span class="text-sky-400 font-semibold">Premium</span>
                        </label>
                        <label class="inline-flex items-center space-x-2 cursor-pointer">
                            <input type="checkbox" name="eligible_plans[]" value="ultra" checked class="rounded border-slate-700 bg-slate-900 text-sky-500 focus:ring-sky-400">
                            <span class="text-purple-400 font-semibold">Ultra</span>
                        </label>
                    </div>
                </div>

                <div class="md:col-span-3">
                    <label class="block text-xs uppercase tracking-wider text-slate-400 mb-1 font-medium">Description</label>
                    <textarea name="description" rows="2" placeholder="Brief summary of package functionality..."
                        class="w-full bg-slate-900/80 border border-slate-700/80 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-sky-400"></textarea>
                </div>

                <div class="md:col-span-3">
                    <button type="submit" class="px-6 py-3 rounded-xl bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-400 hover:to-blue-500 text-white font-semibold text-sm shadow-lg shadow-sky-500/25 transition">
                        Publish to App Marketplace
                    </button>
                </div>
            </form>
        </div>

        <!-- Category Filter & List -->
        <div class="glass-card p-6 md:p-8 rounded-3xl shadow-xl space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <h3 class="text-xl font-bold text-white flex items-center gap-2">
                    <svg class="w-6 h-6 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    Published Packages (<?= count($packages) ?>)
                </h3>
                <div class="flex flex-wrap gap-2 text-xs">
                    <a href="marketplace.php" class="px-3 py-1.5 rounded-lg <?= $selectedCategory === 'All' ? 'bg-sky-500 text-white font-bold' : 'bg-slate-800 text-slate-400 hover:text-white' ?>">
                        All
                    </a>
                    <?php foreach ($categories as $c): ?>
                        <a href="marketplace.php?cat=<?= urlencode($c) ?>" class="px-3 py-1.5 rounded-lg <?= $selectedCategory === $c ? 'bg-sky-500 text-white font-bold' : 'bg-slate-800 text-slate-400 hover:text-white' ?>">
                            <?= htmlspecialchars($c) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="text-slate-400 uppercase tracking-wider border-b border-slate-800">
                        <tr>
                            <th class="py-3 px-4">Package</th>
                            <th class="py-3 px-4">Category</th>
                            <th class="py-3 px-4">Version</th>
                            <th class="py-3 px-4">Eligible Plans</th>
                            <th class="py-3 px-4">Installs</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/80">
                        <?php foreach ($packages as $pkg): ?>
                            <?php $plans = json_decode($pkg['eligible_plans_json'] ?? '["free"]', true); ?>
                            <tr class="hover:bg-slate-900/40 transition">
                                <td class="py-4 px-4">
                                    <div class="font-bold text-white text-sm"><?= htmlspecialchars($pkg['title']) ?></div>
                                    <div class="text-slate-400 font-mono text-[11px]"><?= htmlspecialchars($pkg['package_id']) ?> &bull; <?= htmlspecialchars($pkg['author']) ?></div>
                                </td>
                                <td class="py-4 px-4 font-semibold text-slate-300">
                                    <?= htmlspecialchars($pkg['category']) ?>
                                </td>
                                <td class="py-4 px-4 font-mono text-slate-400">
                                    v<?= htmlspecialchars($pkg['version']) ?>
                                </td>
                                <td class="py-4 px-4 space-x-1">
                                    <?php foreach ($plans as $pl): ?>
                                        <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold <?= $pl === 'ultra' ? 'bg-purple-500/20 text-purple-300 border border-purple-500/30' : ($pl === 'premium' ? 'bg-sky-500/20 text-sky-400 border border-sky-400/30' : 'bg-slate-800 text-slate-400') ?>">
                                            <?= strtoupper($pl) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </td>
                                <td class="py-4 px-4 font-bold text-white">
                                    <?= (int)$pkg['install_count'] ?>
                                </td>
                                <td class="py-4 px-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold <?= $pkg['is_published'] ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-slate-800 text-slate-500' ?>">
                                        <?= $pkg['is_published'] ? 'PUBLISHED' : 'DRAFT' ?>
                                    </span>
                                </td>
                                <td class="py-4 px-4 text-right space-x-2">
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="toggle_publish">
                                        <input type="hidden" name="id" value="<?= (int)$pkg['id'] ?>">
                                        <button type="submit" class="px-2.5 py-1 rounded-lg text-xs bg-slate-800 hover:bg-slate-700 text-slate-300">
                                            <?= $pkg['is_published'] ? 'Unpublish' : 'Publish' ?>
                                        </button>
                                    </form>
                                    <form method="POST" onsubmit="return confirm('Delete this package?');" class="inline">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int)$pkg['id'] ?>">
                                        <button type="submit" class="px-2.5 py-1 rounded-lg text-xs bg-red-500/20 hover:bg-red-500/30 text-red-400 border border-red-500/30">
                                            Delete
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
