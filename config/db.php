<?php
/**
 * NeuroVault — PDO Database Connection Configuration
 * 
 * Centralized MySQL connection via PHP Data Objects (PDO).
 * All attributes enforced per security directive:
 *   - ERRMODE_EXCEPTION for error handling
 *   - FETCH_ASSOC as default fetch mode
 *   - Emulated prepares DISABLED for true parameterized queries
 */

$host    = 'localhost';
$dbname  = 'store_db';
$user    = 'root';
$pass    = '';
$charset = 'utf8mb4';

$dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    http_response_code(503);
    echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8">';
    echo '<title>NeuroVault — Connection Error</title>';
    echo '<style>body{background:#0a0e1a;color:#ff0055;font-family:"Courier New",monospace;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;}';
    echo '.err{border:1px solid #ff0055;padding:2rem;max-width:500px;text-align:center;border-radius:8px;background:#131b2e;}';
    echo 'h1{font-size:1.2rem;margin-bottom:1rem;} p{color:#94a3b8;font-size:.85rem;}</style></head>';
    echo '<body><div class="err"><h1>⛔ DATABASE CONNECTION FAILED</h1>';
    echo '<p>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
    echo '</div></body></html>';
    exit;
}
