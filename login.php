<?php
// ============================================================
//  login.php  – Halaman Login Tunggal
//  Letakkan di: htdocs/fania-food/login.php
// ============================================================
session_start();

// Jika sudah login, redirect ke folder sesuai role
if (isset($_SESSION['user_id'])) {
    header('Location: ' . roleRedirect($_SESSION['role']));
    exit;
}

function roleRedirect($role) {
    $map = [
        'pelanggan'   => 'pelanggan/index.php',
        'admin'       => 'admin/index.php',
        'kepala_toko' => 'kepala_toko/index.php',
    ];
    return $map[$role] ?? 'login.php';
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'koneksi.php';

    $username = esc($conn, $_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        $stmt = $conn->prepare("SELECT id, username, password, nama_lengkap, role FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                // ✅ Login berhasil
                session_regenerate_id(true);
                $_SESSION['user_id']     = $user['id'];
                $_SESSION['username']    = $user['username'];
                $_SESSION['nama']        = $user['nama_lengkap'];
                $_SESSION['role']        = $user['role'];

                header('Location: ' . roleRedirect($user['role']));
                exit;
            }
        }
        $error = 'Username atau password salah.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – Fania Food</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #FF6B35;
            --primary-dark: #E8531A;
            --secondary: #2D6A4F;
            --accent: #FFD166;
            --bg: #FFF8F0;
        }
        body {
            background: var(--bg);
            font-family: 'Nunito', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-wrapper {
            display: flex;
            width: 900px;
            max-width: 100%;
            min-height: 520px;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,.15);
        }
        /* Panel kiri – branding */
        .brand-panel {
            background: linear-gradient(145deg, var(--primary), var(--primary-dark));
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 30px;
            color: #fff;
            position: relative;
            overflow: hidden;
        }
        .brand-panel::before {
            content: '';
            position: absolute;
            width: 300px; height: 300px;
            background: rgba(255,255,255,.06);
            border-radius: 50%;
            top: -80px; left: -80px;
        }
        .brand-panel::after {
            content: '';
            position: absolute;
            width: 200px; height: 200px;
            background: rgba(255,255,255,.06);
            border-radius: 50%;
            bottom: -50px; right: -50px;
        }
        .brand-panel h1 { font-size: 2.4rem; font-weight: 800; letter-spacing: -1px; }
        .brand-panel p  { opacity: .85; font-size: .95rem; }
        .brand-icon { font-size: 5rem; margin-bottom: 16px; }
        /* Panel kanan – form */
        .form-panel {
            background: #fff;
            flex: 0 0 400px;
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .form-panel h2 { font-weight: 800; font-size: 1.6rem; color: #1a1a1a; }
        .form-panel p.sub { color: #888; font-size: .9rem; }
        .form-control {
            border-radius: 10px;
            padding: 12px 16px;
            border: 1.5px solid #e0e0e0;
            font-size: .95rem;
            font-family: 'Nunito', sans-serif;
        }
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(255,107,53,.12);
        }
        .input-group-text {
            background: #fff4ef;
            border: 1.5px solid #e0e0e0;
            border-right: none;
            border-radius: 10px 0 0 10px !important;
            color: var(--primary);
        }
        .btn-login {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 700;
            font-size: 1rem;
            letter-spacing: .3px;
            transition: transform .15s, box-shadow .15s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255,107,53,.35);
            color: #fff;
        }
        .badge-role { font-size: .75rem; border-radius: 20px; padding: 5px 12px; }
        @media (max-width: 700px) {
            .brand-panel { display: none; }
            .form-panel  { flex: 1; padding: 40px 24px; }
        }
    </style>
</head>
<body>
<div class="login-wrapper">
    <!-- Branding -->
    <div class="brand-panel">
        <div class="brand-icon">🧊</div>
        <h1>Fania Food</h1>
        <p class="text-center mt-2">Sistem Informasi Penjualan<br>Frozen Food Berkualitas</p>
        <hr style="border-color:rgba(255,255,255,.3);width:60%;margin:24px auto;">
        <small class="text-center" style="opacity:.7;font-size:.8rem;">
            Akses tersedia untuk<br>
            <span class="badge bg-white text-danger me-1 badge-role mt-1">Pelanggan</span>
            <span class="badge bg-white text-success me-1 badge-role mt-1">Admin</span>
            <span class="badge bg-white text-warning badge-role mt-1" style="color:#e67e00 !important;">Kepala Toko</span>
        </small>
    </div>

    <!-- Form Login -->
    <div class="form-panel">
        <h2>Selamat Datang 👋</h2>
        <p class="sub mb-4">Masuk ke akun Anda untuk melanjutkan</p>

        <?php if ($error): ?>
        <div class="alert alert-danger d-flex align-items-center gap-2 py-2 rounded-3" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <small><?= htmlspecialchars($error) ?></small>
        </div>
        <?php endif; ?>

        <form method="POST" action="login.php" novalidate>
            <div class="mb-3">
                <label class="form-label fw-600 small">Username</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                    <input type="text" name="username" class="form-control"
                           placeholder="Masukkan username"
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                           required autofocus>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label fw-600 small">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                    <input type="password" name="password" id="pwdField"
                           class="form-control" placeholder="Masukkan password" required>
                    <button class="btn btn-outline-secondary border-start-0" type="button"
                            onclick="togglePwd()" style="border-radius:0 10px 10px 0;border:1.5px solid #e0e0e0;border-left:none;">
                        <i class="bi bi-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-login w-100">
                <i class="bi bi-box-arrow-in-right me-2"></i>Masuk
            </button>
        </form>

        <div class="mt-4 p-3 rounded-3" style="background:#fff8f0;border:1px dashed #ffc9a8;">
            <small class="text-muted d-block fw-bold mb-1">🔑 Akun Demo:</small>
            <small class="text-muted">admin / password &nbsp;|&nbsp; kepalatoko / password &nbsp;|&nbsp; budi / password</small>
        </div>
    </div>
</div>

<script>
function togglePwd() {
    const f = document.getElementById('pwdField');
    const i = document.getElementById('eyeIcon');
    if (f.type === 'password') { f.type = 'text';     i.className = 'bi bi-eye-slash'; }
    else                       { f.type = 'password'; i.className = 'bi bi-eye'; }
}
</script>
</body>
</html>
