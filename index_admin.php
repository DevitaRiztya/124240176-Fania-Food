<?php
// ============================================================
//  admin/index.php  – Dashboard Admin
// ============================================================
require_once '../auth_check.php';
authCheck(['admin']);
require_once '../koneksi.php';

// Statistik
$totalProduk    = $conn->query("SELECT COUNT(*) AS c FROM produk")->fetch_assoc()['c'];
$stokHabis      = $conn->query("SELECT COUNT(*) AS c FROM produk WHERE stok = 0")->fetch_assoc()['c'];
$pesananMasuk   = $conn->query("SELECT COUNT(*) AS c FROM pesanan WHERE status_bayar = 'Menunggu Pembayaran'")->fetch_assoc()['c'];
$pesananProses  = $conn->query("SELECT COUNT(*) AS c FROM pesanan WHERE status_bayar IN ('Diproses','Dikirim')")->fetch_assoc()['c'];
$revenueTotal   = $conn->query("SELECT COALESCE(SUM(total_harga),0) AS t FROM pesanan WHERE status_bayar='Selesai'")->fetch_assoc()['t'];
$pesananTerbaru = $conn->query(
    "SELECT p.*, u.nama_lengkap FROM pesanan p
     JOIN users u ON u.id = p.id_user
     ORDER BY p.tanggal DESC LIMIT 5"
);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin – Fania Food</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { font-family:'Nunito',sans-serif; background:#f4f6f9; }
        .card { border:none; border-radius:16px; box-shadow:0 4px 16px rgba(0,0,0,.07); }
        .stat-card { border-radius:16px; border:none; color:#fff; }
        .stat-icon { font-size:2.5rem; opacity:.7; }
    </style>
</head>
<body>
<?php include '_navbar.php'; ?>

<div class="container-fluid p-4">
    <div class="d-flex align-items-center mb-4">
        <div>
            <h4 class="fw-800 mb-0">Dashboard Admin</h4>
            <small class="text-muted">Selamat datang, <?= htmlspecialchars($_SESSION['nama']) ?> 👋 – <?= date('d F Y') ?></small>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card stat-card p-4" style="background:linear-gradient(135deg,#2D6A4F,#40916C)">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <small class="opacity-75">Total Produk</small>
                        <h2 class="fw-800 mb-0"><?= $totalProduk ?></h2>
                    </div>
                    <i class="bi bi-box-seam stat-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card p-4" style="background:linear-gradient(135deg,#DC2626,#EF4444)">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <small class="opacity-75">Stok Habis</small>
                        <h2 class="fw-800 mb-0"><?= $stokHabis ?></h2>
                    </div>
                    <i class="bi bi-exclamation-triangle stat-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card p-4" style="background:linear-gradient(135deg,#D97706,#F59E0B)">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <small class="opacity-75">Menunggu Verifikasi</small>
                        <h2 class="fw-800 mb-0"><?= $pesananMasuk ?></h2>
                    </div>
                    <i class="bi bi-hourglass-split stat-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card p-4" style="background:linear-gradient(135deg,#1D4ED8,#3B82F6)">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <small class="opacity-75">Diproses/Dikirim</small>
                        <h2 class="fw-800 mb-0"><?= $pesananProses ?></h2>
                    </div>
                    <i class="bi bi-truck stat-icon"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Revenue + Pesanan Terbaru -->
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card p-4 h-100 text-center d-flex flex-column justify-content-center" style="background:linear-gradient(135deg,#064E3B,#065F46);color:#fff;">
                <i class="bi bi-cash-coin" style="font-size:3rem;opacity:.6;"></i>
                <h6 class="mt-3 opacity-75">Total Revenue (Selesai)</h6>
                <h3 class="fw-800"><?= rupiah($revenueTotal) ?></h3>
                <a href="pesanan.php" class="btn btn-outline-light rounded-pill mt-3 btn-sm">Lihat Semua Pesanan</a>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card p-4">
                <h6 class="fw-800 mb-3">🕐 Pesanan Terbaru</h6>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light"><tr>
                            <th>ID</th><th>Pelanggan</th><th>Total</th><th>Status</th><th>Waktu</th>
                        </tr></thead>
                        <tbody>
                        <?php
                        $sc = ['Menunggu Pembayaran'=>'warning','Diproses'=>'primary','Dikirim'=>'info','Selesai'=>'success','Dibatalkan'=>'danger'];
                        while ($row = $pesananTerbaru->fetch_assoc()):
                        ?>
                        <tr>
                            <td><b>#<?= $row['id_pesanan'] ?></b></td>
                            <td><?= htmlspecialchars($row['nama_lengkap']) ?></td>
                            <td class="fw-700"><?= rupiah($row['total_harga']) ?></td>
                            <td><span class="badge bg-<?= $sc[$row['status_bayar']]??'secondary' ?> rounded-pill"><?= $row['status_bayar'] ?></span></td>
                            <td class="small text-muted"><?= date('d/m H:i',strtotime($row['tanggal'])) ?></td>
                        </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
