<?php
// ============================================================
//  admin/stok.php  – Kelola Stok Masuk
// ============================================================
require_once '../auth_check.php';
authCheck(['admin']);
require_once '../koneksi.php';

$msg = '';
$type = 'success';

// ── Input stok masuk ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'tambah_stok') {
        $id_produk   = (int)$_POST['id_produk'];
        $jumlah      = (int)$_POST['jumlah'];
        $keterangan  = esc($conn, $_POST['keterangan'] ?? 'Stok masuk');
        $id_admin    = (int)$_SESSION['user_id'];

        if ($id_produk <= 0 || $jumlah <= 0) {
            $msg = 'Isi semua field dengan benar.'; $type = 'danger';
        } else {
            $conn->query("UPDATE produk SET stok = stok + $jumlah WHERE id = $id_produk");
            $conn->query("INSERT INTO stok_log (id_produk,jumlah,keterangan,id_admin) VALUES ($id_produk,$jumlah,'$keterangan',$id_admin)");
            $msg = 'Stok berhasil ditambahkan!';
        }
    }

    if ($aksi === 'edit_produk') {
        $id    = (int)$_POST['id'];
        $nama  = esc($conn, $_POST['nama_produk']);
        $harga = (float)$_POST['harga'];
        $desk  = esc($conn, $_POST['deskripsi']);
        $conn->query("UPDATE produk SET nama_produk='$nama', harga=$harga, deskripsi='$desk' WHERE id=$id");
        $msg = 'Data produk diperbarui.';
    }
}

// ── Ambil data ────────────────────────────────────────────
$produkList = $conn->query("SELECT * FROM produk ORDER BY nama_produk ASC");
$logList    = $conn->query(
    "SELECT sl.*, p.nama_produk, u.username AS admin_name
     FROM stok_log sl
     JOIN produk p ON p.id = sl.id_produk
     JOIN users u  ON u.id = sl.id_admin
     ORDER BY sl.tanggal DESC LIMIT 50"
);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Stok – Admin Fania Food</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { font-family:'Nunito',sans-serif; background:#f4f6f9; }
        .card { border:none; border-radius:16px; box-shadow:0 4px 16px rgba(0,0,0,.07); }
        .badge-stok-ok   { background:#d1fae5; color:#065f46; border-radius:20px; padding:4px 12px; font-size:.8rem; }
        .badge-stok-warn { background:#fef3c7; color:#92400e; border-radius:20px; padding:4px 12px; font-size:.8rem; }
        .badge-stok-low  { background:#fee2e2; color:#991b1b; border-radius:20px; padding:4px 12px; font-size:.8rem; }
    </style>
</head>
<body>
<?php include '_navbar.php'; ?>

<div class="container-fluid p-4">
    <h4 class="fw-800 mb-4">📦 Kelola Stok Produk</h4>

    <?php if ($msg): ?>
    <div class="alert alert-<?= $type ?> alert-dismissible fade show rounded-3">
        <?= htmlspecialchars($msg) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Form Input Stok Masuk -->
        <div class="col-md-4">
            <div class="card p-4">
                <h6 class="fw-800 mb-3"><i class="bi bi-arrow-down-circle text-success me-2"></i>Input Stok Masuk</h6>
                <form method="POST">
                    <input type="hidden" name="aksi" value="tambah_stok">
                    <div class="mb-3">
                        <label class="form-label fw-700 small">Produk</label>
                        <select name="id_produk" class="form-select" required style="border-radius:8px;">
                            <option value="">-- Pilih Produk --</option>
                            <?php
                            $produkList->data_seek(0);
                            while ($p = $produkList->fetch_assoc()):
                            ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nama_produk']) ?> (Stok: <?= $p['stok'] ?>)</option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-700 small">Jumlah Masuk</label>
                        <input type="number" name="jumlah" class="form-control" min="1" placeholder="Masukkan jumlah" required style="border-radius:8px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-700 small">Keterangan</label>
                        <input type="text" name="keterangan" class="form-control" placeholder="Dari supplier / produksi" style="border-radius:8px;">
                    </div>
                    <button type="submit" class="btn w-100 fw-700" style="background:#2D6A4F;color:#fff;border-radius:8px;">
                        <i class="bi bi-plus-circle me-1"></i> Tambah Stok
                    </button>
                </form>
            </div>
        </div>

        <!-- Tabel Produk & Stok -->
        <div class="col-md-8">
            <div class="card p-4">
                <h6 class="fw-800 mb-3"><i class="bi bi-table me-2"></i>Status Stok Semua Produk</h6>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr><th>#</th><th>Nama Produk</th><th class="text-center">Stok</th>
                                <th class="text-end">Harga</th><th class="text-center">Aksi</th></tr>
                        </thead>
                        <tbody>
                        <?php $no=1; $produkList->data_seek(0); while ($p = $produkList->fetch_assoc()): ?>
                        <tr>
                            <td class="text-muted small"><?= $no++ ?></td>
                            <td class="fw-700"><?= htmlspecialchars($p['nama_produk']) ?></td>
                            <td class="text-center">
                                <?php
                                $cls = $p['stok'] >= 20 ? 'badge-stok-ok' : ($p['stok'] >= 5 ? 'badge-stok-warn' : 'badge-stok-low');
                                echo "<span class='$cls'>{$p['stok']} pcs</span>";
                                ?>
                            </td>
                            <td class="text-end"><?= rupiah($p['harga']) ?></td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-primary rounded-3"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editModal"
                                        data-id="<?= $p['id'] ?>"
                                        data-nama="<?= htmlspecialchars($p['nama_produk']) ?>"
                                        data-harga="<?= $p['harga'] ?>"
                                        data-desk="<?= htmlspecialchars($p['deskripsi']) ?>">
                                    <i class="bi bi-pencil"></i> Edit
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Log Stok Masuk -->
        <div class="col-12">
            <div class="card p-4">
                <h6 class="fw-800 mb-3"><i class="bi bi-clock-history me-2"></i>Riwayat Stok Masuk (50 Terakhir)</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle">
                        <thead class="table-light">
                            <tr><th>Tanggal</th><th>Produk</th><th class="text-center">Jumlah</th>
                                <th>Keterangan</th><th>Input oleh</th></tr>
                        </thead>
                        <tbody>
                        <?php while ($log = $logList->fetch_assoc()): ?>
                        <tr>
                            <td class="small text-muted"><?= date('d/m/Y H:i', strtotime($log['tanggal'])) ?></td>
                            <td class="fw-600"><?= htmlspecialchars($log['nama_produk']) ?></td>
                            <td class="text-center"><span class="badge bg-success">+<?= $log['jumlah'] ?></span></td>
                            <td class="small"><?= htmlspecialchars($log['keterangan']) ?></td>
                            <td class="small text-muted"><?= htmlspecialchars($log['admin_name']) ?></td>
                        </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit Produk -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-800">✏️ Edit Produk</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="aksi" value="edit_produk">
                    <input type="hidden" name="id" id="editId">
                    <div class="mb-3">
                        <label class="form-label fw-700 small">Nama Produk</label>
                        <input type="text" name="nama_produk" id="editNama" class="form-control" required style="border-radius:8px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-700 small">Harga (Rp)</label>
                        <input type="number" name="harga" id="editHarga" class="form-control" required style="border-radius:8px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-700 small">Deskripsi</label>
                        <textarea name="deskripsi" id="editDesk" class="form-control" rows="3" style="border-radius:8px;"></textarea>
                    </div>
                    <button type="submit" class="btn w-100 fw-700" style="background:#2D6A4F;color:#fff;border-radius:8px;">Simpan Perubahan</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('editModal').addEventListener('show.bs.modal', function(e) {
    const btn = e.relatedTarget;
    document.getElementById('editId').value    = btn.dataset.id;
    document.getElementById('editNama').value  = btn.dataset.nama;
    document.getElementById('editHarga').value = btn.dataset.harga;
    document.getElementById('editDesk').value  = btn.dataset.desk;
});
</script>
</body>
</html>
