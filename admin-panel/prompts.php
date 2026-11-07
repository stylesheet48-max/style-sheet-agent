<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

$pdo = getDBConnection();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_prompt') {
        $label = trim($_POST['label'] ?? '');
        $prompt = trim($_POST['prompt'] ?? '');
        $sortOrder = (int)($_POST['sort_order'] ?? 0);

        if (!empty($label) && !empty($prompt)) {
            $stmt = $pdo->prepare("INSERT INTO starter_prompts (label, prompt, sort_order) VALUES (?, ?, ?)");
            $stmt->execute([$label, $prompt, $sortOrder]);
            $message = 'Starter prompt added successfully!';
        }
    } elseif ($action === 'delete_prompt') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM starter_prompts WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'Prompt deleted.';
    } elseif ($action === 'toggle_prompt') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("UPDATE starter_prompts SET is_active = (1 - is_active) WHERE id = ?")->execute([$id]);
        $message = 'Prompt status updated.';
    } elseif ($action === 'update_tip') {
        $tipText = trim($_POST['tip_text'] ?? '');
        $tipUrl = trim($_POST['tip_url'] ?? '');

        if (!empty($tipText)) {
            $pdo->exec("UPDATE chat_tips SET is_active = 0");
            $stmt = $pdo->prepare("INSERT INTO chat_tips (text, link_url, is_active) VALUES (?, ?, 1)");
            $stmt->execute([$tipText, $tipUrl]);
            $message = 'Chat announcement tip updated!';
        }
    }
}

$prompts = $pdo->query("SELECT * FROM starter_prompts ORDER BY sort_order ASC, id ASC")->fetchAll();
$currentTip = $pdo->query("SELECT * FROM chat_tips WHERE is_active = 1 ORDER BY id DESC LIMIT 1")->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STYLE SHEET AGENT - Prompts & Composer Tips</title>
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
                    <p class="text-xs text-sky-400 font-medium">Dynamic Prompts & Tips</p>
                </div>
            </div>
            <nav class="flex items-center space-x-6 text-sm">
                <a href="index.php" class="text-slate-400 hover:text-white transition">Dashboard</a>
                <a href="releases.php" class="text-slate-400 hover:text-white transition">Releases & APKs</a>
                <a href="prompts.php" class="text-sky-400 font-semibold border-b-2 border-sky-400 pb-1">Prompts & Tips</a>
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

        <!-- Composer Tip Card -->
        <div class="glass-card p-6 md:p-8 rounded-3xl shadow-xl space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold text-white flex items-center gap-2">
                        <svg class="w-6 h-6 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                        </svg>
                        Global Chat Composer Announcement Tip
                    </h2>
                    <p class="text-xs text-slate-400 mt-1">This text appears directly inside the STYLE SHEET AGENT input bar or above the composer.</p>
                </div>
                <a href="api/v1/chat-tip.php" target="_blank" class="text-xs text-sky-400 hover:underline">View Tip API &rarr;</a>
            </div>

            <form method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2">
                <input type="hidden" name="action" value="update_tip">
                <div class="md:col-span-2">
                    <label class="block text-xs uppercase tracking-wider text-slate-400 mb-2 font-medium">Tip Announcement Text</label>
                    <input type="text" name="tip_text" required value="<?= htmlspecialchars($currentTip['text'] ?? '') ?>"
                        class="w-full bg-slate-900/80 border border-slate-700/80 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-sky-400 focus:ring-1 focus:ring-sky-400 text-sm">
                </div>
                <div>
                    <label class="block text-xs uppercase tracking-wider text-slate-400 mb-2 font-medium">Link URL (Optional)</label>
                    <input type="url" name="tip_url" value="<?= htmlspecialchars($currentTip['link_url'] ?? '') ?>" placeholder="https://..."
                        class="w-full bg-slate-900/80 border border-slate-700/80 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-sky-400 focus:ring-1 focus:ring-sky-400 text-sm">
                </div>
                <div class="md:col-span-3">
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-sky-500 hover:bg-sky-400 text-white font-semibold text-xs shadow-lg shadow-sky-500/25 transition">
                        Update Announcement Tip
                    </button>
                </div>
            </form>
        </div>

        <!-- Add Starter Prompt -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="glass-card p-6 rounded-3xl shadow-xl space-y-4">
                <h3 class="text-lg font-bold text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Starter Prompt
                </h3>
                <p class="text-xs text-slate-400">Quick suggestions displayed when a user opens a fresh chat session.</p>

                <form method="POST" class="space-y-4 pt-2">
                    <input type="hidden" name="action" value="add_prompt">
                    <div>
                        <label class="block text-xs uppercase tracking-wider text-slate-400 mb-1 font-medium">Button Label</label>
                        <input type="text" name="label" required placeholder="e.g. Glassmorphic UI Card"
                            class="w-full bg-slate-900/80 border border-slate-700/80 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-sky-400 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs uppercase tracking-wider text-slate-400 mb-1 font-medium">Full AI Prompt</label>
                        <textarea name="prompt" rows="3" required placeholder="What the agent should execute when tapped..."
                            class="w-full bg-slate-900/80 border border-slate-700/80 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-sky-400 text-sm"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs uppercase tracking-wider text-slate-400 mb-1 font-medium">Sort Order</label>
                        <input type="number" name="sort_order" value="1"
                            class="w-full bg-slate-900/80 border border-slate-700/80 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-sky-400 text-sm">
                    </div>
                    <button type="submit" class="w-full py-3 rounded-xl bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-400 hover:to-blue-500 text-white font-semibold text-xs shadow-lg shadow-sky-500/25 transition">
                        Add Quick Prompt
                    </button>
                </form>
            </div>

            <!-- Existing Prompts List -->
            <div class="lg:col-span-2 glass-card p-6 rounded-3xl shadow-xl space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-bold text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                        </svg>
                        Current Starter Prompts (<?= count($prompts) ?>)
                    </h3>
                    <a href="api/v1/starter-prompts.php" target="_blank" class="text-xs text-sky-400 hover:underline">View Prompts API &rarr;</a>
                </div>

                <div class="space-y-3">
                    <?php foreach ($prompts as $p): ?>
                        <div class="p-4 bg-slate-900/60 rounded-2xl border border-slate-800 flex items-start justify-between gap-4">
                            <div class="space-y-1 flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-mono <?= $p['is_active'] ? 'bg-sky-500/20 text-sky-400 border border-sky-400/30' : 'bg-slate-800 text-slate-500' ?>">
                                        #<?= (int)$p['sort_order'] ?>
                                    </span>
                                    <h4 class="font-bold text-white text-sm"><?= htmlspecialchars($p['label']) ?></h4>
                                    <?php if (!$p['is_active']): ?>
                                        <span class="text-[10px] text-amber-400 font-semibold">(Disabled)</span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-xs text-slate-400 leading-relaxed"><?= htmlspecialchars($p['prompt']) ?></p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <form method="POST">
                                    <input type="hidden" name="action" value="toggle_prompt">
                                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                    <button type="submit" class="p-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs">
                                        <?= $p['is_active'] ? 'Disable' : 'Enable' ?>
                                    </button>
                                </form>
                                <form method="POST" onsubmit="return confirm('Delete this prompt?');">
                                    <input type="hidden" name="action" value="delete_prompt">
                                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                    <button type="submit" class="p-2 rounded-lg bg-red-500/20 hover:bg-red-500/30 text-red-400 text-xs">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
