<?php
session_start();
require_once '../includes/config.php';

if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit; }
if ($_SESSION['role'] == 'admin') { header("Location: ../admin/dashboard.php"); exit; }

$user_id = $_SESSION['user_id'];

// Ambil data user terkini dari DB
$q    = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id");
$user = mysqli_fetch_assoc($q);

$error   = '';
$success = '';

// ============================================================
// PROSES UPDATE PROFIL
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ---- Update Info Dasar ----
    if (isset($_POST['update_profil'])) {
        $nama  = mysqli_real_escape_string($conn, trim($_POST['nama_lengkap']));
        $email = mysqli_real_escape_string($conn, trim($_POST['email']));

        if (empty($nama) || empty($email)) {
            $error = "Nama dan email tidak boleh kosong.";
        } else {
            // Cek email duplikat (kecuali milik sendiri)
            $cek = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email' AND id != $user_id");
            if (mysqli_num_rows($cek) > 0) {
                $error = "Email sudah digunakan akun lain.";
            } else {
                if (mysqli_query($conn, "UPDATE users SET nama_lengkap = '$nama', email = '$email' WHERE id = $user_id")) {
                    $_SESSION['nama_lengkap'] = $nama;
                    $_SESSION['email']        = $email;
                    $user['nama_lengkap']     = $nama;
                    $user['email']            = $email;
                    $success = "Profil berhasil diperbarui!";
                } else {
                    $error = "Gagal memperbarui profil: " . mysqli_error($conn);
                }
            }
        }
    }

    // ---- Update Password ----
    if (isset($_POST['update_password'])) {
        $password_lama = $_POST['password_lama'];
        $password_baru = $_POST['password_baru'];
        $password_konfirmasi = $_POST['password_konfirmasi'];

        if (empty($password_lama) || empty($password_baru) || empty($password_konfirmasi)) {
            $error = "Semua kolom password harus diisi.";
        } elseif (!password_verify($password_lama, $user['password'])) {
            $error = "Password lama tidak sesuai.";
        } elseif (strlen($password_baru) < 6) {
            $error = "Password baru minimal 6 karakter.";
        } elseif ($password_baru !== $password_konfirmasi) {
            $error = "Konfirmasi password baru tidak cocok.";
        } else {
            $hash = password_hash($password_baru, PASSWORD_DEFAULT);
            if (mysqli_query($conn, "UPDATE users SET password = '$hash' WHERE id = $user_id")) {
                $success = "Password berhasil diubah!";
            } else {
                $error = "Gagal mengubah password.";
            }
        }
    }
}

// Statistik user
$total_pesanan = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as jml FROM pesanan WHERE user_id = $user_id"
))['jml'];
$total_favorit = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as jml FROM favorit WHERE user_id = $user_id"
))['jml'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - Braille Book Catalog</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-main: #1cceac;
            --primary-dark: #15a38a;
            --accent-gold:  #FFD700;
            --accent-yellow:#FFC107;
            --light-bg:     #f8f9fa;
            --dark-text:    #2c3e50;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',sans-serif; font-size:16px; background:var(--light-bg); color:var(--dark-text); }

        /* NAVBAR */
        .navbar { background:linear-gradient(135deg,var(--primary-main),var(--primary-dark))!important; box-shadow:0 4px 15px rgba(28,206,172,.2); padding:15px 0; position: sticky; top: 0; z-index: 999; }
        .navbar-brand { font-size:1.4rem; font-weight:bold; color:white!important; }
        .navbar-brand i { color:var(--accent-gold); margin-right:8px; }
        .navbar-nav .nav-link { color:rgba(255,255,255,.9)!important; font-weight:500; padding:8px 16px!important; transition:all .3s; }
        .navbar-nav .nav-link:hover, .navbar-nav .nav-link.active { color:var(--accent-gold)!important; background:rgba(255,255,255,.15); border-radius:8px; }
        .user-badge { background:rgba(255,255,255,.2); padding:7px 15px; border-radius:50px; color:white; font-size:.95rem; }
        .user-badge i { color:var(--accent-gold); margin-right:6px; }

        /* WELCOME SECTION */
        .welcome-section {
            background:linear-gradient(135deg,var(--primary-main),var(--primary-dark));
            padding:35px 0; border-radius:0 0 30px 30px; margin-bottom:35px;
        }

        /* AVATAR BESAR */
        .avatar-circle {
            width:90px; height:90px; border-radius:50%;
            background:rgba(255,255,255,.25);
            border:4px solid rgba(255,255,255,.5);
            display:flex; align-items:center; justify-content:center;
            font-size:38px; font-weight:800; color:white;
            flex-shrink:0;
        }
        .welcome-name  { color:white; font-size:1.6rem; font-weight:bold; margin-bottom:4px; }
        .welcome-email { color:rgba(255,255,255,.8); font-size:.95rem; }
        .welcome-since { color:rgba(255,255,255,.7); font-size:.82rem; margin-top:4px; }

        /* STAT MINI */
        .stat-mini { background:rgba(255,255,255,.15); border:1px solid rgba(255,255,255,.25); border-radius:14px; padding:14px 20px; text-align:center; color:white; }
        .stat-mini .num { font-size:1.6rem; font-weight:800; }
        .stat-mini .lbl { font-size:.78rem; opacity:.85; margin-top:2px; }

        /* CARD */
        .card-custom { background:white; border-radius:20px; padding:28px; box-shadow:0 8px 25px rgba(0,0,0,.07); border:1px solid #eee; margin-bottom:24px; }
        .card-section-title { font-size:1rem; font-weight:700; color:var(--primary-dark); margin-bottom:20px; padding-bottom:10px; border-bottom:2px solid #f0fdf9; display:flex; align-items:center; gap:8px; }
        .card-section-title i { color:var(--primary-main); }

        /* FORM */
        .form-label { font-weight:600; font-size:.9rem; margin-bottom:5px; }
        .form-control { border:1.5px solid #e0e0e0; border-radius:10px; padding:10px 14px; font-size:.95rem; transition:all .3s; }
        .form-control:focus { border-color:var(--primary-main); box-shadow:0 0 0 3px rgba(28,206,172,.15); }
        .form-control[readonly] { background:#f8f9fa; color:#888; cursor:not-allowed; }
        .form-text { font-size:.8rem; color:#888; margin-top:4px; }

        /* PASSWORD STRENGTH */
        .strength-bar { height:5px; border-radius:10px; background:#eee; margin-top:6px; overflow:hidden; }
        .strength-fill { height:100%; border-radius:10px; width:0%; transition:all .4s; }

        /* BUTTONS */
        .btn-simpan { background:linear-gradient(135deg,var(--primary-main),var(--primary-dark)); color:white; border:none; padding:12px 28px; border-radius:12px; font-weight:700; font-size:.95rem; transition:all .3s; box-shadow:0 5px 15px rgba(28,206,172,.3); display:inline-flex; align-items:center; gap:8px; cursor:pointer; }
        .btn-simpan:hover { transform:translateY(-2px); box-shadow:0 8px 22px rgba(28,206,172,.4); color:white; }

        /* ALERT */
        .alert-sukses { background:#e8f5e9; border:none; border-left:4px solid #4caf50; border-radius:12px; padding:14px 18px; color:#2e7d32; display:flex; align-items:center; gap:10px; margin-bottom:20px; }
        .alert-error  { background:#ffebee; border:none; border-left:4px solid #f44336; border-radius:12px; padding:14px 18px; color:#c62828; display:flex; align-items:center; gap:10px; margin-bottom:20px; }

        /* INFO BOX */
        .info-box { background:#f0fdf9; border:1.5px solid #c8f0e8; border-radius:12px; padding:16px 18px; }
        .info-row  { display:flex; padding:8px 0; border-bottom:1px solid #e8f5f2; font-size:.9rem; }
        .info-row:last-child { border-bottom:none; }
        .info-row .lbl { width:40%; font-weight:600; color:#555; display:flex; align-items:center; gap:8px; }
        .info-row .lbl i { color:var(--primary-main); width:14px; }
        .info-row .val { flex:1; color:var(--dark-text); }

        /* SHORTCUT LINKS */
        .shortcut-link { display:flex; align-items:center; gap:14px; padding:14px 16px; border-radius:12px; border:1.5px solid #e8f5f2; background:#f8fffe; text-decoration:none; color:var(--dark-text); transition:all .3s; margin-bottom:10px; }
        .shortcut-link:hover { border-color:var(--primary-main); background:#f0fdf9; transform:translateX(4px); }
        .shortcut-icon { width:42px; height:42px; border-radius:10px; background:linear-gradient(135deg,var(--primary-main),var(--primary-dark)); display:flex; align-items:center; justify-content:center; color:white; font-size:18px; flex-shrink:0; }
        .shortcut-link .shortcut-text strong { display:block; font-size:.92rem; }
        .shortcut-link .shortcut-text small  { color:#888; font-size:.78rem; }
        .shortcut-link i.fa-chevron-right { color:#ccc; margin-left:auto; }

        /* FOOTER */
        .footer {
    background: linear-gradient(135deg, var(--primary-main) 0%, var(--primary-dark) 100%);
    color: white;
    padding: 40px 0;
    margin-top: 60px;
}

.footer h5 {
    color: var(--accent-gold);
    margin-bottom: 15px;
}

.footer a {
    color: rgba(255,255,255,0.9);
    text-decoration: none;
    transition: color 0.3s;
}

.footer a:hover {
    color: var(--accent-gold);
}

.footer .social-links a {
    display: inline-block;
    width: 40px;
    height: 40px;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
    text-align: center;
    line-height: 40px;
    margin-right: 10px;
    transition: all 0.3s;
}

.footer .social-links a:hover {
    background: var(--accent-gold);
    color: var(--primary-dark);
    transform: translateY(-3px);
}

        @media(max-width:768px) {
            .welcome-section .d-flex { flex-direction:column; text-align:center; }
            .avatar-circle { margin:0 auto; }
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="../index.php">
            <i class="fas fa-book-open"></i> BRAILLE BOOK CATALOG
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <!-- Menu Tengah -->
            <ul class="navbar-nav mx-auto">
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' && strpos($_SERVER['PHP_SELF'], 'user') === false ? 'active' : '' ?>" href="../index.php">
                        <i class="fas fa-home"></i> KATALOG
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
                        <i class="fas fa-th-large"></i> DASHBOARD
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'pesanan.php' ? 'active' : '' ?>" href="pesanan.php">
                        <i class="fas fa-shopping-bag"></i> PESANAN
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'profil.php' ? 'active' : '' ?>" href="profil.php">
                        <i class="fas fa-user-circle"></i> PROFIL
                    </a>
                </li>
            </ul>
            <!-- User Badge + Logout (Kanan) -->
            <div class="user-badge">
                <i class="fas fa-user-circle"></i>
                <?= htmlspecialchars($_SESSION['nama_lengkap']) ?>
                <a href="../logout.php" class="btn btn-sm btn-outline-light ms-3">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </div>
</nav>

<!-- WELCOME SECTION -->
<section class="welcome-section">
    <div class="container">
        <div class="row align-items-center g-3">
            <div class="col-md-7">
                <div class="d-flex align-items-center gap-4">
                    <div class="avatar-circle">
                        <?= strtoupper(substr($user['nama_lengkap'], 0, 1)) ?>
                    </div>
                    <div>
                        <div class="welcome-name"><?= htmlspecialchars($user['nama_lengkap']) ?></div>
                        <div class="welcome-email">
                            <i class="fas fa-envelope me-1"></i><?= htmlspecialchars($user['email']) ?>
                        </div>
                        <div class="welcome-since">
                            <i class="fas fa-calendar me-1"></i>
                            Bergabung sejak <?= date('d M Y', strtotime($user['created_at'])) ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-5">
                <div class="row g-2">
                    <div class="col-6">
                        <div class="stat-mini">
                            <div class="num"><?= $total_pesanan ?></div>
                            <div class="lbl">Total Pesanan</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-mini">
                            <div class="num"><?= $total_favorit ?></div>
                            <div class="lbl">Buku Favorit</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- MAIN -->
<div class="container pb-5">

    <!-- Alert -->
    <?php if ($success): ?>
    <div class="alert-sukses">
        <i class="fas fa-check-circle fa-lg"></i>
        <span><strong>Berhasil!</strong> <?= htmlspecialchars($success) ?></span>
    </div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert-error">
        <i class="fas fa-exclamation-circle fa-lg"></i>
        <span><strong>Gagal!</strong> <?= htmlspecialchars($error) ?></span>
    </div>
    <?php endif; ?>

    <div class="row g-4">

        <!-- KIRI: Edit Profil + Ganti Password -->
        <div class="col-lg-7">

            <!-- Edit Info Dasar -->
            <div class="card-custom">
                <div class="card-section-title">
                    <i class="fas fa-user-edit"></i> Edit Informasi Profil
                </div>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" value="@<?= htmlspecialchars($user['username']) ?>" readonly>
                        <div class="form-text">Username tidak dapat diubah.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap <span style="color:#e74c3c">*</span></label>
                        <input type="text" name="nama_lengkap" class="form-control"
                               value="<?= htmlspecialchars($user['nama_lengkap']) ?>"
                               placeholder="Nama lengkap Anda" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Email <span style="color:#e74c3c">*</span></label>
                        <input type="email" name="email" class="form-control"
                               value="<?= htmlspecialchars($user['email']) ?>"
                               placeholder="email@contoh.com" required>
                    </div>
                    <button type="submit" name="update_profil" class="btn-simpan">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                </form>
            </div>

            <!-- Ganti Password -->
            <div class="card-custom">
                <div class="card-section-title">
                    <i class="fas fa-lock"></i> Ganti Password
                </div>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Password Lama <span style="color:#e74c3c">*</span></label>
                        <div class="input-group">
                            <input type="password" name="password_lama" id="passLama"
                                   class="form-control" placeholder="Masukkan password lama" required>
                            <button class="btn btn-outline-secondary" type="button"
                                    onclick="togglePass('passLama', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password Baru <span style="color:#e74c3c">*</span></label>
                        <div class="input-group">
                            <input type="password" name="password_baru" id="passBaru"
                                   class="form-control" placeholder="Minimal 6 karakter"
                                   oninput="cekKekuatan(this.value)" required>
                            <button class="btn btn-outline-secondary" type="button"
                                    onclick="togglePass('passBaru', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <!-- Indikator kekuatan password -->
                        <div class="strength-bar mt-2">
                            <div class="strength-fill" id="strengthFill"></div>
                        </div>
                        <div class="form-text" id="strengthText"></div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Konfirmasi Password Baru <span style="color:#e74c3c">*</span></label>
                        <div class="input-group">
                            <input type="password" name="password_konfirmasi" id="passKonfirmasi"
                                   class="form-control" placeholder="Ulangi password baru"
                                   oninput="cekKonfirmasi()" required>
                            <button class="btn btn-outline-secondary" type="button"
                                    onclick="togglePass('passKonfirmasi', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="form-text" id="konfirmasiText"></div>
                    </div>
                    <button type="submit" name="update_password" class="btn-simpan">
                        <i class="fas fa-key"></i> Ganti Password
                    </button>
                </form>
            </div>

        </div><!-- /kiri -->

        <!-- KANAN: Info Akun + Shortcut -->
        <div class="col-lg-5">

            <!-- Info Akun (read-only) -->
            <div class="card-custom">
                <div class="card-section-title">
                    <i class="fas fa-id-card"></i> Informasi Akun
                </div>
                <div class="info-box">
                    <div class="info-row">
                        <div class="lbl"><i class="fas fa-user"></i> Username</div>
                        <div class="val">@<?= htmlspecialchars($user['username']) ?></div>
                    </div>
                    <div class="info-row">
                        <div class="lbl"><i class="fas fa-envelope"></i> Email</div>
                        <div class="val"><?= htmlspecialchars($user['email']) ?></div>
                    </div>
                    <div class="info-row">
                        <div class="lbl"><i class="fas fa-shield-alt"></i> Role</div>
                        <div class="val">
                            <span style="background:#e8f5e9;color:#2e7d32;padding:3px 12px;border-radius:20px;font-size:.82rem;font-weight:700;">
                                <i class="fas fa-user me-1"></i>User
                            </span>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="lbl"><i class="fas fa-calendar"></i> Bergabung</div>
                        <div class="val"><?= date('d M Y', strtotime($user['created_at'])) ?></div>
                    </div>
                    <div class="info-row">
                        <div class="lbl"><i class="fas fa-shopping-cart"></i> Pesanan</div>
                        <div class="val"><strong><?= $total_pesanan ?></strong> pre-order</div>
                    </div>
                    <div class="info-row">
                        <div class="lbl"><i class="fas fa-heart"></i> Favorit</div>
                        <div class="val"><strong><?= $total_favorit ?></strong> buku disimpan</div>
                    </div>
                </div>
            </div>

             <!-- Shortcut Menu -->
            <div class="card-custom">
                <div class="card-section-title">
                    <i class="fas fa-th-large"></i> Menu Cepat
                </div>
 
                <a href="dashboard.php" class="shortcut-link">
                    <div class="shortcut-icon"><i class="fas fa-th-large"></i></div>
                    <div class="shortcut-text">
                        <strong>Dashboard</strong>
                        <small>Kembali ke halaman utama</small>
                    </div>
                    <i class="fas fa-chevron-right"></i>
                </a>
 
                <a href="pesanan.php" class="shortcut-link">
                    <div class="shortcut-icon"><i class="fas fa-shopping-bag"></i></div>
                    <div class="shortcut-text">
                        <strong>Pesanan Saya</strong>
                        <small><?= $total_pesanan ?> total pre-order</small>
                    </div>
                    <i class="fas fa-chevron-right"></i>
                </a>
 
                <a href="../index.php" class="shortcut-link">
                    <div class="shortcut-icon"><i class="fas fa-book-open"></i></div>
                    <div class="shortcut-text">
                        <strong>Katalog Buku</strong>
                        <small>Jelajahi koleksi buku braille</small>
                    </div>
                    <i class="fas fa-chevron-right"></i>
                </a>
            </div>
        </div><!-- /kanan -->

    </div>
</div>

<!-- FOOTER -->
<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="col-md-5 mb-4 mb-md-0">
                <h5>
                    <i class="fas fa-book-open me-2" style="color: var(--accent-gold);"></i>
                    Braille Book Catalog
                </h5>
                <p style="opacity: 0.9;">
                    Yayasan Raudlatul Makfufin - Percetakan Buku Islam Braille<br>
                    Menyediakan Al-Qur'an Braille dan literasi Islami untuk penyandang disabilitas netra sejak 1997.
                </p>
                <div class="social-links mt-3">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-whatsapp"></i></a>
                    <a href="#"><i class="far fa-envelope"></i></a>
                </div>
            </div>
            <div class="col-md-3 mb-4 mb-md-0">
                <h5>Quick Links</h5>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="../index.php"><i class="fas fa-chevron-right me-2" style="font-size:12px;"></i>Home</a></li>
                    <li class="mb-2"><a href="#"><i class="fas fa-chevron-right me-2" style="font-size:12px;"></i>About Us</a></li>
                    <li class="mb-2"><a href="#"><i class="fas fa-chevron-right me-2" style="font-size:12px;"></i>Contact</a></li>
                    <li class="mb-2"><a href="../login.php"><i class="fas fa-chevron-right me-2" style="font-size:12px;"></i>Login</a></li>
                </ul>
            </div>
            <div class="col-md-4">
                <h5>Kontak Kami</h5>
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <i class="fas fa-map-marker-alt me-2" style="color: var(--accent-gold);"></i>
                        Jl. ... (alamat yayasan)
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-phone me-2" style="color: var(--accent-gold);"></i>
                        +62 xxx-xxxx-xxxx
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-envelope me-2" style="color: var(--accent-gold);"></i>
                        info@raudlatulmakfufin.org
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-clock me-2" style="color: var(--accent-gold);"></i>
                        Senin - Jumat: 08:00 - 16:00 WIB
                    </li>
                </ul>
            </div>
        </div>
        <hr style="border-color: rgba(255,255,255,0.2); margin: 30px 0 20px;">
        <div class="row">
            <div class="col-md-6">
                <p class="mb-0" style="opacity: 0.8;">
                    © 2026 Yayasan Raudlatul Makfufin. All rights reserved.
                </p>
            </div>
            <div class="col-md-6 text-md-end">
                <p class="mb-0" style="opacity: 0.8;">
                    Developed with <i class="fas fa-heart" style="color: #ff6b6b;"></i> for Accessibility
                </p>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Toggle show/hide password
    function togglePass(id, btn) {
        const input = document.getElementById(id);
        const icon  = btn.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    }

    // Indikator kekuatan password
    function cekKekuatan(val) {
        const fill = document.getElementById('strengthFill');
        const text = document.getElementById('strengthText');
        let score = 0;
        if (val.length >= 6)  score++;
        if (val.length >= 10) score++;
        if (/[A-Z]/.test(val)) score++;
        if (/[0-9]/.test(val)) score++;
        if (/[^A-Za-z0-9]/.test(val)) score++;

        const config = [
            { w: '0%',   color: '#eee',    label: '' },
            { w: '25%',  color: '#e74c3c', label: 'Lemah' },
            { w: '50%',  color: '#ffc107', label: 'Cukup' },
            { w: '75%',  color: '#1cceac', label: 'Kuat' },
            { w: '100%', color: '#15a38a', label: 'Sangat Kuat' },
        ];
        const c = config[Math.min(score, 4)];
        fill.style.width      = val.length ? c.w : '0%';
        fill.style.background = c.color;
        text.textContent      = val.length ? c.label : '';
        text.style.color      = c.color;
    }

    // Cek kesesuaian konfirmasi password
    function cekKonfirmasi() {
        const baru  = document.getElementById('passBaru').value;
        const konf  = document.getElementById('passKonfirmasi').value;
        const text  = document.getElementById('konfirmasiText');
        if (!konf) { text.textContent = ''; return; }
        if (baru === konf) {
            text.textContent = '✓ Password cocok';
            text.style.color = '#2e7d32';
        } else {
            text.textContent = '✗ Password tidak cocok';
            text.style.color = '#e74c3c';
        }
    }
</script>
</body>
</html>