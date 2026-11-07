# STYLE SHEET AGENT - PHP Admin Panel

A lightweight, Glassmorphic PHP administration panel and JSON API gateway for **STYLE SHEET AGENT** (`com.stylesheet.agent`).

---

## 🌟 Key Features

1. **In-App Update Server (`/api/v1/update.php`)**:
   - Manages version checks, APK download URLs, release notes, and mandatory force updates.
2. **Dynamic Prompts & Tips (`/api/v1/starter-prompts.php`, `/api/v1/chat-tip.php`)**:
   - Customize chat suggestions and global announcement bars instantly without rebuilding or republishing the APK.
3. **Fleet Telemetry (`/api/v1/telemetry.php`)**:
   - Track connected device installations, Android versions, and active pings.
4. **Zero-Config Database**:
   - Uses SQLite via PDO (`database/style_sheet_agent.sqlite`). No MySQL installation required unless desired.
5. **Glassmorphism Dark Theme**:
   - Obsidian `#070B14`, frosted glass cards, cyan `#38BDF8` neon accents.

---

## 🚀 How to Run Locally (Termux / Linux / Mac / Windows)

Open your terminal in this directory and start PHP's built-in web server:

```bash
cd /storage/emulated/0/Stylesheet/admin-panel
php -S 0.0.0.0:8080
```

Now open your browser:
- **URL**: `http://localhost:8080` (or `http://YOUR_PHONE_IP:8080`)
- **Default Username**: `admin`
- **Default Password**: `admin123`

---

## 🌐 Deploying to cPanel / VPS / Apache / Nginx

1. Upload the entire `admin-panel` folder to your public directory (e.g. `public_html/agent-admin`).
2. Ensure the `database/` folder and `uploads/` folder have write permissions (`chmod 775 database uploads`).
3. Point your app update configuration to `https://your-domain.com/agent-admin/api/v1/update.php`.

---

## 📡 API Endpoints

- `GET /api/v1/update.php?current_version=2.1.8&version_code=2`
- `GET /api/v1/starter-prompts.php`
- `GET /api/v1/chat-tip.php`
- `POST /api/v1/telemetry.php`
