<?php
session_start();
require_once '../includes/config.php';

if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit; }
if ($_SESSION['role'] == 'admin') { header("Location: ../admin/dashboard.php"); exit; }

$user_id = $_SESSION['user_id'];

// Hitung total semua pesanan
$total_pesanan = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as jml FROM pesanan WHERE user_id = $user_id"
))['jml'];

// Hitung per status
$q_stat = mysqli_query($conn,
    "SELECT status, COUNT(*) as jml FROM pesanan WHERE user_id = $user_id GROUP BY status"
);
$stat_count = ['pending' => 0, 'diproses' => 0, 'selesai' => 0, 'batal' => 0];
while ($r = mysqli_fetch_assoc($q_stat)) {
    $stat_count[$r['status']] = (int)$r['jml'];
}

$status_config = [
    'pending'  => ['label' => 'Menunggu DP',  'color' => '#856404', 'bg' => '#fff3cd', 'border' => '#ffc107', 'icon' => 'fa-clock'],
    'diproses' => ['label' => 'Diproses',      'color' => '#0c63e4', 'bg' => '#cfe2ff', 'border' => '#9ec5fe', 'icon' => 'fa-spinner'],
    'selesai'  => ['label' => 'Selesai',       'color' => '#0f5132', 'bg' => '#d1e7dd', 'border' => '#a3cfbb', 'icon' => 'fa-check-circle'],
    'batal'    => ['label' => 'Dibatalkan',    'color' => '#842029', 'bg' => '#f8d7da', 'border' => '#f1aeb5', 'icon' => 'fa-times-circle'],
];

// Filter aktif
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'semua';
if (!in_array($filter, ['semua', 'pending', 'diproses', 'selesai', 'batal'])) $filter = 'semua';
$where_filter = ($filter != 'semua') ? "AND p.status = '$filter'" : '';

// Query pesanan dengan filter
$q_pesanan = mysqli_query($conn,
    "SELECT p.*,
        (SELECT b.judul FROM detail_pesanan dp
         JOIN buku b ON dp.buku_id = b.id
         WHERE dp.pesanan_id = p.id LIMIT 1) AS judul_buku,
        (SELECT b.gambar FROM detail_pesanan dp
         JOIN buku b ON dp.buku_id = b.id
         WHERE dp.pesanan_id = p.id LIMIT 1) AS gambar_buku,
        (SELECT COUNT(*) FROM detail_pesanan dp
         WHERE dp.pesanan_id = p.id) AS total_item
     FROM pesanan p
     WHERE p.user_id = $user_id $where_filter
     ORDER BY p.created_at DESC"
);
$jml_filtered = mysqli_num_rows($q_pesanan);

function rupiah($n) {
    return "Rp " . number_format((float)$n, 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Saya - Braille Book Catalog</title>
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

        .navbar { background:linear-gradient(135deg,var(--primary-main),var(--primary-dark))!important; box-shadow:0 4px 15px rgba(28,206,172,.2); padding:15px 0; position: sticky; top: 0; z-index: 999; }
        .navbar-brand { font-size:1.4rem; font-weight:bold; color:white!important; }
        .navbar-brand i { color:var(--accent-gold); margin-right:8px; }
        .navbar-nav .nav-link { color:rgba(255,255,255,.9)!important; font-weight:500; padding:8px 16px!important; transition:all .3s; }
        .navbar-nav .nav-link:hover, .navbar-nav .nav-link.active { color:var(--accent-gold)!important; background:rgba(255,255,255,.15); border-radius:8px; }
        .user-badge { background:rgba(255,255,255,.2); padding:7px 15px; border-radius:50px; color:white; font-size:.95rem; }
        .user-badge i { color:var(--accent-gold); margin-right:6px; }

        /* WELCOME */
        .welcome-section { background:linear-gradient(135deg,var(--primary-main),var(--primary-dark)); padding:35px 0; border-radius:0 0 30px 30px; margin-bottom:35px; }
        .welcome-section h2 { color:white; font-weight:bold; font-size:1.8rem; }
        .welcome-section h2 i { color:var(--accent-gold); }
        .welcome-section p { color:rgba(255,255,255,.85); margin:6px 0 0; }

        .stat-mini { background:rgba(255,255,255,.15); border:1px solid rgba(255,255,255,.25); border-radius:14px; padding:14px 18px; text-align:center; color:white; }
        .stat-mini .num { font-size:1.6rem; font-weight:800; }
        .stat-mini .lbl { font-size:.78rem; opacity:.85; margin-top:2px; }

        /* FILTER TABS */
        .filter-tabs { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:24px; }
        .filter-tab { background:white; border:2px solid #e0e0e0; color:#555; padding:8px 18px; border-radius:25px; font-weight:600; font-size:.9rem; text-decoration:none; transition:all .3s; }
        .filter-tab:hover { border-color:var(--primary-main); color:var(--primary-dark); }
        .filter-tab.active { background:var(--primary-main); border-color:var(--primary-main); color:white; }
        .filter-tab .badge-count { background:rgba(0,0,0,.1); padding:1px 8px; border-radius:20px; margin-left:6px; font-size:.8rem; }
        .filter-tab.active .badge-count { background:rgba(255,255,255,.25); }

        /* PESANAN CARD */
        .pesanan-card { background:white; border-radius:18px; border:1.5px solid #eee; box-shadow:0 5px 18px rgba(0,0,0,.06); margin-bottom:18px; transition:all .3s; overflow:hidden; }
        .pesanan-card:hover { border-color:var(--primary-main); box-shadow:0 10px 28px rgba(28,206,172,.15); transform:translateY(-2px); }

        .pesanan-header { background:linear-gradient(135deg,#f8fffe,#f0fdf9); padding:14px 20px; border-bottom:1.5px solid #e8f5f2; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px; }
        .pesanan-header .order-id { font-weight:700; color:var(--primary-dark); font-size:.95rem; }
        .pesanan-header .order-date { color:#888; font-size:.83rem; margin-top:2px; }

        .status-badge { display:inline-flex; align-items:center; gap:6px; padding:5px 14px; border-radius:20px; font-weight:600; font-size:.82rem; border:1.5px solid; }

        .pesanan-body { padding:18px 20px; display:flex; align-items:center; gap:16px; }

        .buku-thumb { width:60px; height:60px; border-radius:10px; flex-shrink:0; overflow:hidden; background:linear-gradient(135deg,var(--primary-main),var(--primary-dark)); display:flex; align-items:center; justify-content:center; color:white; font-size:22px; }
        .buku-thumb img { width:100%; height:100%; object-fit:cover; }

        .pesanan-info { flex:1; min-width:0; }
        .pesanan-info .judul { font-weight:700; font-size:.95rem; color:var(--dark-text); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .pesanan-info .meta { color:#888; font-size:.83rem; margin-top:3px; }

        .pesanan-harga { text-align:right; flex-shrink:0; }
        .pesanan-harga .total { font-size:1rem; font-weight:800; color:var(--primary-dark); }
        .pesanan-harga .dp-label { font-size:.8rem; color:#856404; background:#fff3cd; padding:2px 8px; border-radius:10px; display:inline-block; margin-top:3px; }

        .pesanan-footer { padding:12px 20px; border-top:1px solid #f0f0f0; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px; }
        .pesanan-footer .estimasi { font-size:.83rem; color:#555; }
        .pesanan-footer .estimasi i { color:var(--primary-main); margin-right:4px; }

        .btn-lihat-detail { background:var(--primary-main); color:white; border:none; padding:8px 20px; border-radius:20px; font-weight:600; font-size:.88rem; text-decoration:none; transition:all .3s; display:inline-flex; align-items:center; gap:6px; }
        .btn-lihat-detail:hover { background:var(--primary-dark); color:white; transform:translateY(-1px); }

        /* EMPTY STATE */
        .empty-state { background:white; border-radius:20px; padding:60px 30px; text-align:center; box-shadow:0 5px 20px rgba(0,0,0,.05); border:2px dashed #e0e0e0; }
        .empty-state i { color:var(--primary-main); opacity:.3; margin-bottom:20px; }
        .empty-state h4 { color:#aaa; margin-bottom:10px; }
        .empty-state p { color:#bbb; margin-bottom:24px; }
        .btn-mulai { background:linear-gradient(135deg,var(--primary-main),var(--primary-dark)); color:white; border:none; padding:12px 28px; border-radius:30px; font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:8px; transition:all .3s; }
        .btn-mulai:hover { transform:translateY(-3px); box-shadow:0 8px 20px rgba(28,206,172,.4); color:white; }

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
            .pesanan-harga { display:none; }
            .welcome-section h2 { font-size:1.4rem; }
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

<!-- WELCOME + STATISTIK -->
<section class="welcome-section">
    <div class="container">
        <div class="row align-items-center g-3">
            <div class="col-md-5">
                <h2><i class="fas fa-shopping-bag"></i> Pesanan Saya</h2>
                <p>Riwayat seluruh pre-order buku braille Anda</p>
            </div>
            <div class="col-md-7">
                <div class="row g-2">
                    <div class="col-3">
                        <div class="stat-mini">
                            <div class="num"><?= $total_pesanan ?></div>
                            <div class="lbl">Semua</div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="stat-mini">
                            <div class="num"><?= $stat_count['pending'] ?></div>
                            <div class="lbl">Pending</div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="stat-mini">
                            <div class="num"><?= $stat_count['diproses'] ?></div>
                            <div class="lbl">Diproses</div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="stat-mini">
                            <div class="num"><?= $stat_count['selesai'] ?></div>
                            <div class="lbl">Selesai</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="container pb-5">

    <!-- Filter Tabs -->
    <div class="filter-tabs">
        <a href="pesanan.php?filter=semua"    class="filter-tab <?= $filter=='semua'    ? 'active':'' ?>">
            Semua <span class="badge-count"><?= $total_pesanan ?></span>
        </a>
        <a href="pesanan.php?filter=pending"  class="filter-tab <?= $filter=='pending'  ? 'active':'' ?>">
            Menunggu DP <span class="badge-count"><?= $stat_count['pending'] ?></span>
        </a>
        <a href="pesanan.php?filter=diproses" class="filter-tab <?= $filter=='diproses' ? 'active':'' ?>">
            Diproses <span class="badge-count"><?= $stat_count['diproses'] ?></span>
        </a>
        <a href="pesanan.php?filter=selesai"  class="filter-tab <?= $filter=='selesai'  ? 'active':'' ?>">
            Selesai <span class="badge-count"><?= $stat_count['selesai'] ?></span>
        </a>
        <a href="pesanan.php?filter=batal"    class="filter-tab <?= $filter=='batal'    ? 'active':'' ?>">
            Dibatalkan <span class="badge-count"><?= $stat_count['batal'] ?></span>
        </a>
    </div>

    <!-- Daftar Pesanan -->
    <?php if ($jml_filtered == 0): ?>
    <div class="empty-state">
        <i class="fas fa-shopping-bag fa-5x d-block mb-3"></i>
        <h4>
            <?= $filter == 'semua'
                ? 'Belum ada pesanan'
                : 'Tidak ada pesanan "' . ($status_config[$filter]['label'] ?? $filter) . '"' ?>
        </h4>
        <p>
            <?= $filter == 'semua'
                ? 'Anda belum pernah melakukan pre-order. Mulai jelajahi katalog buku braille kami.'
                : 'Coba lihat semua pesanan atau filter lainnya.' ?>
        </p>
        <?php if ($filter == 'semua'): ?>
            <a href="../index.php" class="btn-mulai">
                <i class="fas fa-book-open"></i> Jelajahi Katalog
            </a>
        <?php else: ?>
            <a href="pesanan.php" class="btn-mulai">
                <i class="fas fa-list"></i> Lihat Semua Pesanan
            </a>
        <?php endif; ?>
    </div>

    <?php else: ?>

    <?php while ($p = mysqli_fetch_assoc($q_pesanan)):
        $st = $status_config[$p['status']] ?? $status_config['pending'];
    ?>
    <div class="pesanan-card">
        <!-- Header -->
        <div class="pesanan-header">
            <div>
                <div class="order-id">
                    <i class="fas fa-hashtag me-1" style="color:var(--primary-main)"></i>
                    Pesanan #<?= str_pad($p['id'], 5, '0', STR_PAD_LEFT) ?>
                    <?php if ($p['total_item'] > 1): ?>
                        <span class="text-muted fw-normal" style="font-size:.82rem;">(<?= $p['total_item'] ?> item)</span>
                    <?php endif; ?>
                </div>
                <div class="order-date">
                    <i class="fas fa-calendar me-1"></i>
                    <?= date('d M Y, H:i', strtotime($p['created_at'])) ?> WIB
                </div>
            </div>
            <span class="status-badge" style="color:<?= $st['color'] ?>;background:<?= $st['bg'] ?>;border-color:<?= $st['border'] ?>">
                <i class="fas <?= $st['icon'] ?>"></i> <?= $st['label'] ?>
            </span>
        </div>

        <!-- Body -->
        <div class="pesanan-body">
            <div class="buku-thumb">
                <?php if (!empty($p['gambar_buku']) && file_exists('../assets/img/' . $p['gambar_buku'])): ?>
                    <img src="../assets/img/<?= $p['gambar_buku'] ?>" alt="<?= htmlspecialchars($p['judul_buku'] ?? '') ?>">
                <?php else: ?>
                    <i class="fas fa-book"></i>
                <?php endif; ?>
            </div>
            <div class="pesanan-info">
                <div class="judul"><?= htmlspecialchars($p['judul_buku'] ?? 'Buku tidak ditemukan') ?></div>
                <div class="meta">
                    <i class="fas fa-box me-1"></i><?= $p['jumlah'] ?> eksemplar
                    &nbsp;|&nbsp;
                    <i class="fas fa-truck me-1"></i>Ongkir <?= rupiah($p['ongkir']) ?>
                </div>
            </div>
            <div class="pesanan-harga">
                <div class="total"><?= rupiah($p['total_harga']) ?></div>
                <div class="dp-label">DP <?= rupiah($p['dp_nominal']) ?></div>
            </div>
        </div>

        <!-- Footer -->
        <div class="pesanan-footer">
            <div class="estimasi">
                <?php if ($p['estimasi_selesai']): ?>
                    <i class="fas fa-calendar-check"></i>
                    Estimasi: <strong><?= date('d M Y', strtotime($p['estimasi_selesai'])) ?></strong>
                <?php else: ?>
                    <i class="fas fa-info-circle"></i> Estimasi belum ditentukan
                <?php endif; ?>
            </div>
            <a href="detail-pesanan.php?id=<?= $p['id'] ?>" class="btn-lihat-detail">
                <i class="fas fa-eye"></i> Lihat Detail
            </a>
        </div>
    </div>
    <?php endwhile; ?>

    <?php endif; ?>

</div>

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
</body>
</html>