<?php
/**
 * STYLE SHEET AGENT - Admin Panel Configuration & Database Handler
 */

session_start();

define('APP_NAME', 'STYLE SHEET AGENT');
define('APP_VERSION', '2.1.8');
define('PACKAGE_NAME', 'com.stylesheet.agent');
define('DB_PATH', __DIR__ . '/../database/style_sheet_agent.sqlite');

// Database Initialization (SQLite with PDO)
function getDBConnection(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dbDir = dirname(DB_PATH);
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0777, true);
        }
        $isNew = !file_exists(DB_PATH);
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        if ($isNew) {
            initDatabaseSchema($pdo);
        }
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

    // 2. App Releases
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

    // 3. Starter Prompts Catalog
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

    // 4. Chat Tips / Announcements
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS chat_tips (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            text TEXT NOT NULL,
            link_url TEXT,
            is_active INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");

    // 5. Skills Catalog
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS skills_catalog (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            skill_id TEXT UNIQUE NOT NULL,
            name TEXT NOT NULL,
            description TEXT,
            author TEXT,
            default_installed INTEGER DEFAULT 0,
            is_active INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");

    // 6. Devices / Telemetry
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

    // Seed default admin: admin / admin123
    $defaultHash = password_hash('admin123', PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT OR IGNORE INTO admin_users (username, password_hash) VALUES ('admin', ?)");
    $stmt->execute([$defaultHash]);

    // Seed current release v2.1.8
    $stmt = $pdo->prepare("
        INSERT INTO app_releases (version, version_code, apk_url, release_notes, min_supported_version, is_force_update, is_active)
        VALUES ('2.1.8', 2, 'https://github.com/stylesheet48-max/style-sheet-agent/releases/download/v2.1.8/STYLE_SHEET_AGENT.apk',
                'Glassmorphism Visual Identity, Cyan Blue Accents, Standalone GitHub Architecture, New Package Name com.stylesheet.agent.',
                '2.0.0', 0, 1)
    ");
    $stmt->execute();

    // Seed default starter prompts
    $prompts = [
        ['Glassmorphism Card', 'Create a modern React Native Glassmorphic card component with backdrop blur and cyan glow borders.'],
        ['CSS Layout Design', 'Design an ultra-clean mobile-first responsive flex/grid dashboard.'],
        ['System Architecture', 'Analyze my agent workflow and optimize state sync performance.']
    ];
    $stmt = $pdo->prepare("INSERT INTO starter_prompts (label, prompt, sort_order) VALUES (?, ?, ?)");
    foreach ($prompts as $idx => $p) {
        $stmt->execute([$p[0], $p[1], $idx + 1]);
    }

    // Seed default chat tip
    $stmt = $pdo->prepare("INSERT INTO chat_tips (text, link_url, is_active) VALUES (?, ?, 1)");
    $stmt->execute(['Welcome to STYLE SHEET AGENT 2.1.8! Glassmorphism AI workspace ready.', 'https://github.com/stylesheet48-max/style-sheet-agent']);
}

// Authentication Helpers
function isLoggedIn(): bool {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}
