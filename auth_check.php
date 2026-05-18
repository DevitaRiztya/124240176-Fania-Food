<?php
// ============================================================
//  auth_check.php  – Session Guard (sertakan di tiap halaman protected)
//  Penggunaan:
//    require_once '../auth_check.php';
//    authCheck(['admin']);              // hanya admin
//    authCheck(['admin','kepala_toko']); // admin & kepala toko
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function authCheck(array $allowedRoles = []) {
    if (!isset($_SESSION['user_id'])) {
        // Belum login → ke halaman login
        header('Location: ' . rootPath() . 'login.php?msg=not_logged_in');
        exit;
    }
    if (!empty($allowedRoles) && !in_array($_SESSION['role'], $allowedRoles, true)) {
        // Role tidak sesuai → tolak akses
        http_response_code(403);
        die('
        <div style="font-family:sans-serif;text-align:center;padding:60px;">
            <h2 style="color:#e74c3c;">⛔ Akses Ditolak</h2>
            <p>Anda tidak memiliki hak akses ke halaman ini.</p>
            <a href="' . rootPath() . 'login.php" style="color:#3498db;">Kembali ke Login</a>
        </div>');
    }
}

// Hitung path relatif ke root (support nested folder)
function rootPath() {
    $depth = substr_count(str_replace('\\','/',dirname($_SERVER['SCRIPT_FILENAME'])),
                          '/fania-food/');
    return $depth > 0 ? str_repeat('../', $depth) : '';
}

function logoutUrl() {
    return rootPath() . 'logout.php';
}
?>
