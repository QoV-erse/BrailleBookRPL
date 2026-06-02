<?php
require_once '../../includes/session.php';
require_once '../../includes/config.php';

if ($_SESSION['role'] != 'admin') {
    header("Location: ../../login.php");
    exit;
}

// Filter status
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'semua';
$valid  = ['semua', 'pending', 'diproses', 'selesai', 'batal'];
if (!in_array($filter, $valid)) $filter = 'semua';
$where_filter = ($filter != 'semua') ? "WHERE p.status = '$filter'" : '';

// Hitung per status
$q_stat = mysqli_query($conn, "SELECT status, COUNT(*) as jml FROM pesanan GROUP BY status");
$stat   = ['pending' => 0, 'diproses' => 0, 'selesai' => 0, 'batal' => 0];
while ($r = mysqli_fetch_assoc($q_stat)) $stat[$r['status']] = (int)$r['jml'];
$total_semua = array_sum($stat);

// Ambil pesanan dengan filter
$q_pesanan = mysqli_query($conn,
    "SELECT p.*,
        u.nama_lengkap, u.email,
        (SELECT b.judul FROM detail_pesanan dp
         JOIN buku b ON dp.buku_id = b.id
         WHERE dp.pesanan_id = p.id LIMIT 1) AS judul_buku,
        (SELECT COUNT(*) FROM detail_pesanan dp
         WHERE dp.pesanan_id = p.id) AS total_item
     FROM pesanan p
     JOIN users u ON p.user_id = u.id
     $where_filter
     ORDER BY p.created_at DESC"
);

$status_config = [
    'pending'  => ['label' => 'Menunggu DP',  'color' => '#856404', 'bg' => '#fff3cd', 'border' => '#ffc107', 'icon' => 'fa-clock'],
    'diproses' => ['label' => 'Diproses',      'color' => '#0c63e4', 'bg' => '#cfe2ff', 'border' => '#9ec5fe', 'icon' => 'fa-spinner'],
    'selesai'  => ['label' => 'Selesai',       'color' => '#0f5132', 'bg' => '#d1e7dd', 'border' => '#a3cfbb', 'icon' => 'fa-check-circle'],
    'batal'    => ['label' => 'Dibatalkan',    'color' => '#842029', 'bg' => '#f8d7da', 'border' => '#f1aeb5', 'icon' => 'fa-times-circle'],
];

function rupiah($n) {
    return "Rp " . number_format((float)$n, 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Masuk - Admin Braille</title>
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

        /* STATUS BADGE */
        .status-badge { display:inline-flex; align-items:center; gap:5px; padding:5px 12px; border-radius:20px; font-weight:600; font-size:.78rem; border:1.5px solid; white-space:nowrap; }

        /* ACTION BUTTONS */
        .btn-detail-order { background:#e8f5e9; color:#2e7d32; border:1px solid #a5d6a7; padding:6px 14px; border-radius:8px; font-size:.82rem; font-weight:600; text-decoration:none; transition:all .3s; display:inline-flex; align-items:center; gap:5px; }
        .btn-detail-order:hover { background:#2e7d32; color:white; transform:translateY(-1px); }

        /* EMPTY STATE */
        .empty-state { text-align:center; padding:60px 20px; }
        .empty-state i { font-size:56px; color:#ccc; margin-bottom:16px; }
        .empty-state h5 { color:#aaa; margin-bottom:8px; }

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
            <a class="nav-link active" href="index.php">
                <i class="fas fa-shopping-cart"></i> Pesanan Masuk
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="../users/index.php">
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
            <h2><i class="fas fa-shopping-cart"></i> Pesanan Masuk</h2>
            <p class="text-muted mb-0" style="font-size:.9rem;">Kelola seluruh pre-order dari pelanggan</p>
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

    <!-- Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="stat-card">
                <div class="stat-icon" style="background:#f0fdf9;">
                    <i class="fas fa-inbox" style="color:var(--primary-main)"></i>
                </div>
                <div class="stat-value"><?= $total_semua ?></div>
                <div class="stat-label">Total Pesanan</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card">
                <div class="stat-icon" style="background:#fff3cd;">
                    <i class="fas fa-clock" style="color:#856404"></i>
                </div>
                <div class="stat-value" style="color:#856404"><?= $stat['pending'] ?></div>
                <div class="stat-label">Menunggu DP</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card">
                <div class="stat-icon" style="background:#cfe2ff;">
                    <i class="fas fa-spinner" style="color:#0c63e4"></i>
                </div>
                <div class="stat-value" style="color:#0c63e4"><?= $stat['diproses'] ?></div>
                <div class="stat-label">Sedang Diproses</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card">
                <div class="stat-icon" style="background:#d1e7dd;">
                    <i class="fas fa-check-circle" style="color:#0f5132"></i>
                </div>
                <div class="stat-value" style="color:#0f5132"><?= $stat['selesai'] ?></div>
                <div class="stat-label">Selesai</div>
            </div>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="filter-tabs">
        <a href="index.php?filter=semua"    class="filter-tab <?= $filter=='semua'    ? 'active':'' ?>">Semua <span class="badge-count"><?= $total_semua ?></span></a>
        <a href="index.php?filter=pending"  class="filter-tab <?= $filter=='pending'  ? 'active':'' ?>">Menunggu DP <span class="badge-count"><?= $stat['pending'] ?></span></a>
        <a href="index.php?filter=diproses" class="filter-tab <?= $filter=='diproses' ? 'active':'' ?>">Diproses <span class="badge-count"><?= $stat['diproses'] ?></span></a>
        <a href="index.php?filter=selesai"  class="filter-tab <?= $filter=='selesai'  ? 'active':'' ?>">Selesai <span class="badge-count"><?= $stat['selesai'] ?></span></a>
        <a href="index.php?filter=batal"    class="filter-tab <?= $filter=='batal'    ? 'active':'' ?>">Dibatalkan <span class="badge-count"><?= $stat['batal'] ?></span></a>
    </div>

    <!-- Tabel Pesanan -->
    <div class="table-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th width="12%">No. Pesanan</th>
                        <th width="18%">Pelanggan</th>
                        <th width="22%">Buku Dipesan</th>
                        <th width="8%">Qty</th>
                        <th width="12%">Total</th>
                        <th width="12%">DP</th>
                        <th width="11%">Status</th>
                        <th width="10%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $no = 1;
                $ada = false;
                while ($p = mysqli_fetch_assoc($q_pesanan)):
                    $ada = true;
                    $st  = $status_config[$p['status']] ?? $status_config['pending'];
                ?>
                <tr>
                    <td><strong><?= $no++ ?></strong></td>
                    <td>
                        <span style="font-weight:700;color:var(--primary-dark);font-size:.9rem;">
                            #<?= str_pad($p['id'], 5, '0', STR_PAD_LEFT) ?>
                        </span><br>
                        <small class="text-muted"><?= date('d M Y', strtotime($p['created_at'])) ?></small>
                    </td>
                    <td>
                        <strong style="font-size:.9rem;"><?= htmlspecialchars($p['nama_lengkap']) ?></strong><br>
                        <small class="text-muted"><?= htmlspecialchars($p['email']) ?></small>
                    </td>
                    <td>
                        <span style="font-size:.88rem;font-weight:600;">
                            <?= htmlspecialchars($p['judul_buku'] ?? '-') ?>
                        </span>
                        <?php if ($p['total_item'] > 1): ?>
                            <br><small class="text-muted">+<?= $p['total_item'] - 1 ?> item lainnya</small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="fw-bold"><?= $p['jumlah'] ?></span>
                        <small class="text-muted d-block">eks</small>
                    </td>
                    <td>
                        <strong style="color:var(--primary-dark);font-size:.9rem;">
                            <?= rupiah($p['total_harga']) ?>
                        </strong>
                    </td>
                    <td>
                        <span style="color:#856404;font-weight:600;font-size:.88rem;">
                            <?= rupiah($p['dp_nominal']) ?>
                        </span>
                    </td>
                    <td>
                        <span class="status-badge"
                              style="color:<?= $st['color'] ?>;background:<?= $st['bg'] ?>;border-color:<?= $st['border'] ?>">
                            <i class="fas <?= $st['icon'] ?>"></i> <?= $st['label'] ?>
                        </span>
                    </td>
                    <td>
                        <a href="detail.php?id=<?= $p['id'] ?>" class="btn-detail-order">
                            <i class="fas fa-eye"></i> Detail
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>

                <?php if (!$ada): ?>
                <tr>
                    <td colspan="9">
                        <div class="empty-state">
                            <i class="fas fa-inbox d-block"></i>
                            <h5>
                                <?= $filter == 'semua'
                                    ? 'Belum ada pesanan masuk'
                                    : 'Tidak ada pesanan "' . ($status_config[$filter]['label'] ?? $filter) . '"' ?>
                            </h5>
                            <p class="text-muted" style="font-size:.88rem;">
                                <?= $filter == 'semua'
                                    ? 'Pesanan akan muncul di sini setelah pelanggan melakukan pre-order.'
                                    : 'Coba lihat semua pesanan.' ?>
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