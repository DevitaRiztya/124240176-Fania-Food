<?php
// ============================================================
//  pelanggan/index.php  – Katalog Produk + Keranjang (FIXED)
// ============================================================
require_once '../auth_check.php';
authCheck(['pelanggan']);
require_once '../koneksi.php';

// Fungsi escape pengganti esc() yang error agar aman dari SQL Injection
function aman($koneksi, $data) {
    return mysqli_real_escape_string($koneksi, trim($data));
}

// ── Aksi Keranjang (POST) ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'tambah_keranjang') {
        $id_produk = (int)$_POST['id_produk'];
        $jumlah    = max(1, (int)$_POST['jumlah']);

        // Cek stok
        $r = $conn->query("SELECT stok, nama_produk, harga FROM produk WHERE id=$id_produk LIMIT 1");
        $p = $r->fetch_assoc();
        if ($p && $p['stok'] >= $jumlah) {
            $_SESSION['keranjang'][$id_produk] = [
                'nama'   => $p['nama_produk'],
                'harga'  => $p['harga'],
                'jumlah' => ($jumlah + ($_SESSION['keranjang'][$id_produk]['jumlah'] ?? 0)),
            ];
            $_SESSION['flash'] = ['type'=>'success','msg'=>"✅ {$p['nama_produk']} ditambahkan ke keranjang!"];
        } else {
            $_SESSION['flash'] = ['type'=>'danger','msg'=>'❌ Stok tidak mencukupi.'];
        }
    }

    if ($aksi === 'hapus_keranjang') {
        unset($_SESSION['keranjang'][(int)$_POST['id_produk']]);
    }

    if ($aksi === 'checkout') {
        $keranjang = $_SESSION['keranjang'] ?? [];
        if (empty($keranjang)) {
            $_SESSION['flash'] = ['type'=>'warning','msg'=>'Keranjang masih kosong.'];
        } else {
            header('Location: checkout.php');
            exit;
        }
    }

    header('Location: index.php');
    exit;
}

// ── Ambil Produk Berdasarkan Pencarian ──────────────────────
$keyword  = isset($_GET['q']) ? aman($conn, $_GET['q']) : '';
$sqlProduk = "SELECT * FROM produk WHERE stok > 0";
if ($keyword !== '') {
    $sqlProduk .= " AND nama_produk LIKE '%$keyword%'";
}
$sqlProduk .= " ORDER BY id ASC";
$produkResult = $conn->query($sqlProduk);

$keranjang  = $_SESSION['keranjang'] ?? [];
$totalKeranjang = array_sum(array_column($keranjang, 'jumlah'));
$flash      = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog – Fania Food</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root { --primary:#FF6B35; --primary-dark:#E8531A; }
        body  { background:#f8f9fa; font-family:'Nunito',sans-serif; }
        .navbar-brand { font-weight:800; font-size:1.4rem; color:var(--primary) !important; }
        
        /* CSS Card Anti-Kedut Presisi */
        .card-produk   { border:none; border-radius:16px; box-shadow:0 4px 20px rgba(0,0,0,.07);
                         transition:transform .2s,box-shadow .2s; overflow:hidden; display: flex; flex-direction: column; }
        .card-produk:hover { transform:translateY(-4px); box-shadow:0 8px 28px rgba(0,0,0,.13); }
        
        .img-container     { height:180px; width:100%; overflow:hidden; background:#f1f3f5; position:relative; }
        .card-produk img   { height:100%; width:100%; object-fit:cover; }
        
        .btn-orange        { background:var(--primary); color:#fff; border:none; border-radius:8px; font-weight:700; }
        .btn-orange:hover  { background:var(--primary-dark); color:#fff; }
        .badge-stok        { position:absolute; top:10px; right:10px; background:rgba(0,0,0,.6);
                             color:#fff; font-size:.7rem; border-radius:20px; padding:3px 10px; z-index: 10; }
        .harga             { color:var(--primary); font-size:1.1rem; font-weight:800; }
        .cart-floating     { position:fixed; bottom:28px; right:28px; z-index:999; }
        .cart-floating .btn{ border-radius:50px; padding:14px 22px; font-size:1rem; font-weight:700;
                             box-shadow:0 8px 24px rgba(255,107,53,.5); }
    </style>
</head>
<body>
<nav class="navbar navbar-light bg-white shadow-sm sticky-top">
    <div class="container">
        <span class="navbar-brand">🧊 Fania Food</span>
        <div class="d-flex align-items-center gap-3">
            <form class="d-flex" method="GET">
                <input class="form-control me-2" type="search" name="q"
                       placeholder="Cari produk..." value="<?= htmlspecialchars($keyword) ?>" style="border-radius:8px;">
                <button class="btn btn-orange" type="submit"><i class="bi bi-search"></i></button>
            </form>
            <span class="text-muted small">Halo, <b><?= htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Pelanggan') ?></b></span>
            <a href="../logout.php" class="btn btn-sm btn-outline-secondary rounded-pill">Logout</a>
        </div>
    </div>
</nav>

<div class="container my-4">
    <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show rounded-3" role="alert">
        <?= $flash['msg'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <h4 class="fw-800 mb-4">🛒 Katalog Frozen Food</h4>
    <div class="row g-4">
    <?php if ($produkResult && $produkResult->num_rows > 0): ?>
        <?php while ($p = $produkResult->fetch_assoc()): ?>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="card card-produk h-100">
                    <div class="img-container">
                        <img src="images/<?= htmlspecialchars($p['foto']) ?>" 
                             onerror="this.src='images/sosis-sapi.jpg';" 
                             alt="<?= htmlspecialchars($p['nama_produk']) ?>">
                        <span class="badge-stok">Stok: <?= $p['stok'] ?></span>
                    </div>
                    <div class="card-body d-flex flex-column p-3 flex-grow-1">
                        <h6 class="fw-700 mb-1" style="font-size:.9rem; height: 44px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                            <?= htmlspecialchars($p['nama_produk']) ?>
                        </h6>
                        <p class="text-muted small mb-2 flex-grow-1" style="font-size:.78rem; height: 36px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                            <?= htmlspecialchars($p['deskripsi']) ?>
                        </p>
                        <div class="harga mb-3"><?= rupiah($p['harga']) ?></div>
                        <form method="POST">
                            <input type="hidden" name="aksi"      value="tambah_keranjang">
                            <input type="hidden" name="id_produk" value="<?= $p['id'] ?>">
                            <div class="d-flex gap-2 align-items-center">
                                <input type="number" name="jumlah" value="1" min="1" max="<?= $p['stok'] ?>"
                                       class="form-control form-control-sm" style="width:60px;border-radius:8px;">
                                <button type="submit" class="btn btn-orange btn-sm flex-grow-1">
                                    <i class="bi bi-cart-plus-fill"></i> Tambah
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="col-12 text-center py-5">
            <i class="bi bi-box-seam text-muted display-4"></i>
            <p class="text-muted mt-2">Produk tidak ditemukan atau stok sedang habis.</p>
        </div>
    <?php endif; ?>
    </div>

    <?php if (!empty($keranjang)): ?>
    <div class="mt-5">
        <h5 class="fw-800">🛒 Keranjang Belanja (<?= $totalKeranjang ?> item)</h5>
        <div class="card border-0 shadow-sm rounded-4 p-3">
            <table class="table table-borderless align-middle mb-0">
                <thead class="table-light"><tr>
                    <th>Produk</th><th class="text-center">Qty</th>
                    <th class="text-end">Subtotal</th><th></th>
                </tr></thead>
                <tbody>
                <?php $grandTotal = 0; foreach ($keranjang as $kid => $kitem):
                    $sub = $kitem['harga'] * $kitem['jumlah']; $grandTotal += $sub; ?>
                <tr>
                    <td><b><?= htmlspecialchars($kitem['nama']) ?></b><br>
                        <small class="text-muted"><?= rupiah($kitem['harga']) ?>/pcs</small></td>
                    <td class="text-center"><?= $kitem['jumlah'] ?></td>
                    <td class="text-end fw-bold"><?= rupiah($sub) ?></td>
                    <td>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="aksi"      value="hapus_keranjang">
                            <input type="hidden" name="id_produk" value="<?= $kid ?>">
                            <button class="btn btn-sm btn-outline-danger rounded-3"><i class="bi bi-trash3"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot><tr>
                    <td colspan="2" class="fw-800">Total</td>
                    <td class="text-end fw-800 fs-5" style="color:var(--primary)"><?= rupiah($grandTotal) ?></td>
                    <td></td>
                </tr></tfoot>
            </table>
            <div class="text-end mt-3">
                <form method="POST" class="d-inline">
                    <input type="hidden" name="aksi" value="checkout">
                    <button type="submit" class="btn btn-orange px-4 py-2">
                        <i class="bi bi-credit-card me-1"></i> Lanjut ke Checkout
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="cart-floating">
    <a href="#" onclick="window.scrollTo({top:99999,behavior:'smooth'})" class="btn btn-orange">
        🛒 <span class="badge bg-white text-danger ms-1"><?= $totalKeranjang ?></span>
    </a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>