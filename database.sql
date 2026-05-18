-- ============================================================
--  DATABASE: db_fania_food
--  Sistem Informasi Frozen Food - Fania Food
-- ============================================================

CREATE DATABASE IF NOT EXISTS db_fania_food
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE db_fania_food;

-- ============================================================
--  TABEL: users
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(100) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,       -- bcrypt hash
    nama_lengkap VARCHAR(150) NOT NULL,
    role        ENUM('pelanggan','admin','kepala_toko') NOT NULL DEFAULT 'pelanggan',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
--  TABEL: produk
-- ============================================================
CREATE TABLE IF NOT EXISTS produk (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    nama_produk     VARCHAR(200) NOT NULL,
    harga           DECIMAL(12,2) NOT NULL DEFAULT 0,
    stok            INT NOT NULL DEFAULT 0,
    foto            VARCHAR(255) DEFAULT 'default.jpg',
    deskripsi       TEXT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
--  TABEL: pesanan (header)
-- ============================================================
CREATE TABLE IF NOT EXISTS pesanan (
    id_pesanan      INT AUTO_INCREMENT PRIMARY KEY,
    id_user         INT NOT NULL,
    total_harga     DECIMAL(14,2) NOT NULL DEFAULT 0,
    status_bayar    ENUM('Menunggu Pembayaran','Diproses','Dikirim','Selesai','Dibatalkan')
                    NOT NULL DEFAULT 'Menunggu Pembayaran',
    bukti_bayar     VARCHAR(255) DEFAULT NULL,
    alamat_pengiriman TEXT,
    catatan         TEXT,
    tanggal         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
--  TABEL: detail_pesanan (item per pesanan)
-- ============================================================
CREATE TABLE IF NOT EXISTS detail_pesanan (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    id_pesanan  INT NOT NULL,
    id_produk   INT NOT NULL,
    jumlah      INT NOT NULL DEFAULT 1,
    harga_satuan DECIMAL(12,2) NOT NULL,
    subtotal    DECIMAL(14,2) GENERATED ALWAYS AS (jumlah * harga_satuan) STORED,
    FOREIGN KEY (id_pesanan) REFERENCES pesanan(id_pesanan) ON DELETE CASCADE,
    FOREIGN KEY (id_produk)  REFERENCES produk(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================================
--  TABEL: stok_log  (riwayat stok masuk)
-- ============================================================
CREATE TABLE IF NOT EXISTS stok_log (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    id_produk   INT NOT NULL,
    jumlah      INT NOT NULL,
    keterangan  VARCHAR(255) DEFAULT 'Stok masuk',
    id_admin    INT NOT NULL,
    tanggal     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_produk) REFERENCES produk(id) ON DELETE CASCADE,
    FOREIGN KEY (id_admin)  REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================================
--  DUMMY DATA – Users
--  password "admin123"   → bcrypt
--  password "toko123"    → bcrypt
--  password "pelanggan1" → bcrypt
--  (Di-hash ulang di PHP: password_hash('...', PASSWORD_BCRYPT))
-- ============================================================
INSERT INTO users (username, password, nama_lengkap, role) VALUES
('admin',       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin Fania Food',   'admin'),
('kepalatoko',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Kepala Toko Fania',  'kepala_toko'),
('budi',        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Budi Santoso',       'pelanggan'),
('siti',        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Siti Rahayu',        'pelanggan');
-- CATATAN: Hash di atas adalah hash dari string "password"
-- Ganti dengan: php -r "echo password_hash('admin123', PASSWORD_BCRYPT);"

-- ============================================================
--  DUMMY DATA – Produk
-- ============================================================
INSERT INTO produk (nama_produk, harga, stok, foto, deskripsi) VALUES
('Nugget Ayam Crispy 500g',   25000, 50, 'nugget-ayam.jpg',   'Nugget ayam renyah, cocok untuk camilan keluarga.'),
('Sosis Sapi Premium 250g',   18000, 80, 'sosis-sapi.jpg',    'Sosis sapi pilihan, bebas pengawet berbahaya.'),
('Bakso Ikan Jumbo 300g',     22000, 40, 'bakso-ikan.jpg',    'Bakso ikan segar dengan tekstur kenyal.'),
('Dimsum Ayam Udang 200g',    30000, 30, 'dimsum.jpg',        'Dimsum premium isi ayam dan udang segar.'),
('Kentang Goreng Beku 1kg',   20000, 60, 'kentang-goreng.jpg','Kentang goreng beku siap masak, krenyess!'),
('Cireng Isi Keju 300g',      15000, 70, 'cireng.jpg',        'Cireng isi keju mozzarella, jajanan hits.'),
('Chicken Karage 400g',       35000, 25, 'karage.jpg',        'Ayam karage ala restoran Jepang.'),
('Pempek Kapal Selam 5pcs',   40000, 20, 'pempek.jpg',        'Pempek Palembang isi telur, autentik.');

-- ============================================================
--  DUMMY DATA – Pesanan
-- ============================================================
INSERT INTO pesanan (id_user, total_harga, status_bayar, bukti_bayar, alamat_pengiriman, tanggal) VALUES
(3, 65000, 'Diproses',              'bukti_1.jpg', 'Jl. Merdeka No.5, Yogyakarta',   NOW()),
(4, 55000, 'Menunggu Pembayaran',   NULL,          'Jl. Sudirman No.10, Sleman',     NOW()),
(3, 40000, 'Selesai',              'bukti_2.jpg', 'Jl. Merdeka No.5, Yogyakarta',   DATE_SUB(NOW(), INTERVAL 3 DAY));

INSERT INTO detail_pesanan (id_pesanan, id_produk, jumlah, harga_satuan) VALUES
(1, 1, 2, 25000),
(1, 6, 1, 15000),
(2, 2, 2, 18000),
(2, 5, 1, 20000),  -- 20000 bukan 19000 agar pas 55000 ... disesuaikan
(3, 8, 1, 40000);
