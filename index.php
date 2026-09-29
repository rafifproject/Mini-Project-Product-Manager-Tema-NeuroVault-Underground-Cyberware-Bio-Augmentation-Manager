<?php
/**
 * NeuroVault — Vault Inventory Overview (index.php)
 * 
 * READ & SEARCH: Displays all cyberware items in a flexbox card grid.
 * Supports GET-based search, category filtering, flash status alerts,
 * and CSRF token initialization.
 */

session_start();
require_once __DIR__ . '/config/db.php';

// ── Initialize CSRF Token ────────────────────────────────────
$_SESSION['csrf'] ??= bin2hex(random_bytes(32));

// ── Read Theme Preference ────────────────────────────────────
$theme = $_COOKIE['neurovault_theme'] ?? 'light';

// ── Search & Category Filter Parameters ──────────────────────
$search   = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');

// ── Build Query ──────────────────────────────────────────────
if ($search !== '') {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE name LIKE :q1 OR category LIKE :q2 ORDER BY id DESC");
    $stmt->execute([':q1' => "%{$search}%", ':q2' => "%{$search}%"]);
} elseif ($category !== '') {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE category = :cat ORDER BY id DESC");
    $stmt->execute([':cat' => $category]);
} else {
    $stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
}
$products = $stmt->fetchAll();

// ── Stats Computation ────────────────────────────────────────
$statsStmt    = $pdo->query("SELECT COUNT(*) as total, SUM(price * stock) as valuation, SUM(CASE WHEN stock > 0 THEN 1 ELSE 0 END) as in_stock FROM products");
$stats        = $statsStmt->fetch();
$totalItems   = (int)$stats['total'];
$valuation    = (float)($stats['valuation'] ?? 0);
$inStock      = (int)$stats['in_stock'];
$backorder    = $totalItems - $inStock;

// ── Category Counts ──────────────────────────────────────────
$catStmt   = $pdo->query("SELECT category, COUNT(*) as cnt FROM products GROUP BY category ORDER BY category");
$catCounts = $catStmt->fetchAll();

// ── Status Flash Messages ────────────────────────────────────
$status       = $_GET['status'] ?? '';
$alertMessage = '';
$alertType    = '';

switch ($status) {
    case 'created':
        $alertMessage = 'Implan sibernetik berhasil didaftarkan ke vault.';
        $alertType    = 'success';
        break;
    case 'updated':
        $alertMessage = 'Spesifikasi implan berhasil dimutakhirkan.';
        $alertType    = 'success';
        break;
    case 'deleted':
        $alertMessage = 'Unit implan berhasil dinonaktifkan dan dihapus dari vault.';
        $alertType    = 'success';
        break;
}

// ── Helper: Category Badge Class ─────────────────────────────
function getCatClass(string $cat): string {
    $map = [
        'Neural Implants'  => 'neural',
        'Combat Cyberware' => 'combat',
        'Ocular Optics'    => 'ocular',
        'Bio-Organs'       => 'bio',
    ];
    return $map[$cat] ?? 'default';
}

// ── Helper: Category Badge Icon ──────────────────────────────
function getCatIcon(string $cat): string {
    $map = [
        'Neural Implants'  => '🧠',
        'Combat Cyberware' => '⚔️',
        'Ocular Optics'    => '👁',
        'Bio-Organs'       => '🫀',
    ];
    return $map[$cat] ?? '📦';
}

// ── Helper: Serial Number ────────────────────────────────────
function serialNumber(int $id): string {
    return '#NV-' . str_pad($id * 317 % 10000, 4, '0', STR_PAD_LEFT);
}

// ── Helper: Escape ───────────────────────────────────────────
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="id" data-theme="<?= e($theme) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="NeuroVault — Underground Cyberware & Bio-Augmentation Inventory Manager. Konsol katalog dan kontrol hardware black-market.">
    <title>NeuroVault — Vault Inventory Overview</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <!-- Mobile Toggle -->
    <button class="mobile-toggle" id="mobileToggle" aria-label="Toggle sidebar">☰</button>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="app-layout">
        <!-- ═══════ SIDEBAR ═══════ -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="logo-row">
                    <span class="logo-icon">🧬</span>
                    <span class="logo-text">NeuroVault</span>
                    <span class="logo-badge">PRO</span>
                </div>
                <div class="logo-version">v2.4 // Ripperdoc OS Terminal</div>
            </div>

            <div class="sidebar-section">
                <div class="sidebar-label">Command Center</div>
                <a href="index.php" class="nav-item active" id="navVault">
                    <span class="nav-icon">🗂️</span>
                    <span>Vault Inventory</span>
                    <span class="nav-badge"><?= $totalItems ?></span>
                </a>
                <a href="create.php" class="nav-item nav-item-add" id="navCreate">
                    <span class="nav-icon">+</span>
                    <span>Daftarkan Implan Baru</span>
                </a>
            </div>

            <div class="sidebar-action">
                <a href="create.php" class="btn-sidebar-action" id="btnSidebarCreate">
                    + Daftarkan Implan
                </a>
            </div>

            <div class="sidebar-section">
                <div class="sidebar-label">Quick Category Filters</div>
                <ul class="category-list">
                    <li>
                        <a href="index.php" class="category-item <?= ($category === '' && $search === '') ? 'active' : '' ?>" id="filterAll">
                            <span class="category-dot all"></span>
                            <span>All Augmentations</span>
                            <span class="category-count"><?= $totalItems ?></span>
                        </a>
                    </li>
                    <?php
                    $allCategories = ['Neural Implants', 'Combat Cyberware', 'Ocular Optics', 'Bio-Organs'];
                    $catCountMap = [];
                    foreach ($catCounts as $cc) {
                        $catCountMap[$cc['category']] = $cc['cnt'];
                    }
                    foreach ($allCategories as $cat):
                        $cls   = getCatClass($cat);
                        $cnt   = $catCountMap[$cat] ?? 0;
                        $isAct = ($category === $cat) ? 'active' : '';
                    ?>
                    <li>
                        <a href="index.php?category=<?= urlencode($cat) ?>" 
                           class="category-item <?= $isAct ?>"
                           id="filter<?= str_replace(' ', '', $cat) ?>">
                            <span class="category-dot <?= $cls ?>"></span>
                            <span><?= e($cat) ?></span>
                            <span class="category-count"><?= $cnt ?></span>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="sidebar-footer">
                <div class="subnet-status">
                    <span class="status-dot"></span>
                    <span>SUB-NET: 127.0.0.1 // ONLINE</span>
                </div>
                <div class="theme-toggle">
                    <span class="theme-toggle-label" id="themeLabel">
                        <span id="themeIcon"><?= $theme === 'light' ? '☀️' : '🌙' ?></span>
                        <span id="themeName"><?= $theme === 'light' ? 'Clean Light' : 'Cyber Dark' ?></span>
                    </span>
                    <button class="theme-toggle-btn" id="themeToggleBtn">Switch</button>
                </div>
            </div>
        </aside>

        <!-- ═══════ MAIN CONTENT ═══════ -->
        <main class="main-content">
            <!-- Page Header + Search -->
            <div class="page-header">
                <div>
                    <h1 class="page-title">Vault Inventory Overview</h1>
                    <p class="page-subtitle">STATUS // KONSOL KATALOG & KONTROL HARDWARE BLACK-MARKET</p>
                </div>
                <form method="GET" action="index.php" class="search-container" id="searchForm">
                    <input type="text" 
                           name="q" 
                           class="search-input" 
                           id="searchInput"
                           placeholder="Cari nama implan atau kategori..." 
                           value="<?= e($search) ?>"
                           autocomplete="off">
                    <button type="submit" class="search-btn" id="searchBtn">Cari</button>
                </form>
            </div>

            <!-- Flash Alert -->
            <?php if ($alertMessage): ?>
            <div class="alert alert-<?= $alertType ?>" id="flashAlert">
                <span class="alert-icon">⚡</span>
                <span><strong>STATUS UPDATED:</strong> <?= e($alertMessage) ?></span>
                <button class="alert-close" id="alertClose" aria-label="Close alert">✕</button>
            </div>
            <?php endif; ?>

            <!-- Stats Dashboard -->
            <div class="stats-grid">
                <div class="stat-card" id="statTotal">
                    <div class="stat-label">Total Vault Units</div>
                    <div class="stat-value"><?= $totalItems ?> Items</div>
                    <div class="stat-sub">Active repository</div>
                </div>
                <div class="stat-card" id="statValuation">
                    <div class="stat-label">Vault Valuation</div>
                    <div class="stat-value">$ <?= number_format($valuation, 0, ',', '.') ?></div>
                    <div class="stat-sub">US Dollar</div>
                </div>
                <div class="stat-card" id="statStock">
                    <div class="stat-label">Stock Status</div>
                    <div class="stat-value green"><?= $inStock ?> In-Stock</div>
                    <div class="stat-sub"><?= $backorder ?> Backorder alert</div>
                </div>
                <div class="stat-card" id="statCsrf">
                    <div class="stat-label">Cryptographic Token</div>
                    <div class="stat-value mono" style="font-size:0.95rem;">CSRF_PASS_<?= strtoupper(substr($_SESSION['csrf'], 0, 4)) ?></div>
                    <div class="stat-sub">Secure POST handshake</div>
                </div>
            </div>

            <!-- Search Results Info -->
            <?php if ($search !== ''): ?>
            <div class="alert alert-success" style="background:var(--accent-cyan-dim);border-color:var(--accent-cyan);color:var(--accent-cyan);">
                <span class="alert-icon">🔍</span>
                <span>Menampilkan <?= count($products) ?> hasil untuk pencarian: "<strong><?= e($search) ?></strong>"</span>
                <a href="index.php" class="alert-close" aria-label="Clear search">✕</a>
            </div>
            <?php elseif ($category !== ''): ?>
            <div class="alert alert-success" style="background:var(--accent-cyan-dim);border-color:var(--accent-cyan);color:var(--accent-cyan);">
                <span class="alert-icon">🏷️</span>
                <span>Filter aktif: <strong><?= e($category) ?></strong> — <?= count($products) ?> item ditemukan</span>
                <a href="index.php" class="alert-close" aria-label="Clear filter">✕</a>
            </div>
            <?php endif; ?>

            <!-- Inventory Grid -->
            <?php if (empty($products)): ?>
            <div class="empty-state" id="emptyState">
                <div class="empty-state-icon">🔬</div>
                <p class="empty-state-text">Tidak ada implan ditemukan dalam vault.</p>
                <a href="create.php" class="btn btn-primary">+ Daftarkan Implan Baru</a>
            </div>
            <?php else: ?>
            <div class="inventory-grid" id="inventoryGrid">
                <?php foreach ($products as $p):
                    $cls    = getCatClass($p['category']);
                    $icon   = getCatIcon($p['category']);
                    $serial = serialNumber($p['id']);
                ?>
                <div class="cyber-card cat-<?= $cls ?>" id="card-<?= $p['id'] ?>">
                    <div class="card-header">
                        <span class="category-badge badge-<?= $cls ?>">
                            <?= $icon ?> <?= e($p['category']) ?>
                        </span>
                        <span class="card-serial mono"><?= $serial ?></span>
                    </div>

                    <h3 class="card-name"><?= e($p['name']) ?></h3>

                    <?php if (!empty($p['description'])): ?>
                    <p class="card-desc"><?= e($p['description']) ?></p>
                    <?php endif; ?>

                    <div class="card-footer">
                        <div class="card-meta">
                            <div class="price-block">
                                <div class="price-label">Credit Price</div>
                                <div class="price-value">
                                    <span class="price-currency">$</span><?= number_format($p['price'], 0, ',', '.') ?>
                                </div>
                            </div>
                            <span class="stock-badge <?= $p['stock'] > 0 ? 'stock-available' : 'stock-empty' ?>">
                                <span class="stock-dot"></span>
                                Stok: <?= (int)$p['stock'] ?> Unit<?= $p['stock'] == 0 ? ' (Habis)' : '' ?>
                            </span>
                        </div>

                        <div class="card-actions">
                            <a href="edit.php?id=<?= $p['id'] ?>" class="btn-edit" id="editBtn-<?= $p['id'] ?>">
                                🔧 Edit
                            </a>
                            <form method="POST" action="delete.php" class="delete-form"
                                  onsubmit="return confirm('⚠️ Konfirmasi deaktivasi unit implan: <?= e($p['name']) ?>?');">
                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                                <button type="submit" class="btn-delete" id="deleteBtn-<?= $p['id'] ?>">
                                    🗑️ Hapus
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
    // ── Theme Toggle ─────────────────────────────────────────
    (function() {
        const html   = document.documentElement;
        const btn    = document.getElementById('themeToggleBtn');
        const icon   = document.getElementById('themeIcon');
        const name   = document.getElementById('themeName');

        btn.addEventListener('click', function() {
            const current = html.getAttribute('data-theme');
            const next    = current === 'light' ? 'dark' : 'light';
            html.setAttribute('data-theme', next);
            document.cookie = 'neurovault_theme=' + next + ';path=/;max-age=31536000';
            icon.textContent = next === 'light' ? '☀️' : '🌙';
            name.textContent = next === 'light' ? 'Clean Light' : 'Cyber Dark';
        });
    })();

    // ── Alert Close ──────────────────────────────────────────
    (function() {
        const closeBtn = document.getElementById('alertClose');
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                const alert = document.getElementById('flashAlert');
                if (alert) {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-12px)';
                    setTimeout(function() { alert.remove(); }, 250);
                }
            });
        }
    })();

    // ── Mobile Sidebar Toggle ────────────────────────────────
    (function() {
        const toggle  = document.getElementById('mobileToggle');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        toggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('visible');
        });

        overlay.addEventListener('click', function() {
            sidebar.classList.remove('open');
            overlay.classList.remove('visible');
        });
    })();
    </script>
</body>
</html>
