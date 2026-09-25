# 🧬 NeuroVault: Underground Cyberware & Bio-Augmentation Manager

> Sistem Manajemen Inventaris dan Perdagangan Gelap untuk Implan Sibernetik, Augmentasi Neural, dan Organ Sintetis berbasis Web (PHP Native & MySQL).

Sistem ini dirancang untuk operasional klinik sibernetik bawah tanah (**Ripperdoc Clinic**) dengan mengedepankan keamanan tingkat tinggi, arsitektur request yang bersih, serta antarmuka bergaya **cyberpunk dark-terminal murni**.

---

## 📁 Struktur Direktori Proyek

```
product-manager/
├── config/
│   └── db.php               # Konfigurasi & instansiasi koneksi PDO
├── database/
│   └── store_db.sql         # Skema database store_db & tabel products
├── public/
│   ├── assets/
│   │   └── style.css        # Tata letak Box Model & Flexbox bertema Cyberpunk
│   ├── index.php            # READ: Katalog implan, filter pencarian GET, flash alert
│   ├── create.php           # CREATE: Form registrasi implan + validasi + PRG
│   ├── edit.php             # UPDATE: Form modifikasi spesifikasi implan + PRG
│   └── delete.php           # DELETE: Handler deaktivasi item aman (POST + CSRF)
└── README.md                # Dokumentasi sistem & panduan pengujian
```

---

## 🛡️ Arsitektur Keamanan

| Layer | Mekanisme | Implementasi |
|-------|-----------|-------------|
| **Database** | PDO Prepared Statements | Semua query berparameter (`$pdo->prepare()`) |
| **Output** | Anti-XSS Escaping | `htmlspecialchars($data, ENT_QUOTES, 'UTF-8')` |
| **Aksi Destruktif** | Anti-CSRF Token | `hash_equals($_SESSION['csrf'], $_POST['csrf'])` |
| **Request Flow** | Post-Redirect-Get (PRG) | `header("Location: ..."); exit;` |
| **Validasi** | Server-Side Rules | Nama ≥ 3 char, Harga > 0, Stok ≥ 0 |
| **Duplikasi** | Exception Handling | `catch PDOException` kode `23000` |

---

## 🚀 Panduan Instalasi & Setup

### Prasyarat
- PHP ≥ 7.4 (disarankan PHP 8.x)
- MySQL ≥ 5.7 / MariaDB ≥ 10.3
- Web Server (Apache / XAMPP / Laragon)

### Langkah Instalasi

1. **Clone/Copy** direktori `product-manager/` ke direktori web server:
   ```
   Contoh XAMPP: C:\xampp\htdocs\product-manager\
   ```

2. **Import Database** — Buka phpMyAdmin atau MySQL CLI:
   ```sql
   SOURCE /path/to/product-manager/database/store_db.sql;
   ```
   Atau import file `store_db.sql` melalui phpMyAdmin.

3. **Konfigurasi Koneksi** — Edit `config/db.php` jika diperlukan:
   ```php
   $host    = 'localhost';
   $dbname  = 'store_db';
   $user    = 'root';
   $pass    = '';     // Sesuaikan password MySQL Anda
   $charset = 'utf8mb4';
   ```

4. **Akses Aplikasi** di browser:
   ```
   http://localhost/product-manager/public/index.php
   ```

---

## 🎨 Fitur Antarmuka

- **Dual Theme**: Cyber Dark (default) & Clean Light — toggle via tombol Switch di sidebar
- **Sidebar Navigation**: Command Center, Quick Category Filters dengan jumlah item per kategori
- **Stats Dashboard**: Total Unit, Valuasi Vault, Status Stok, Token CSRF
- **Responsive Flexbox Grid**: Kartu implan otomatis menyesuaikan kolom layar
- **Flash Alert System**: Notifikasi status operasi CRUD
- **Category Badges**: Warna unik per kategori (Neural, Combat, Ocular, Bio-Organs)
- **Serial Number Auto-Generated**: Format #NV-XXXX untuk setiap item

---

## 🧪 Matriks Skenario Uji

| No | Skenario | Aksi / Data Masukan | Perilaku yang Diharapkan |
|----|----------|---------------------|--------------------------|
| 1 | Create Sukses | Nama: Titanium Bone Lacing, Kat: Combat Cyberware, Harga: 24000000, Stok: 5 | Tersimpan, redirect ke index, banner hijau muncul |
| 2 | Validasi Nama Pendek | Nama: ZX (< 3 huruf) | Ditolak, pesan: "Nama minimal 3 karakter" |
| 3 | Validasi Angka Negatif | Harga: -1000 atau Stok: -4 | Ditolak dengan pesan kesalahan validasi |
| 4 | Duplikasi Unik | Nama implan yang sudah ada | Exception 23000, pesan elegan |
| 5 | Injeksi XSS | Nama: `<script>alert(1)</script>` | Ditampilkan sebagai teks biasa |
| 6 | PRG (Refresh Test) | Tambah data → tekan F5 | Tidak ada resubmit / duplikasi |
| 7 | GET Delete | URL: `delete.php?id=1` | HTTP 405 Method Not Allowed |
| 8 | CSRF Palsu | POST hapus tanpa/token palsu | HTTP 403 Forbidden |
| 9 | Pencarian GET | Kata kunci: "Neural" | Hasil sesuai query berparameter |
| 10 | Responsif Flexbox | Perkecil jendela browser | Kartu menyusun ulang vertikal |

---

## 🗄️ Skema Database

```sql
CREATE TABLE products (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100)   NOT NULL UNIQUE,
    category    VARCHAR(50)    NOT NULL DEFAULT 'Umum',
    description TEXT           NULL,
    price       DECIMAL(12,2)  NOT NULL,
    stock       INT            NOT NULL DEFAULT 0,
    created_at  TIMESTAMP      DEFAULT CURRENT_TIMESTAMP
);
```

---

## ⚙️ Teknologi

| Komponen | Teknologi |
|----------|-----------|
| Backend | PHP Native (tanpa framework) |
| Database | MySQL via PDO |
| Frontend | HTML5, CSS3 Native (Flexbox), Vanilla JS |
| Font | Inter + JetBrains Mono (Google Fonts) |
| Framework CSS | ❌ Tidak ada (Pure CSS) |
| Library JS | ❌ Tidak ada (Vanilla JS) |

---

> **NeuroVault v2.4** — *Ripperdoc OS Terminal*  
> 🧬 *"Every body part is just hardware waiting for an upgrade."*
