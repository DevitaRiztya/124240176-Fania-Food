<?php
// ============================================================
//  admin/pesanan.php  – Verifikasi Pesanan & Pembayaran
// ============================================================
require_once '../auth_check.php';
authCheck(['admin']);
require_once '../koneksi.php';

// ── Update status pesanan ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pesanan  = (int)$_POST['id_pesanan'];
    $status_baru = esc($conn, $_POST['status_baru']);
    $allowed = ['Diproses','Dikirim','Selesai','Dibatalkan'];
    if ($id_pesanan > 0 && in_array($status_baru, $allowed)) {
        $conn->query("UPDATE pesanan SET status_bayar='$status_baru' WHERE id_pesanan=$id_pesanan");
        $_SESSION['flash'] = ['type'=>'success','msg'=>"Status pesanan #$id_pesanan diubah ke $status_baru."];
    }
    header('Location: pesanan.php');
    exit;
}

$filter = esc($conn, $_GET['status'] ?? '');
$where  = $filter ? "WHERE p.status_bayar = '$filter'" : '';
$pesananList = $conn->query(
    "SELECT p.*, u.nama_lengkap, u.username
     FROM pesanan p JOIN users u ON u.id = p.id_user
     $where ORDER BY p.tanggal DESC"
);

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$statusColor = [
    'Menunggu Pembayaran' => 'warning',
    'Diproses'            => 'primary',
    'Dikirim'             => 'info',
    'Selesai'             => 'success',
    'Dibatalkan'          => 'danger',
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan – Admin Fania Food</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { font-family:'Nunito',sans-serif; background:#f4f6f9; }
        .card { border:none; border-radius:16px; box-shadow:0 4px 16px rgba(0,0,0,.07); }
    </style>
</head>
<body>
<?php include '_navbar.php'; ?>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-800 mb-0">🧾 Manajemen Pesanan</h4>
        <div class="d-flex gap-2">
            <?php
            $statuses = ['', 'Menunggu Pembayaran', 'Diproses', 'Dikirim', 'Selesai', 'Dibatalkan'];
            $labels   = ['Semua', 'Menunggu', 'Diproses', 'Dikirim', 'Selesai', 'Batal'];
            foreach ($statuses as $i => $s): ?>
            <a href="?status=<?= urlencode($s) ?>"
               class="btn btn-sm <?= $filter===$s ? 'btn-dark' : 'btn-outline-secondary' ?> rounded-pill">
                <?= $labels[$i] ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show rounded-3">
        <?= $flash['msg'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-4">ID</th><th>Pelanggan</th><th>Total</th>
                        <th>Tanggal</th><th class="text-center">Status</th>
                        <th class="text-center">Bukti Bayar</th><th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($pesananList->num_rows === 0): ?>
                <tr><td colspan="7" class="text-center text-muted py-5">Tidak ada pesanan ditemukan</td></tr>
                <?php endif; ?>
                <?php while ($ps = $pesananList->fetch_assoc()): ?>
                <tr>
                    <td class="ps-4 fw-700">#<?= $ps['id_pesanan'] ?></td>
                    <td>
                        <b><?= htmlspecialchars($ps['nama_lengkap']) ?></b><br>
                        <small class="text-muted">@<?= htmlspecialchars($ps['username']) ?></small>
                    </td>
                    <td class="fw-700" style="color:#2D6A4F"><?= rupiah($ps['total_harga']) ?></td>
                    <td class="small text-muted"><?= date('d/m/Y H:i', strtotime($ps['tanggal'])) ?></td>
                    <td class="text-center">
                        <span class="badge bg-<?= $statusColor[$ps['status_bayar']] ?? 'secondary' ?> rounded-pill px-3">
                            <?= htmlspecialchars($ps['status_bayar']) ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <?php if ($ps['bukti_bayar']): ?>
                        <a href="../uploads/bukti_bayar/<?= htmlspecialchars($ps['bukti_bayar']) ?>"
                           target="_blank" class="btn btn-sm btn-outline-info rounded-3">
                            <i class="bi bi-image"></i> Lihat
                        </a>
                        <?php else: ?>
                        <span class="text-muted small">Belum upload</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <!-- Detail Modal Trigger -->
                        <button class="btn btn-sm btn-outline-secondary rounded-3 me-1"
                                data-bs-toggle="modal"
                                data-bs-target="#detailModal"
                                data-id="<?= $ps['id_pesanan'] ?>"
                                data-alamat="<?= htmlspecialchars($ps['alamat_pengiriman']) ?>"
                                data-catatan="<?= htmlspecialchars($ps['catatan']) ?>">
                            <i class="bi bi-info-circle"></i>
                        </button>
                        <!-- Form ubah status -->
                        <?php if (!in_array($ps['status_bayar'], ['Selesai','Dibatalkan'])): ?>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="id_pesanan" value="<?= $ps['id_pesanan'] ?>">
                            <select name="status_baru" onchange="this.form.submit()" class="form-select form-select-sm d-inline-block w-auto rounded-3" style="font-size:.8rem;">
                                <option value="">Ubah Status</option>
                                <option value="Diproses">✅ Diproses</option>
                                <option value="Dikirim">🚚 Dikirim</option>
                                <option value="Selesai">🎉 Selesai</option>
                                <option value="Dibatalkan">❌ Batalkan</option>
                            </select>
                        </form>
                        <?php else: ?>
                        <span class="text-muted small">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Detail Pesanan -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-0">
                <h6 class="modal-title fw-800">📋 Detail Pesanan #<span id="dId"></span></h6>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted fw-bold mb-1">Alamat Pengiriman:</p>
                <p id="dAlamat" class="mb-3"></p>
                <p class="small text-muted fw-bold mb-1">Catatan:</p>
                <p id="dCatatan" class="mb-0 text-muted"></p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('detailModal').addEventListener('show.bs.modal', function(e) {
    const b = e.relatedTarget;
    document.getElementById('dId').textContent      = b.dataset.id;
    document.getElementById('dAlamat').textContent  = b.dataset.alamat || '—';
    document.getElementById('dCatatan').textContent = b.dataset.catatan || '—';
});
</script>
</body>
</html>
