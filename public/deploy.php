<?php
// ============================================================
// deploy.php — GitHub Webhook Auto-Deployment Script
// Letakkan file ini di: public/deploy.php
// Webhook URL: https://internalsys.my.id/deploy.php?token=40bb92c0ad5195c80eaa5c50c936d997a8221819757808e1
// ============================================================

$secret_token = '40bb92c0ad5195c80eaa5c50c936d997a8221819757808e1';

// 1. Security Check
if (!isset($_GET['token']) || $_GET['token'] !== $secret_token) {
    http_response_code(403);
    die('Unauthorized access.');
}

echo "<h2>KehadiranApp — Auto Deployment</h2>";

// 2. Check if shell_exec is available
if (!function_exists('shell_exec') || shell_exec('echo test') === null) {
    echo "<p style='color:red'><strong>ERROR:</strong> shell_exec() is disabled on this server. "
       . "You must manually trigger deployment from cPanel &rarr; Git Version Control.</p>";
    exit;
}

// 3. Repository path (cPanel Git Version Control internal repo)
$repo_path = '/home/gere1931/repositories/internalsys';

if (!is_dir($repo_path)) {
    echo "<p style='color:red'><strong>ERROR:</strong> Repository path not found: {$repo_path}</p>";
    echo "<p>Check cPanel &rarr; Git Version Control for the exact repository path.</p>";
    exit;
}

echo "<p>Starting deployment...</p>";

// 4. Pull latest code from GitHub
$pull_output = shell_exec("cd " . escapeshellarg($repo_path) . " && git pull origin main 2>&1");
echo "<h3>Git Pull Result:</h3><pre>" . htmlspecialchars($pull_output) . "</pre>";

// 5. Trigger cPanel deployment tasks (.cpanel.yml)
$deploy_output = shell_exec("uapi VersionControl deployment repository_root=" . escapeshellarg($repo_path) . " 2>&1");
echo "<h3>cPanel Deployment Result:</h3><pre>" . htmlspecialchars($deploy_output) . "</pre>";

echo "<p style='color:green'><strong>Done.</strong></p>";
