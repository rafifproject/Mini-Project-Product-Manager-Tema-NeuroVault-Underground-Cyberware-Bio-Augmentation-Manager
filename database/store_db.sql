-- ============================================================
-- NeuroVault: Underground Cyberware & Bio-Augmentation Manager
-- Database Schema & Seed Data
-- ============================================================

-- NOTE: Di shared hosting, hapus CREATE DATABASE & USE.
-- Pastikan database sudah dibuat via cPanel/phpMyAdmin, lalu import file ini ke database tersebut.

CREATE TABLE IF NOT EXISTS products (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100)   NOT NULL UNIQUE,
    category    VARCHAR(50)    NOT NULL DEFAULT 'Umum',
    description TEXT           NULL,
    price       DECIMAL(12,2)  NOT NULL,
    stock       INT            NOT NULL DEFAULT 0,
    created_at  TIMESTAMP      DEFAULT CURRENT_TIMESTAMP
);

-- Seed Data: Underground Cyberware & Bio-Augmentations
INSERT INTO products (name, category, description, price, stock) VALUES
('Sandevistan Neural Accelerator Mk.IV', 'Neural Implants',
 'Modul spinal percepatan refleks sinaptik tingkat lanjut. Memperlambat persepsi waktu pengguna hingga 85% selama 12 detik.',
 45000000.00, 3),

('Kiroshi Optical Sensory Gen-3', 'Ocular Optics',
 'Lensa okular resolusi ultra-tinggi dilengkapi overlay pemindaian inframerah, dekompilasi firewall, dan zoom analitik x16.',
 18500000.00, 8),

('Sub-Dermal Titanium Bone Plating', 'Combat Cyberware',
 'Bilah lengan ganda paduan titanium dari saluran superkonduktor panas untuk penetrasi armor komposit militer.',
 32000000.00, 5),

('Synthetic High-Flow Bio-Lungs', 'Bio-Organs',
 'Jantung buatan sirkulasi nano-pump mandiri. Mengeliminasi kelelahan asam laktat serta memberikan ketahanan toksin darah.',
 27500000.00, 4),

('Gorilla Arm Hydraulic Muscle Weave', 'Combat Cyberware',
 'Peningkat kekuatan genggaman biomekanik berat. Mampu mendobrak pintu bunker baja dan menahan recoil senapan berat.',
 39000000.00, 2),

('Reflex Booster Synaptic Chip', 'Neural Implants',
 'Kompilasi chip neural IC dengan injeksi gelombang elektromagnetik untuk membersihkan rekaman memori sensorik sesaat.',
 14000000.00, 11);
