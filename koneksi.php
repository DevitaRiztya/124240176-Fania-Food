<?php
// ============================================================
//  koneksi.php  – Koneksi Database
//  Letakkan di: htdocs/fania-food/koneksi.php
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');               // Kosong = default XAMPP
define('DB_NAME', 'db_fania_food');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die('<div style="font-family:sans-serif;color:red;padding:20px;">
         ❌ Koneksi database gagal: ' . $conn->connect_error . '
         </div>');
}

$conn->set_charset('utf8mb4');

// ============================================================
//  Helper: Escape string aman
// ============================================================
function esc($conn, $str) {
    return $conn->real_escape_string(htmlspecialchars(trim($str)));
}

// ============================================================
//  Helper: Format Rupiah
// ============================================================
function rupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}
?>
