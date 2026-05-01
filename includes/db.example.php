<?php
// ── Database Configuration ──────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_USER', 'root');        // Change if you set a MySQL password
define('DB_PASS', '');            // Default XAMPP has no password
define('DB_NAME', 'scam_db');

// ── Ollama Local AI Config ───────────────────────────────────
define('OLLAMA_URL',   'http://localhost:11434/api/chat');
define('OLLAMA_MODEL', 'llama3.1:8b');

// ── App Settings ─────────────────────────────────────────────
define('APP_NAME',    'ScamGuard');
define('APP_URL',     'http://localhost/scam_platform');
define('UPLOAD_DIR',  __DIR__ . '/../assets/uploads/');
define('UPLOAD_URL',  APP_URL . '/assets/uploads/');
define('MAX_FILE_MB', 5);

// ── Database Connection (PDO) ────────────────────────────────
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die('<div style="font-family:monospace;padding:2rem;background:#1e1e1e;color:#f87171;">
         <b>Database connection failed:</b><br>' . htmlspecialchars($e->getMessage()) .
         '<br><br>Make sure XAMPP MySQL is running and the database <b>scam_db</b> exists.</div>');
}

// ── Session Start ────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
