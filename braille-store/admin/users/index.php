<?php
require_once '../../includes/session.php';
require_once '../../includes/config.php';

if ($_SESSION['role'] != 'admin') {
    header("Location: ../../login.php");
    exit;
}

// Filter role
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'semua';
if (!in_array($filter, ['semua', 'user', 'admin'])) $filter = 'semua';
$where_filter = ($filter != 'semua') ? "WHERE role = '$filter'" : '';

// Hitung per role
$total_user  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as jml FROM users WHERE role = 'user'"))['jml'];
$total_admin = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as jml FROM users WHERE role = 'admin'"))['jml'];
$total_semua = $total_user + $total_admin;

// Ambil data user dengan statistik pesanan & favorit
$q_users = mysqli_query($conn,
    "SELECT u.*,
        (SELECT COUNT(*) FROM pesanan p WHERE p.user_id = u.id) AS total_pesanan,
        (SELECT COUNT(*) FROM favorit f WHERE f.user_id = u.id) AS total_favorit
     FROM users u
     $where_filter
     ORDER BY u.created_at DESC"
);

// Notifikasi
$notif = '';
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'hapus_sukses') $notif = 'sukses';
    if ($_GET['status'] == 'hapus_gagal')  $notif = 'gagal';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pengguna - Admin Braille</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-main: #1cceac;
            --primary-dark: #15a38a;
            --accent-gold:  #FFD700;
            --light-bg:     #f4f6f9;
            --dark-text:    #2c3e50;
            --sidebar-width:280px;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',sans-serif; background:var(--light-bg); color:var(--dark-text); }

        /* SIDEBAR */
        .sidebar { position:fixed; top:0; left:0; height:100vh; width:var(--sidebar-width); background:linear-gradient(180deg,var(--primary-main) 0%,var(--primary-dark) 100%); box-shadow:4px 0 20px rgba(28,206,172,.15); z-index:1000; overflow-y:auto; }
        .sidebar-brand { padding:25px 20px; border-bottom:1px solid rgba(255,255,255,.2); text-align:center; }
        .sidebar-brand i { font-size:40px; color:var(--accent-gold); }
        .sidebar-brand h4 { color:white; margin-top:10px; font-weight:bold; }
        .sidebar-nav { padding:20px 0; }
        .sidebar-nav .nav-link { color:rgba(255,255,255,.85); padding:15px 25px; font-size:16px; font-weight:500; transition:all .3s; border-left:4px solid transparent; display:flex; align-items:center; }
        .sidebar-nav .nav-link:hover { color:white; background:rgba(255,255,255,.15); border-left-color:var(--accent-gold); }
        .sidebar-nav .nav-link.active { color:white; background:rgba(255,255,255,.2); border-left-color:var(--accent-gold); }
        .sidebar-nav .nav-link i { width:25px; margin-right:10px; color:var(--accent-gold); }
        .sidebar-footer { position:absolute; bottom:0; width:100%; padding:20px; border-top:1px solid rgba(255,255,255,.2); color:rgba(255,255,255,.8); }

        /* MAIN */
        .main-content { margin-left:var(--sidebar-width); min-height:100vh; padding:25px 30px; }

        /* TOP BAR */
        .top-bar { display:flex; justify-content:space-between; align-items:center; margin-bottom:30px; padding-bottom:20px; border-bottom:1px solid #e0e0e0; }
        .top-bar h2 { color:var(--primary-dark); margin:0; font-size:1.5rem; }
        .top-bar h2 i { color:var(--accent-gold); margin-right:8px; }
        .admin-avatar { width:45px; height:45px; background:linear-gradient(135deg,var(--primary-main),var(--primary-dark)); border-radius:50%; display:flex; align-items:center; justify-content:center; color:white; font-size:20px; font-weight:bold; }

        /* STAT CARDS */
        .stat-card { background:white; border-radius:14px; padding:20px; box-shadow:0 5px 15px rgba(0,0,0,.05); border:1px solid #eee; transition:all .3s; }
        .stat-card:hover { transform:translateY(-3px); box-shadow:0 8px 22px rgba(28,206,172,.12); }
        .stat-icon { width:50px; height:50px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:22px; margin-bottom:12px; }
        .stat-value { font-size:1.8rem; font-weight:800; color:var(--primary-dark); }
        .stat-label { color:#888; font-size:.8rem; text-transform:uppercase; letter-spacing:.5px; }

        /* FILTER TABS */
        .filter-tabs { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:20px; }
        .filter-tab { background:white; border:2px solid #e0e0e0; color:#555; padding:8px 18px; border-radius:25px; font-weight:600; font-size:.88rem; text-decoration:none; transition:all .3s; }
        .filter-tab:hover { border-color:var(--primary-main); color:var(--primary-dark); }
        .filter-tab.active { background:var(--primary-main); border-color:var(--primary-main); color:white; }
        .filter-tab .badge-count { background:rgba(0,0,0,.1); padding:1px 8px; border-radius:20px; margin-left:6px; font-size:.78rem; }
        .filter-tab.active .badge-count { background:rgba(255,255,255,.25); }

        /* TABLE CARD */
        .table-card { background:white; border-radius:16px; padding:24px; box-shadow:0 5px 15px rgba(0,0,0,.05); border:1px solid #eee; }
        .table thead tr th { background:linear-gradient(135deg,var(--primary-main),var(--primary-dark)); color:white; font-weight:600; padding:13px 14px; border:none; font-size:.82rem; text-transform:uppercase; letter-spacing:.4px; white-space:nowrap; }
        .table tbody tr { border-bottom:1px solid #f5f5f5; transition:background .2s; }
        .table tbody tr:hover { background:#f0fdf9; }
        .table tbody td { padding:14px; vertical-align:middle; font-size:.9rem; }

        /* AVATAR USER */
        .user-avatar { width:42px; height:42px; border-radius:50%; background:linear-gradient(135deg,var(--primary-main),var(--primary-dark)); display:flex; align-items:center; justify-content:center; color:white; font-size:18px; font-weight:700; flex-shrink:0; }

        /* ROLE BADGE */
        .badge-admin { background:#fff3cd; color:#856404; border:1.5px solid #ffc107; padding:4px 12px; border-radius:20px; font-weight:700; font-size:.78rem; }
        .badge-user  { background:#e8f5e9; color:#2e7d32; border:1.5px solid #a5d6a7; padding:4px 12px; border-radius:20px; font-weight:700; font-size:.78rem; }

        /* MINI STAT di tabel */
        .mini-stat { display:inline-flex; align-items:center; gap:5px; font-size:.82rem; color:#666; }
        .mini-stat i { color:var(--primary-main); }

        /* ACTION BUTTONS */
        .btn-hapus { background:#ffebee; color:#c62828; border:1px solid #ef9a9a; padding:6px 14px; border-radius:8px; font-size:.82rem; font-weight:600; text-decoration:none; transition:all .3s; display:inline-flex; align-items:center; gap:5px; cursor:pointer; }
        .btn-hapus:hover { background:#c62828; color:white; transform:translateY(-1px); }

        /* ALERT */
        .alert-custom { border-radius:12px; padding:14px 18px; border:none; margin-bottom:20px; display:flex; align-items:center; gap:10px; }
        .alert-success-custom { background:#e8f5e9; color:#2e7d32; border-left:4px solid #4caf50; }
        .alert-danger-custom  { background:#ffebee; color:#c62828; border-left:4px solid #f44336; }

        /* EMPTY STATE */
        .empty-state { text-align:center; padding:60px 20px; }
        .empty-state i { font-size:56px; color:#ccc; margin-bottom:16px; }
        .empty-state h5 { color:#aaa; }

        /* FOOTER */
        .footer-admin { margin-top:30px; padding-top:20px; border-top:1px solid #e0e0e0; text-align:center; color:#aaa; font-size:.875rem; }

        @media(max-width:768px) { .sidebar{transform:translateX(-100%);} .main-content{margin-left:0;padding:15px;} }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-brand">
        <i class="fas fa-book-open"></i>
        <h4>Braille Admin</h4>
        <small class="text-white-50">Yayasan Raudlatul Makfufin</small>
    </div>
    <ul class="nav flex-column sidebar-nav">
        <li class="nav-item">
            <a class="nav-link" href="../dashboard.php">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="../buku/index.php">
                <i class="fas fa-book"></i> Kelola Buku
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="../pesanan/index.php">
                <i class="fas fa-shopping-cart"></i> Pesanan Masuk
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link active" href="index.php">
                <i class="fas fa-users"></i> Data Pengguna
            </a>
        </li>
    </ul>
    <div class="sidebar-footer">
        <small><i class="far fa-clock me-1"></i><?= date('d M Y, H:i') ?> WIB</small>
    </div>
</div>

<!-- MAIN CONTENT -->
<div class="main-content">

    <!-- Top Bar -->
    <div class="top-bar">
        <div>
            <h2><i class="fas fa-users"></i> Data Pengguna</h2>
            <p class="text-muted mb-0" style="font-size:.9rem;">Kelola seluruh akun yang terdaftar</p>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="text-end">
                <strong style="font-size:.95rem;"><?= htmlspecialchars($_SESSION['nama_lengkap']) ?></strong><br>
                <small class="text-muted">Administrator</small>
            </div>
            <div class="admin-avatar">
                <?= strtoupper(substr($_SESSION['nama_lengkap'], 0, 1)) ?>
            </div>
            <a href="../../logout.php" class="btn btn-outline-danger btn-sm">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </div>

    <!-- Notifikasi -->
    <?php if ($notif == 'sukses'): ?>
    <div class="alert-custom alert-success-custom">
        <i class="fas fa-check-circle fa-lg"></i>
        <span><strong>Berhasil!</strong> Akun pengguna telah dihapus.</span>
    </div>
    <?php elseif ($notif == 'gagal'): ?>
    <div class="alert-custom alert-danger-custom">
        <i class="fas fa-exclamation-triangle fa-lg"></i>
        <span><strong>Gagal!</strong> Tidak dapat menghapus akun ini.</span>
    </div>
    <?php endif; ?>

    <!-- Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4 col-6">
            <div class="stat-card">
                <div class="stat-icon" style="background:#f0fdf9;">
                    <i class="fas fa-users" style="color:var(--primary-main)"></i>
                </div>
                <div class="stat-value"><?= $total_semua ?></div>
                <div class="stat-label">Total Pengguna</div>
            </div>
        </div>
        <div class="col-md-4 col-6">
            <div class="stat-card">
                <div class="stat-icon" style="background:#e8f5e9;">
                    <i class="fas fa-user" style="color:#2e7d32"></i>
                </div>
                <div class="stat-value" style="color:#2e7d32"><?= $total_user ?></div>
                <div class="stat-label">User Biasa</div>
            </div>
        </div>
        <div class="col-md-4 col-6">
            <div class="stat-card">
                <div class="stat-icon" style="background:#fff3cd;">
                    <i class="fas fa-user-shield" style="color:#856404"></i>
                </div>
                <div class="stat-value" style="color:#856404"><?= $total_admin ?></div>
                <div class="stat-label">Administrator</div>
            </div>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="filter-tabs">
        <a href="index.php?filter=semua" class="filter-tab <?= $filter=='semua' ? 'active':'' ?>">
            Semua <span class="badge-count"><?= $total_semua ?></span>
        </a>
        <a href="index.php?filter=user"  class="filter-tab <?= $filter=='user'  ? 'active':'' ?>">
            User Biasa <span class="badge-count"><?= $total_user ?></span>
        </a>
        <a href="index.php?filter=admin" class="filter-tab <?= $filter=='admin' ? 'active':'' ?>">
            Administrator <span class="badge-count"><?= $total_admin ?></span>
        </a>
    </div>

    <!-- Tabel Pengguna -->
    <div class="table-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th width="4%">No</th>
                        <th width="25%">Pengguna</th>
                        <th width="20%">Email</th>
                        <th width="10%">Role</th>
                        <th width="10%">Pesanan</th>
                        <th width="10%">Favorit</th>
                        <th width="13%">Terdaftar</th>
                        <th width="8%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $no  = 1;
                $ada = false;
                while ($u = mysqli_fetch_assoc($q_users)):
                    $ada = true;
                    $is_me = ($u['id'] == $_SESSION['user_id']);
                ?>
                <tr>
                    <td><strong><?= $no++ ?></strong></td>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <div class="user-avatar">
                                <?= strtoupper(substr($u['nama_lengkap'], 0, 1)) ?>
                            </div>
                            <div>
                                <strong style="font-size:.9rem;">
                                    <?= htmlspecialchars($u['nama_lengkap']) ?>
                                    <?php if ($is_me): ?>
                                        <span style="color:var(--primary-main);font-size:.75rem;font-weight:600;"> (Anda)</span>
                                    <?php endif; ?>
                                </strong><br>
                                <small class="text-muted">@<?= htmlspecialchars($u['username']) ?></small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <small><?= htmlspecialchars($u['email']) ?></small>
                    </td>
                    <td>
                        <?php if ($u['role'] == 'admin'): ?>
                            <span class="badge-admin"><i class="fas fa-shield-alt me-1"></i>Admin</span>
                        <?php else: ?>
                            <span class="badge-user"><i class="fas fa-user me-1"></i>User</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="mini-stat">
                            <i class="fas fa-shopping-cart"></i>
                            <strong><?= $u['total_pesanan'] ?></strong>
                        </div>
                    </td>
                    <td>
                        <div class="mini-stat">
                            <i class="fas fa-heart"></i>
                            <strong><?= $u['total_favorit'] ?></strong>
                        </div>
                    </td>
                    <td>
                        <small><?= date('d M Y', strtotime($u['created_at'])) ?></small><br>
                        <small class="text-muted"><?= date('H:i', strtotime($u['created_at'])) ?> WIB</small>
                    </td>
                    <td>
                        <?php if ($is_me): ?>
                            <!-- Tidak bisa hapus akun sendiri -->
                            <span class="text-muted" style="font-size:.8rem;">
                                <i class="fas fa-lock"></i>
                            </span>
                        <?php else: ?>
                            <a href="hapus.php?id=<?= $u['id'] ?>"
                               class="btn-hapus"
                               onclick="return confirm('Yakin hapus akun <?= addslashes(htmlspecialchars($u['nama_lengkap'])) ?>?\n\nSemua data pesanan dan favorit user ini juga akan terhapus.')">
                                <i class="fas fa-trash"></i> Hapus
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>

                <?php if (!$ada): ?>
                <tr>
                    <td colspan="8">
                        <div class="empty-state">
                            <i class="fas fa-users d-block"></i>
                            <h5>Tidak ada pengguna ditemukan</h5>
                            <p class="text-muted" style="font-size:.88rem;">
                                <?= $filter != 'semua' ? 'Coba lihat semua pengguna.' : 'Belum ada yang mendaftar.' ?>
                            </p>
                            <?php if ($filter != 'semua'): ?>
                                <a href="index.php" class="btn btn-sm" style="background:var(--primary-main);color:white;border-radius:20px;">
                                    Lihat Semua
                                </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="footer-admin">
        <i class="fas fa-book-open me-2" style="color:var(--primary-main)"></i>
        © 2026 Yayasan Raudlatul Makfufin — Sistem Informasi Katalog Buku Braille
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>