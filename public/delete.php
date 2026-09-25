<?php
/**
 * NeuroVault — DELETE Handler (Anti-CSRF Protected)
 * 
 * Secure item deactivation via POST method only.
 * CSRF token verification using hash_equals().
 * PRG pattern enforced after successful deletion.
 */

session_start();
require_once __DIR__ . '/../config/db.php';

// ── Guard: Reject non-POST requests ─────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8">';
    echo '<title>405 — Method Not Allowed</title>';
    echo '<style>body{background:#0a0e1a;color:#ff0055;font-family:"Courier New",monospace;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;}';
    echo '.err{border:1px solid #ff0055;padding:2rem;max-width:500px;text-align:center;border-radius:8px;background:#131b2e;}';
    echo 'h1{font-size:1.2rem;margin-bottom:1rem;} p{color:#94a3b8;font-size:.85rem;} a{color:#00f2fe;}</style></head>';
    echo '<body><div class="err"><h1>⛔ 405 — METHOD NOT ALLOWED</h1>';
    echo '<p>Operasi penghapusan hanya dapat diakses melalui metode POST.</p>';
    echo '<p><a href="index.php">← Kembali ke Vault</a></p>';
    echo '</div></body></html>';
    exit;
}

// ── Guard: CSRF Token Verification ───────────────────────────
if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
    http_response_code(403);
    echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8">';
    echo '<title>403 — Forbidden</title>';
    echo '<style>body{background:#0a0e1a;color:#ff0055;font-family:"Courier New",monospace;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;}';
    echo '.err{border:1px solid #ff0055;padding:2rem;max-width:500px;text-align:center;border-radius:8px;background:#131b2e;}';
    echo 'h1{font-size:1.2rem;margin-bottom:1rem;} p{color:#94a3b8;font-size:.85rem;} a{color:#00f2fe;}</style></head>';
    echo '<body><div class="err"><h1>🔒 403 — CSRF TOKEN MISMATCH</h1>';
    echo '<p>Token keamanan tidak valid atau telah kedaluwarsa. Permintaan ditolak.</p>';
    echo '<p><a href="index.php">← Kembali ke Vault</a></p>';
    echo '</div></body></html>';
    exit;
}

// ── Guard: Validate ID ───────────────────────────────────────
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(400);
    echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8">';
    echo '<title>400 — Bad Request</title>';
    echo '<style>body{background:#0a0e1a;color:#ff0055;font-family:"Courier New",monospace;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;}';
    echo '.err{border:1px solid #ff0055;padding:2rem;max-width:500px;text-align:center;border-radius:8px;background:#131b2e;}';
    echo 'h1{font-size:1.2rem;margin-bottom:1rem;} p{color:#94a3b8;font-size:.85rem;} a{color:#00f2fe;}</style></head>';
    echo '<body><div class="err"><h1>⚠️ 400 — INVALID REQUEST</h1>';
    echo '<p>ID item tidak valid.</p>';
    echo '<p><a href="index.php">← Kembali ke Vault</a></p>';
    echo '</div></body></html>';
    exit;
}

// ── Execute Deletion via Prepared Statement ──────────────────
$stmt = $pdo->prepare("DELETE FROM products WHERE id = :id");
$stmt->execute([':id' => $id]);

// ── PRG Redirect ─────────────────────────────────────────────
header("Location: index.php?status=deleted");
exit;
