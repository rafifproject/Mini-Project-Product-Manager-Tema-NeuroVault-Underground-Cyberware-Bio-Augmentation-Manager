<?php
/**
 * NeuroVault — CREATE: Registrasi Implan Cyberware Baru (create.php)
 * 
 * Form registration with full server-side validation.
 * Sticky form on validation failure.
 * Duplicate name handled via PDOException 23000.
 * PRG pattern after successful insert.
 */

session_start();
require_once __DIR__ . '/../config/db.php';

// ── Initialize CSRF Token ────────────────────────────────────
$_SESSION['csrf'] ??= bin2hex(random_bytes(32));

// ── Read Theme Preference ────────────────────────────────────
$theme = $_COOKIE['neurovault_theme'] ?? 'light';

// ── Get Stats for Sidebar ────────────────────────────────────
$countStmt  = $pdo->query("SELECT COUNT(*) as total FROM products");
$totalItems = (int)$countStmt->fetch()['total'];

// ── Category Counts for Sidebar ──────────────────────────────
$catStmt   = $pdo->query("SELECT category, COUNT(*) as cnt FROM products GROUP BY category ORDER BY category");
$catCounts = $catStmt->fetchAll();
$catCountMap = [];
foreach ($catCounts as $cc) {
    $catCountMap[$cc['category']] = $cc['cnt'];
}

// ── Escape Helper ────────────────────────────────────────────
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function getCatClass(string $cat): string {
    $map = ['Neural Implants' => 'neural', 'Combat Cyberware' => 'combat', 'Ocular Optics' => 'ocular', 'Bio-Organs' => 'bio'];
    return $map[$cat] ?? 'default';
}

// ── Form State ───────────────────────────────────────────────
$errors      = [];
$old         = ['name' => '', 'category' => '', 'price' => '', 'stock' => '', 'description' => ''];

// ── POST Handler ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize text inputs
    $name        = trim($_POST['name'] ?? '');
    $category    = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price       = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $stock       = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT);

    // Preserve old values for sticky form
    $old['name']        = $name;
    $old['category']    = $category;
    $old['description'] = $description;
    $old['price']       = $_POST['price'] ?? '';
    $old['stock']       = $_POST['stock'] ?? '';

    // ── Server-Side Validation ───────────────────────────────
    if (mb_strlen($name) < 3) {
        $errors[] = 'Nama implan minimal 3 karakter.';
    }

    if (empty($category)) {
        $errors[] = 'Kategori cyberware wajib dipilih.';
    }

    if ($price === false || $price <= 0) {
        $errors[] = 'Harga kredit harus berupa angka valid dan lebih dari 0.';
    }

    if ($stock === false || $stock < 0) {
        $errors[] = 'Kuantitas stok harus berupa bilangan bulat dan tidak boleh negatif.';
    }

    // ── Insert if No Errors ──────────────────────────────────
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO products (name, category, description, price, stock) VALUES (:name, :category, :description, :price, :stock)"
            );
            $stmt->execute([
                ':name'        => $name,
                ':category'    => $category,
                ':description' => $description ?: null,
                ':price'       => $price,
                ':stock'       => $stock,
            ]);

            // PRG: Redirect to prevent resubmit
            header("Location: index.php?status=created");
            exit;

        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $errors[] = 'Nama implan sudah terdaftar dalam sistem. Gunakan nama yang berbeda.';
            } else {
                $errors[] = 'Terjadi kesalahan sistem: ' . $e->getMessage();
            }
        }
    }
}

// ── Category Options ─────────────────────────────────────────
$categories = ['Neural Implants', 'Combat Cyberware', 'Ocular Optics', 'Bio-Organs', 'Umum'];
?>
<!DOCTYPE html>
<html lang="id" data-theme="<?= e($theme) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="NeuroVault — Registrasi implan cyberware baru ke vault repository.">
    <title>NeuroVault — Daftarkan Implan Baru</title>
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
                <a href="index.php" class="nav-item" id="navVault">
                    <span class="nav-icon">🗂️</span>
                    <span>Vault Inventory</span>
                    <span class="nav-badge"><?= $totalItems ?></span>
                </a>
                <a href="create.php" class="nav-item active" id="navCreate">
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
                        <a href="index.php" class="category-item" id="filterAll">
                            <span class="category-dot all"></span>
                            <span>All Augmentations</span>
                            <span class="category-count"><?= $totalItems ?></span>
                        </a>
                    </li>
                    <?php
                    $allCategories = ['Neural Implants', 'Combat Cyberware', 'Ocular Optics', 'Bio-Organs'];
                    foreach ($allCategories as $cat):
                        $cls = getCatClass($cat);
                        $cnt = $catCountMap[$cat] ?? 0;
                    ?>
                    <li>
                        <a href="index.php?category=<?= urlencode($cat) ?>" class="category-item" id="filter<?= str_replace(' ', '', $cat) ?>">
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
            <div class="form-page-header">
                <a href="index.php" class="back-link" id="backLink">← Kembali ke Katalog Vault (index.php)</a>

                <div class="form-title">
                    <span>Registrasi Implan Cyberware Baru</span>
                    <span class="file-badge">create.php</span>
                </div>
                <p class="form-subtitle">FORMULIR VALIDASI DUA-ARAH // STANDAR ENKRIPSI POST & CSRF DILINDUNGI // ENKRIPSI SH-256</p>
            </div>

            <!-- Error Messages -->
            <?php if (!empty($errors)): ?>
            <ul class="error-list" id="errorList">
                <?php foreach ($errors as $err): ?>
                <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>

            <!-- Registration Form -->
            <form method="POST" action="create.php" class="form-container" id="createForm">
                <!-- Nama Implan -->
                <div class="form-row single">
                    <div class="form-group">
                        <label class="form-label" for="inputName">
                            <span>Nama Unit Implan <span class="required">*</span></span>
                            <span class="form-hint">Wajib diisi</span>
                        </label>
                        <input type="text" 
                               name="name" 
                               id="inputName" 
                               class="form-input" 
                               placeholder="Contoh: Netwatch Netdriver Mk.5"
                               value="<?= e($old['name']) ?>"
                               required
                               minlength="3"
                               maxlength="100">
                    </div>
                </div>

                <!-- Kategori + Harga -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="inputCategory">
                            <span>Kategori Cyberware <span class="required">*</span></span>
                        </label>
                        <select name="category" id="inputCategory" class="form-select" required>
                            <option value="">Pilih Kategori...</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= e($cat) ?>" <?= $old['category'] === $cat ? 'selected' : '' ?>>
                                <?= e($cat) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="inputPrice">
                            <span>Harga Kredit (§ Eurodollar) <span class="required">*</span></span>
                        </label>
                        <input type="number" 
                               name="price" 
                               id="inputPrice" 
                               class="form-input" 
                               placeholder="25000"
                               value="<?= e($old['price']) ?>"
                               required
                               min="1"
                               step="any">
                    </div>
                </div>

                <!-- Stok + Serial -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="inputStock">
                            <span>Kuantitas Stok Awal <span class="required">*</span></span>
                        </label>
                        <input type="number" 
                               name="stock" 
                               id="inputStock" 
                               class="form-input" 
                               placeholder="1"
                               value="<?= e($old['stock']) ?>"
                               required
                               min="0"
                               step="1">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="inputSerial">
                            <span>Nomor Serial Vault (Auto)</span>
                            <span class="form-hint">Read-only sistem</span>
                        </label>
                        <input type="text" 
                               id="inputSerial" 
                               class="form-input readonly" 
                               value="#NV-AUTO-GENERATED" 
                               readonly 
                               tabindex="-1">
                    </div>
                </div>

                <!-- Description -->
                <div class="form-row single">
                    <div class="form-group">
                        <label class="form-label" for="inputDesc">
                            <span>Spesifikasi & Diagnostik Medis</span>
                            <span class="form-hint">Opsional</span>
                        </label>
                        <textarea name="description" 
                                  id="inputDesc" 
                                  class="form-textarea"
                                  placeholder="Tuliskan spesifikasi voltase sinaptik, kompatibilitas ripperdoc, atau batasan toleransi cyberpsychosis..."
                                  rows="4"><?= e($old['description']) ?></textarea>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <a href="index.php" class="btn btn-secondary" id="btnCancel">Batal / Kembali</a>
                    <button type="reset" class="btn btn-reset" id="btnReset">Reset Form</button>
                    <button type="submit" class="btn btn-primary" id="btnSubmit">
                        📦 Simpan ke Vault Repository
                    </button>
                </div>
            </form>

            <!-- Terminal Protocol Info Box -->
            <div class="protocol-box" id="protocolBox">
                <div class="protocol-title">
                    🔒 TERMINAL PROTOCOL // PROTOKOL & REGULASI RIPPERDOC
                </div>
                <div class="protocol-item">
                    <span class="highlight">Verifikasi Hardware:</span> Setiap unit implan yang didaftarkan ke pasar gelap otomatis didelegasikan ke token SHA-256 untuk audit desentralisasi.
                </div>
                <div class="protocol-item">
                    <strong>Peringatan Cyberpsychosis:</strong> Implan grade militer (Combat Cyberware) wajib menyertakan voltase beban saraf pada kolom spesifikasi teknis.
                </div>
                <div class="protocol-item">
                    <span class="highlight">Otentikasi Sandi:</span> Modifikasi stok dilakukan real-time pada cold-storage node NeuroVault dengan hand-shake token kriptografis.
                </div>
            </div>
        </main>
    </div>

    <script>
    // ── Theme Toggle ─────────────────────────────────────────
    (function() {
        const html = document.documentElement;
        const btn  = document.getElementById('themeToggleBtn');
        const icon = document.getElementById('themeIcon');
        const name = document.getElementById('themeName');
        btn.addEventListener('click', function() {
            const next = html.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
            html.setAttribute('data-theme', next);
            document.cookie = 'neurovault_theme=' + next + ';path=/;max-age=31536000';
            icon.textContent = next === 'light' ? '☀️' : '🌙';
            name.textContent = next === 'light' ? 'Clean Light' : 'Cyber Dark';
        });
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
