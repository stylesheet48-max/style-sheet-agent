<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

$pdo = getDBConnection();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_ad') {
        $campaignId = strtolower(trim($_POST['campaign_id'] ?? ''));
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $imageUrl = trim($_POST['image_url'] ?? '');
        $destUrl = trim($_POST['destination_url'] ?? '');
        $placements = $_POST['placements'] ?? ['chat_input'];
        $eligiblePlans = $_POST['eligible_plans'] ?? ['free', 'premium', 'ultra'];
        $frequencyCap = (int)($_POST['frequency_cap'] ?? 5);

        if (empty($campaignId) || empty($title) || empty($destUrl)) {
            $error = 'Campaign ID, Title, and Destination URL are required.';
        } else {
            $stmt = $pdo->prepare("
                INSERT OR REPLACE INTO ad_campaigns
                (campaign_id, title, description, image_url, destination_url, placements_json, eligible_plans_json, frequency_cap, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')
            ");
            $stmt->execute([
                $campaignId, $title, $description, $imageUrl, $destUrl,
                json_encode(array_values($placements)), json_encode(array_values($eligiblePlans)), $frequencyCap
            ]);
            auditLog($pdo, null, 'AD_CAMPAIGN_CREATE', "Created ad campaign: {$campaignId}");
            $message = "Ad campaign '{$title}' created successfully!";
        }
    } elseif ($action === 'toggle_status') {
        $id = (int)($_POST['id'] ?? 0);
        $curr = $pdo->query("SELECT status FROM ad_campaigns WHERE id = {$id}")->fetchColumn();
        $next = ($curr === 'active') ? 'disabled' : 'active';
        $pdo->prepare("UPDATE ad_campaigns SET status = ? WHERE id = ?")->execute([$next, $id]);
        $message = "Campaign status changed to {$next}.";
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("DELETE FROM ad_campaigns WHERE id = ?")->execute([$id]);
        $message = 'Campaign deleted.';
    }
}

$ads = $pdo->query("SELECT * FROM ad_campaigns ORDER BY id DESC")->fetchAll();
$totalImpressions = $pdo->query("SELECT SUM(impressions_count) FROM ad_campaigns")->fetchColumn() ?: 0;
$totalClicks = $pdo->query("SELECT SUM(clicks_count) FROM ad_campaigns")->fetchColumn() ?: 0;
$ctr = $totalImpressions > 0 ? round(($totalClicks / $totalImpressions) * 100, 2) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STYLE SHEET AGENT - Ads Management</title>
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
                    <p class="text-xs text-sky-400 font-medium">Sponsored Ads & Monetization Center</p>
                </div>
            </div>
            <nav class="flex items-center space-x-6 text-sm">
                <a href="index.php" class="text-slate-400 hover:text-white transition">Dashboard</a>
                <a href="users.php" class="text-slate-400 hover:text-white transition">Users</a>
                <a href="marketplace.php" class="text-slate-400 hover:text-white transition">Marketplace</a>
                <a href="ads.php" class="text-sky-400 font-semibold border-b-2 border-sky-400 pb-1">Ads Manager</a>
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

        <!-- Ad Analytics Metrics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="glass-card p-6 rounded-2xl shadow-xl">
                <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Active Campaigns</div>
                <div class="text-3xl font-extrabold text-white"><?= count($ads) ?></div>
                <div class="text-xs text-emerald-400 mt-2">Serving live to app</div>
            </div>
            <div class="glass-card p-6 rounded-2xl shadow-xl">
                <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Total Impressions</div>
                <div class="text-3xl font-extrabold text-white"><?= number_format($totalImpressions) ?></div>
                <div class="text-xs text-slate-400 mt-2">Rendered in app views</div>
            </div>
            <div class="glass-card p-6 rounded-2xl shadow-xl">
                <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Total Clicks</div>
                <div class="text-3xl font-extrabold text-white"><?= number_format($totalClicks) ?></div>
                <div class="text-xs text-sky-400 mt-2">Verified interactions</div>
            </div>
            <div class="glass-card p-6 rounded-2xl shadow-xl">
                <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Avg Click-Through (CTR)</div>
                <div class="text-3xl font-extrabold text-white"><?= $ctr ?>%</div>
                <div class="text-xs text-purple-400 mt-2">Performance index</div>
            </div>
        </div>

        <!-- Create Campaign Form -->
        <div class="glass-card p-6 md:p-8 rounded-3xl shadow-xl">
            <h2 class="text-xl font-bold text-white mb-2 flex items-center gap-2">
                <svg class="w-6 h-6 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                </svg>
                Create Sponsored Ad Campaign
            </h2>
            <p class="text-xs text-slate-400 mb-6">Ads are displayed non-intrusively in approved placements across Free, Premium, and Ultra users (Admin OFF).</p>

            <form method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <input type="hidden" name="action" value="create_ad">

                <div>
                    <label class="block text-xs uppercase tracking-wider text-slate-400 mb-1 font-medium">Campaign Unique ID</label>
                    <input type="text" name="campaign_id" required placeholder="ad-glass-pro"
                        class="w-full bg-slate-900/80 border border-slate-700/80 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-sky-400">
                </div>

                <div>
                    <label class="block text-xs uppercase tracking-wider text-slate-400 mb-1 font-medium">Ad Headline / Title</label>
                    <input type="text" name="title" required placeholder="STYLE SHEET AGENT Pro Edition"
                        class="w-full bg-slate-900/80 border border-slate-700/80 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-sky-400">
                </div>

                <div>
                    <label class="block text-xs uppercase tracking-wider text-slate-400 mb-1 font-medium">Destination URL</label>
                    <input type="url" name="destination_url" required placeholder="https://..."
                        class="w-full bg-slate-900/80 border border-slate-700/80 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-sky-400">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs uppercase tracking-wider text-slate-400 mb-1 font-medium">Image Banner URL (Optional)</label>
                    <input type="url" name="image_url" placeholder="https://..."
                        class="w-full bg-slate-900/80 border border-slate-700/80 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-sky-400">
                </div>

                <div>
                    <label class="block text-xs uppercase tracking-wider text-slate-400 mb-1 font-medium">Frequency Cap (Max / Session)</label>
                    <input type="number" name="frequency_cap" value="5"
                        class="w-full bg-slate-900/80 border border-slate-700/80 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-sky-400">
                </div>

                <div class="md:col-span-3">
                    <label class="block text-xs uppercase tracking-wider text-slate-400 mb-1 font-medium">Ad Description / Body</label>
                    <textarea name="description" rows="2" placeholder="Short non-intrusive promotional message..."
                        class="w-full bg-slate-900/80 border border-slate-700/80 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-sky-400"></textarea>
                </div>

                <div>
                    <label class="block text-xs uppercase tracking-wider text-slate-400 mb-2 font-medium">Approved Placements</label>
                    <div class="space-y-2 text-xs">
                        <label class="inline-flex items-center space-x-2">
                            <input type="checkbox" name="placements[]" value="chat_input" checked class="rounded border-slate-700 bg-slate-900 text-sky-500">
                            <span class="text-slate-300">Placement 1: Above Chat Input Area</span>
                        </label><br>
                        <label class="inline-flex items-center space-x-2">
                            <input type="checkbox" name="placements[]" value="chat_feed" checked class="rounded border-slate-700 bg-slate-900 text-sky-500">
                            <span class="text-slate-300">Placement 2: Chat Message Feed</span>
                        </label><br>
                        <label class="inline-flex items-center space-x-2">
                            <input type="checkbox" name="placements[]" value="marketplace" checked class="rounded border-slate-700 bg-slate-900 text-sky-500">
                            <span class="text-slate-300">Marketplace Discovery Cards</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-xs uppercase tracking-wider text-slate-400 mb-2 font-medium">Target User Plans</label>
                    <div class="space-y-2 text-xs">
                        <label class="inline-flex items-center space-x-2">
                            <input type="checkbox" name="eligible_plans[]" value="free" checked class="rounded border-slate-700 bg-slate-900 text-sky-500">
                            <span class="text-slate-300">Free Users (Ads ON)</span>
                        </label><br>
                        <label class="inline-flex items-center space-x-2">
                            <input type="checkbox" name="eligible_plans[]" value="premium" checked class="rounded border-slate-700 bg-slate-900 text-sky-500">
                            <span class="text-sky-400 font-semibold">Premium Users (Ads ON)</span>
                        </label><br>
                        <label class="inline-flex items-center space-x-2">
                            <input type="checkbox" name="eligible_plans[]" value="ultra" checked class="rounded border-slate-700 bg-slate-900 text-sky-500">
                            <span class="text-purple-400 font-semibold">Ultra Users (Ads ON)</span>
                        </label>
                    </div>
                </div>

                <div class="md:col-span-3 pt-2">
                    <button type="submit" class="px-6 py-3 rounded-xl bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-400 hover:to-blue-500 text-white font-semibold text-sm shadow-lg shadow-sky-500/25 transition">
                        Launch Ad Campaign
                    </button>
                </div>
            </form>
        </div>

        <!-- Existing Campaigns List -->
        <div class="glass-card p-6 md:p-8 rounded-3xl shadow-xl space-y-4">
            <h3 class="text-xl font-bold text-white flex items-center gap-2">
                <svg class="w-6 h-6 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                Campaign Performance & Management (<?= count($ads) ?>)
            </h3>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="text-slate-400 uppercase tracking-wider border-b border-slate-800">
                        <tr>
                            <th class="py-3 px-4">Campaign</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4">Placements</th>
                            <th class="py-3 px-4">Impressions</th>
                            <th class="py-3 px-4">Clicks</th>
                            <th class="py-3 px-4">CTR</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/80">
                        <?php foreach ($ads as $a): ?>
                            <?php
                            $cImp = (int)$a['impressions_count'];
                            $cCli = (int)$a['clicks_count'];
                            $cRate = $cImp > 0 ? round(($cCli / $cImp) * 100, 1) : 0;
                            $places = json_decode($a['placements_json'] ?? '[]', true);
                            ?>
                            <tr class="hover:bg-slate-900/40 transition">
                                <td class="py-4 px-4">
                                    <div class="font-bold text-white text-sm"><?= htmlspecialchars($a['title']) ?></div>
                                    <div class="text-slate-400 font-mono text-[11px]"><?= htmlspecialchars($a['campaign_id']) ?> &bull; <?= htmlspecialchars($a['destination_url']) ?></div>
                                </td>
                                <td class="py-4 px-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold <?= $a['status'] === 'active' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-slate-800 text-slate-500' ?>">
                                        <?= strtoupper($a['status']) ?>
                                    </span>
                                </td>
                                <td class="py-4 px-4 text-slate-300">
                                    <?= implode(', ', $places) ?>
                                </td>
                                <td class="py-4 px-4 font-mono font-bold text-white">
                                    <?= number_format($cImp) ?>
                                </td>
                                <td class="py-4 px-4 font-mono font-bold text-sky-400">
                                    <?= number_format($cCli) ?>
                                </td>
                                <td class="py-4 px-4 font-mono font-bold text-purple-400">
                                    <?= $cRate ?>%
                                </td>
                                <td class="py-4 px-4 text-right space-x-2">
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                                        <button type="submit" class="px-2.5 py-1 rounded-lg text-xs bg-slate-800 hover:bg-slate-700 text-slate-300">
                                            <?= $a['status'] === 'active' ? 'Pause' : 'Activate' ?>
                                        </button>
                                    </form>
                                    <form method="POST" onsubmit="return confirm('Delete this ad campaign?');" class="inline">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
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
