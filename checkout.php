<?php
// ============================================================
//  pelanggan/checkout.php  – Checkout & Upload Bukti Bayar
// ============================================================
require_once '../auth_check.php';
authCheck(['pelanggan']);
require_once '../koneksi.php';

$keranjang = $_SESSION['keranjang'] ?? [];
if (empty($keranjang)) { header('Location: index.php'); exit; }

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $alamat = esc($conn, $_POST['alamat'] ?? '');
    $catatan = esc($conn, $_POST['catatan'] ?? '');

    if (empty($alamat)) {
        $error = 'Alamat pengiriman wajib diisi.';
    } elseif (empty($_FILES['bukti_bayar']['name'])) {
        $error = 'Upload bukti pembayaran terlebih dahulu.';
    } else {
        // Validasi file
        $file    = $_FILES['bukti_bayar'];
        $allowedTypes = ['image/jpeg','image/png','image/jpg','image/gif'];
        $maxSize = 2 * 1024 * 1024; // 2 MB

        if (!in_array($file['type'], $allowedTypes)) {
            $error = 'Format file harus JPG, PNG, atau GIF.';
        } elseif ($file['size'] > $maxSize) {
            $error = 'Ukuran file maksimal 2 MB.';
        } else {
            // Hitung total
            $total = 0;
            foreach ($keranjang as $k) { $total += $k['harga'] * $k['jumlah']; }

            // Simpan pesanan header
            $id_user = (int)$_SESSION['user_id'];
            $stmt = $conn->prepare("INSERT INTO pesanan (id_user, total_harga, status_bayar, alamat_pengiriman, catatan) VALUES (?,?,'Menunggu Pembayaran',?,?)");
            $stmt->bind_param('idss', $id_user, $total, $alamat, $catatan);
            $stmt->execute();
            $id_pesanan = $conn->insert_id;

            // Simpan detail & kurangi stok
            foreach ($keranjang as $id_produk => $k) {
                $jml   = (int)$k['jumlah'];
                $harga = (float)$k['harga'];
                $conn->query("INSERT INTO detail_pesanan (id_pesanan,id_produk,jumlah,harga_satuan) VALUES ($id_pesanan,$id_produk,$jml,$harga)");
                $conn->query("UPDATE produk SET stok = stok - $jml WHERE id = $id_produk AND stok >= $jml");
            }

            // Upload bukti
            $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'bukti_' . $id_pesanan . '_' . time() . '.' . $ext;
            $uploadDir = '../uploads/bukti_bayar/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                $conn->query("UPDATE pesanan SET bukti_bayar='$filename' WHERE id_pesanan=$id_pesanan");
                unset($_SESSION['keranjang']);
                $success = "Pesanan #$id_pesanan berhasil dibuat! Kami akan memverifikasi pembayaran Anda segera.";
            } else {
                $error = 'Gagal mengupload file. Coba lagi.';
            }
        }
    }
}

$grandTotal = array_sum(array_map(fn($k) => $k['harga'] * $k['jumlah'], $keranjang));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout – Fania Food</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background:#f8f9fa; font-family:'Nunito',sans-serif; }
        .card { border:none; border-radius:16px; box-shadow:0 4px 20px rgba(0,0,0,.07); }
        .btn-orange { background:#FF6B35; color:#fff; border:none; border-radius:8px; font-weight:700; }
        .btn-orange:hover { background:#E8531A; color:#fff; }
        .upload-area { border:2px dashed #FF6B35; border-radius:12px; padding:30px; text-align:center; cursor:pointer;
                       background:#fff8f0; transition:background .2s; }
        .upload-area:hover { background:#fff0e5; }
    </style>
</head>
<body>
<nav class="navbar navbar-light bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-800" style="color:#FF6B35">🧊 Fania Food</a>
        <a href="index.php" class="btn btn-sm btn-outline-secondary rounded-pill">← Katalog</a>
    </div>
</nav>

<div class="container my-4" style="max-width:800px">
    <h4 class="fw-800 mb-4">💳 Checkout</h4>

    <?php if ($success): ?>
    <div class="alert alert-success rounded-4 p-4 text-center">
        <h5 class="fw-800">🎉 <?= htmlspecialchars($success) ?></h5>
        <a href="index.php" class="btn btn-orange mt-2">Belanja Lagi</a>
        <a href="riwayat.php" class="btn btn-outline-secondary mt-2 ms-2">Riwayat Pesanan</a>
    </div>
    <?php else: ?>

    <?php if ($error): ?>
    <div class="alert alert-danger rounded-3"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Ringkasan Pesanan -->
        <div class="col-md-5">
            <div class="card p-4">
                <h6 class="fw-800 mb-3">Ringkasan Pesanan</h6>
                <?php foreach ($keranjang as $k): ?>
                <div class="d-flex justify-content-between small mb-2">
                    <span><?= htmlspecialchars($k['nama']) ?> ×<?= $k['jumlah'] ?></span>
                    <span class="fw-700"><?= rupiah($k['harga'] * $k['jumlah']) ?></span>
                </div>
                <?php endforeach; ?>
                <hr>
                <div class="d-flex justify-content-between fw-800">
                    <span>Total</span>
                    <span style="color:#FF6B35"><?= rupiah($grandTotal) ?></span>
                </div>

                <div class="mt-4 p-3 rounded-3" style="background:#f0f9f4;border:1px solid #b7e4c7">
                    <small class="fw-bold text-success d-block mb-1">📦 Transfer ke:</small>
                    <small>BCA: <b>1234567890</b><br>a.n. Fania Food Indonesia</small>
                </div>
            </div>
        </div>

        <!-- Form Checkout -->
        <div class="col-md-7">
            <div class="card p-4">
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label fw-700">Alamat Pengiriman <span class="text-danger">*</span></label>
                        <textarea name="alamat" class="form-control" rows="3"
                                  placeholder="Jl. Merdeka No. 10, Kel. Caturtunggal, Sleman..."
                                  required style="border-radius:10px;"><?= htmlspecialchars($_POST['alamat'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-700">Catatan (opsional)</label>
                        <input type="text" name="catatan" class="form-control"
                               placeholder="mis. Tolong bungkus rapi" style="border-radius:10px;"
                               value="<?= htmlspecialchars($_POST['catatan'] ?? '') ?>">
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-700">Upload Bukti Pembayaran <span class="text-danger">*</span></label>
                        <div class="upload-area" onclick="document.getElementById('buktiFile').click()">
                            <i class="bi bi-cloud-upload fs-2 text-warning"></i>
                            <p class="mb-0 mt-2 fw-600" id="uploadLabel">Klik untuk upload gambar bukti transfer</p>
                            <small class="text-muted">JPG / PNG / GIF, maks. 2 MB</small>
                        </div>
                        <input type="file" name="bukti_bayar" id="buktiFile" accept="image/*"
                               class="d-none" required onchange="updateLabel(this)">
                    </div>
                    <button type="submit" class="btn btn-orange w-100 py-2 fs-5">
                        <i class="bi bi-send-check me-2"></i>Konfirmasi Pesanan
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function updateLabel(input) {
    const label = document.getElementById('uploadLabel');
    if (input.files && input.files[0]) {
        label.textContent = '✅ ' + input.files[0].name;
    }
}
</script>
</body>
</html>
