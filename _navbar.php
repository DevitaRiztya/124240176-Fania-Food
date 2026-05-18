<?php
// ============================================================
//  admin/_navbar.php  – Shared Navbar Admin
//  Di-include di setiap halaman admin
// ============================================================
?>
<nav class="navbar navbar-expand-lg navbar-dark" style="background:linear-gradient(135deg,#2D6A4F,#1B4332)">
    <div class="container-fluid px-4">
        <span class="navbar-brand fw-800" style="font-family:'Nunito',sans-serif;font-size:1.3rem;">
            🧊 Fania Food <small class="badge bg-warning text-dark ms-2 fw-700" style="font-size:.65rem;">ADMIN</small>
        </span>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navAdmin">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navAdmin">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF'])=='index.php'?'active':'' ?>" href="index.php">
                        <i class="bi bi-speedometer2 me-1"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF'])=='produk.php'?'active':'' ?>" href="produk.php">
                        <i class="bi bi-box-seam me-1"></i>Produk
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF'])=='stok.php'?'active':'' ?>" href="stok.php">
                        <i class="bi bi-arrow-down-circle me-1"></i>Stok Masuk
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF'])=='pesanan.php'?'active':'' ?>" href="pesanan.php">
                        <i class="bi bi-receipt me-1"></i>Pesanan
                    </a>
                </li>
            </ul>
            <span class="navbar-text text-white-50 me-3 small">
                👤 <?= htmlspecialchars($_SESSION['nama_lengkap']) ?>
            </span>
            <a href="../logout.php" class="btn btn-sm btn-outline-light rounded-pill">Logout</a>
        </div>
    </div>
</nav>
