<?php
/**
 * STYLE SHEET AGENT - Admin Panel Configuration, Schema & Security Helpers
 */

session_start();

define('APP_NAME', 'STYLE SHEET AGENT');
define('APP_VERSION', '2.1.8');
define('PACKAGE_NAME', 'com.stylesheet.agent');
define('DB_PATH', __DIR__ . '/../database/style_sheet_agent.sqlite');
define('JWT_SECRET', 'stylesheet_agent_secure_jwt_secret_key_2026_x99');

// Database Initialization (SQLite with PDO)
function getDBConnection(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dbDir = dirname(DB_PATH);
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0777, true);
        }
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        initDatabaseSchema($pdo);
    }
    return $pdo;
}

function initDatabaseSchema(PDO $pdo): void {
    // 1. Admin Users
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admin_users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");

    // 2. Client Users
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            uid TEXT UNIQUE NOT NULL,
            email TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            full_name TEXT NOT NULL,
            avatar_url TEXT,
            plan_id TEXT DEFAULT 'free',
            plan_expires_at DATETIME,
            status TEXT DEFAULT 'active',
            email_verified INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");

    // 3. Subscription Plans (Free, Premium, Ultra)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS subscription_plans (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            plan_key TEXT UNIQUE NOT NULL,
            name TEXT NOT NULL,
            description TEXT,
            price_monthly REAL DEFAULT 0,
            duration_days INTEGER DEFAULT 30,
            ai_models_json TEXT,
            usage_limits_json TEXT,
            is_active INTEGER DEFAULT 1
        );
    ");

    // 4. Marketplace Packages (9 Categories)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS marketplace_packages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            package_id TEXT UNIQUE NOT NULL,
            category TEXT NOT NULL,
            title TEXT NOT NULL,
            description TEXT,
            version TEXT NOT NULL,
            author TEXT NOT NULL,
            icon_name TEXT DEFAULT 'Layers',
            preview_image_url TEXT,
            eligible_plans_json TEXT NOT NULL,
            permissions_json TEXT,
            dependencies_json TEXT,
            manifest_json TEXT,
            sha256_checksum TEXT,
            download_url TEXT,
            install_count INTEGER DEFAULT 0,
            is_published INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");

    // 5. Package Installations / User History
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS package_installations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            package_id TEXT NOT NULL,
            installed_version TEXT NOT NULL,
            status TEXT DEFAULT 'active',
            backup_manifest TEXT,
            installed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );
    ");

    // 6. Audit & Security Event Logs
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS audit_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            action TEXT NOT NULL,
            details TEXT,
            ip_address TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");

    // 7. App Releases
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS app_releases (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            version TEXT NOT NULL,
            version_code INTEGER NOT NULL,
            apk_url TEXT NOT NULL,
            release_notes TEXT,
            min_supported_version TEXT DEFAULT '2.0.0',
            is_force_update INTEGER DEFAULT 0,
            is_active INTEGER DEFAULT 1,
            download_count INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");

    // 8. Starter Prompts Catalog
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS starter_prompts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            label TEXT NOT NULL,
            prompt TEXT NOT NULL,
            sort_order INTEGER DEFAULT 0,
            is_active INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");

    // 9. Chat Tips / Announcements
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS chat_tips (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            text TEXT NOT NULL,
            link_url TEXT,
            is_active INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");

    // 10. Device Telemetry
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS device_telemetry (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            device_id TEXT NOT NULL,
            platform TEXT DEFAULT 'android',
            os_version TEXT,
            app_version TEXT,
            ip_address TEXT,
            last_seen DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");

    // 11. Ads Management & Campaigns
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ad_campaigns (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            campaign_id TEXT UNIQUE NOT NULL,
            title TEXT NOT NULL,
            description TEXT,
            image_url TEXT,
            destination_url TEXT NOT NULL,
            placements_json TEXT NOT NULL,
            eligible_plans_json TEXT NOT NULL,
            frequency_cap INTEGER DEFAULT 5,
            start_date DATE,
            end_date DATE,
            status TEXT DEFAULT 'active',
            impressions_count INTEGER DEFAULT 0,
            clicks_count INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");

    // 12. Ad Events (Impressions & Clicks)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ad_events (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            campaign_id TEXT NOT NULL,
            user_id INTEGER,
            event_type TEXT NOT NULL,
            placement TEXT,
            ip_address TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");

    // Seed Default Ads (Glassmorphism & Dev Tools Promos)
    $checkAds = $pdo->query("SELECT COUNT(*) FROM ad_campaigns")->fetchColumn();
    if ($checkAds == 0) {
        $seedAds = [
            [
                'ad-glass-pro', 'STYLE SHEET PRO - Unlock Ultra Autonomous Agents',
                'Accelerate your design workflows with multi-agent orchestration, Claude 3.5 Sonnet & DeepSeek R1 models.',
                'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=600&auto=format&fit=crop&q=80',
                'https://github.com/stylesheet48-max/style-sheet-agent',
                json_encode(['chat_input', 'chat_feed', 'marketplace']),
                json_encode(['free', 'premium', 'ultra']),
                10
            ],
            [
                'ad-mcp-cloud', 'PostgreSQL & Docker Cloud MCP Gateway',
                'Connect your mobile AI agent to production cloud databases and Docker clusters securely.',
                'https://images.unsplash.com/photo-1558494949-ef010cbdcc31?w=600&auto=format&fit=crop&q=80',
                'https://github.com/stylesheet48-max/style-sheet-agent/tree/main/catalog',
                json_encode(['chat_input', 'chat_feed']),
                json_encode(['free', 'premium', 'ultra']),
                10
            ]
        ];
        $adStmt = $pdo->prepare("
            INSERT INTO ad_campaigns (campaign_id, title, description, image_url, destination_url, placements_json, eligible_plans_json, frequency_cap)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        foreach ($seedAds as $sa) {
            $adStmt->execute($sa);
        }
    }

    // Seed default admin: admin / admin123
    $defaultHash = password_hash('admin123', PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT OR IGNORE INTO admin_users (username, password_hash) VALUES ('admin', ?)");
    $stmt->execute([$defaultHash]);

    // Seed Default Plans
    $plans = [
        [
            'free', 'Free Explorer',
            'Basic access to lightweight AI models and community templates.',
            0.00, 3650,
            json_encode(['gpt-4o-mini', 'claude-3-haiku', 'gemini-2.0-flash']),
            json_encode(['daily_requests' => 50, 'max_context_tokens' => 16000, 'mcp_connections' => 2])
        ],
        [
            'premium', 'Premium Pro',
            'Advanced AI agent orchestration, all coding models, and premium tools.',
            14.99, 30,
            json_encode(['gpt-4o', 'claude-3.5-sonnet', 'deepseek-v3', 'gemini-2.0-flash', 'whisper-large']),
            json_encode(['daily_requests' => 500, 'max_context_tokens' => 64000, 'mcp_connections' => 10])
        ],
        [
            'ultra', 'Ultra Architect',
            'Full enterprise autonomous workflows, unlimited MCP nodes, and Ultra packages.',
            39.99, 30,
            json_encode(['gpt-4o', 'o1', 'o3-mini', 'claude-3.5-sonnet', 'deepseek-r1', 'gemini-2.0-pro']),
            json_encode(['daily_requests' => 2500, 'max_context_tokens' => 200000, 'mcp_connections' => 50])
        ]
    ];

    $checkPlan = $pdo->query("SELECT COUNT(*) FROM subscription_plans")->fetchColumn();
    if ($checkPlan == 0) {
        $pStmt = $pdo->prepare("
            INSERT INTO subscription_plans (plan_key, name, description, price_monthly, duration_days, ai_models_json, usage_limits_json)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        foreach ($plans as $p) {
            $pStmt->execute($p);
        }
    }

    // Seed Default Demo User: demo@stylesheet.agent / demo123 (Plan: premium)
    $checkUser = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($checkUser == 0) {
        $userHash = password_hash('demo123', PASSWORD_BCRYPT);
        $uStmt = $pdo->prepare("
            INSERT INTO users (uid, email, password_hash, full_name, plan_id, plan_expires_at, status)
            VALUES (?, ?, ?, ?, 'premium', datetime('now', '+30 days'), 'active')
        ");
        $uStmt->execute(['usr_' . bin2hex(random_bytes(8)), 'demo@stylesheet.agent', $userHash, 'Stylesheet Architect']);
    }

    // Seed Default Marketplace Packages across categories
    $checkPackages = $pdo->query("SELECT COUNT(*) FROM marketplace_packages")->fetchColumn();
    if ($checkPackages == 0) {
        $seedPackages = [
            [
                'pkg-glass-ui', 'Templates', 'Glassmorphism Design System',
                'Comprehensive frosted glass theme tokens, UI components, and animations for React Native & Web.',
                '1.2.0', 'STYLE SHEET LABS', 'Layers',
                json_encode(['free', 'premium', 'ultra']),
                json_encode(['filesystem', 'theme_override']),
                'https://raw.githubusercontent.com/stylesheet48-max/style-sheet-agent/main/catalog/templates/glass-ui.json'
            ],
            [
                'pkg-git-flow', 'Skills', 'Autonomous Git Workflow',
                'Skill that automates git branches, smart commit generation, and conflict resolution.',
                '2.0.1', 'DevOps Core', 'GitBranch',
                json_encode(['free', 'premium', 'ultra']),
                json_encode(['terminal_exec', 'git_repo']),
                'https://raw.githubusercontent.com/stylesheet48-max/style-sheet-agent/main/catalog/skills/git-flow.json'
            ],
            [
                'pkg-postgres-mcp', 'MCP Servers', 'PostgreSQL Cloud MCP Server',
                'Model Context Protocol server for direct database schema introspection and query execution.',
                '1.0.4', 'DataStack', 'Database',
                json_encode(['premium', 'ultra']),
                json_encode(['network_access', 'mcp_bridge']),
                'https://raw.githubusercontent.com/stylesheet48-max/style-sheet-agent/main/catalog/mcp/postgres.json'
            ],
            [
                'pkg-code-auditor', 'AI Agents', 'Full-Stack Code Auditor Agent',
                'Specialized autonomous security review agent with OWASP Top 10 vulnerability scanner.',
                '3.1.0', 'SecAgent Inc', 'ShieldAlert',
                json_encode(['premium', 'ultra']),
                json_encode(['file_read', 'ast_parser']),
                'https://raw.githubusercontent.com/stylesheet48-max/style-sheet-agent/main/catalog/agents/code-auditor.json'
            ],
            [
                'pkg-ci-orchestrator', 'Workflows', 'GitHub Actions Pipeline Orchestrator',
                'Automated multi-stage workflow pipeline for building Android APKs and testing React Native apps.',
                '1.5.0', 'STYLE SHEET LABS', 'Cpu',
                json_encode(['ultra']),
                json_encode(['cloud_api', 'github_oauth']),
                'https://raw.githubusercontent.com/stylesheet48-max/style-sheet-agent/main/catalog/workflows/ci.json'
            ],
            [
                'pkg-math-tools', 'Plugins', 'Math & Symbolic Calculus Plugin',
                'High performance Python and WASM symbolic algebra and plotting tools for AI calculations.',
                '1.1.2', 'OpenScientific', 'Calculator',
                json_encode(['free', 'premium', 'ultra']),
                json_encode(['isolated_sandbox']),
                'https://raw.githubusercontent.com/stylesheet48-max/style-sheet-agent/main/catalog/plugins/math.json'
            ],
            [
                'pkg-rn-native-kit', 'Code Packages', 'React Native Glassmorphism Essentials',
                'Prebuilt TSX layout components with animated blur effects and glow borders.',
                '2.3.0', 'STYLE SHEET LABS', 'Box',
                json_encode(['free', 'premium', 'ultra']),
                json_encode(['project_import']),
                'https://raw.githubusercontent.com/stylesheet48-max/style-sheet-agent/main/catalog/packages/rn-kit.json'
            ],
            [
                'pkg-react-native-knowledge', 'Knowledge Packs', 'React Native 0.86 Architecture Guide',
                'Indexed vector knowledge pack covering Bridgeless Mode, Fabric render pipeline, and TurboModules.',
                '1.0.0', 'DocEngine', 'BookOpen',
                json_encode(['premium', 'ultra']),
                json_encode(['agent_context']),
                'https://raw.githubusercontent.com/stylesheet48-max/style-sheet-agent/main/catalog/knowledge/rn-guide.json'
            ],
            [
                'pkg-profiler-tools', 'Developer Tools', 'Mobile Performance Profiler & Memory Leak Detector',
                'Real-time frame drops, bundle memory analysis, and JNI bridge latency inspector.',
                '2.1.0', 'CorePerf', 'Gauge',
                json_encode(['ultra']),
                json_encode(['profiler_hook', 'memory_monitor']),
                'https://raw.githubusercontent.com/stylesheet48-max/style-sheet-agent/main/catalog/tools/profiler.json'
            ]
        ];

        $pkgStmt = $pdo->prepare("
            INSERT INTO marketplace_packages (package_id, category, title, description, version, author, icon_name, eligible_plans_json, permissions_json, download_url, is_published)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
        ");
        foreach ($seedPackages as $sp) {
            $pkgStmt->execute($sp);
        }
    }
}

// Authentication & Token Helpers
function generateUserToken(array $user): string {
    $payload = [
        'sub' => $user['id'],
        'uid' => $user['uid'],
        'email' => $user['email'],
        'plan' => $user['plan_id'],
        'iat' => time(),
        'exp' => time() + (30 * 86400) // 30 days
    ];
    $header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
    $body = base64_encode(json_encode($payload));
    $signature = hash_hmac('sha256', "$header.$body", JWT_SECRET);
    return "$header.$body.$signature";
}

function verifyUserToken(?string $authHeader): ?array {
    if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        return null;
    }
    $jwt = $matches[1];
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) {
        return null;
    }
    [$header, $body, $sig] = $parts;
    $expectedSig = hash_hmac('sha256', "$header.$body", JWT_SECRET);
    if (!hash_equals($expectedSig, $sig)) {
        return null;
    }
    $payload = json_decode(base64_decode($body), true);
    if (!$payload || !isset($payload['exp']) || $payload['exp'] < time()) {
        return null;
    }
    return $payload;
}

function auditLog(PDO $pdo, ?int $userId, string $action, string $details): void {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $action, $details, $ip]);
    } catch (Exception $e) {
        // Silently ignore logging failures
    }
}

function isLoggedIn(): bool {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}
