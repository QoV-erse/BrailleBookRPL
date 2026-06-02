<?php
require_once '../../includes/session.php';
require_once '../../includes/config.php';

if ($_SESSION['role'] != 'admin') {
    header("Location: ../../login.php");
    exit;
}

$pesanan_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($pesanan_id == 0) {
    header("Location: index.php");
    exit;
}

// Ambil data pesanan + info user
$q = mysqli_query($conn,
    "SELECT p.*, u.nama_lengkap, u.email, u.username, u.created_at as tgl_daftar
     FROM pesanan p
     JOIN users u ON p.user_id = u.id
     WHERE p.id = $pesanan_id"
);
$pesanan = mysqli_fetch_assoc($q);

if (!$pesanan) {
    header("Location: index.php");
    exit;
}

// Ambil detail item pesanan
$q_detail = mysqli_query($conn,
    "SELECT dp.*, b.judul, b.pengarang, b.kategori, b.ukuran, b.gambar
     FROM detail_pesanan dp
     JOIN buku b ON dp.buku_id = b.id
     WHERE dp.pesanan_id = $pesanan_id"
);

// ============================================================
// PROSES UPDATE STATUS
// ============================================================
$notif = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $status_baru = $_POST['status_baru'];
    $valid_status = ['pending', 'diproses', 'selesai', 'batal'];

    if (in_array($status_baru, $valid_status)) {
        $status_esc = mysqli_real_escape_string($conn, $status_baru);
        if (mysqli_query($conn, "UPDATE pesanan SET status = '$status_esc' WHERE id = $pesanan_id")) {
            // Refresh data pesanan setelah update
            $q2 = mysqli_query($conn,
                "SELECT p.*, u.nama_lengkap, u.email, u.username, u.created_at as tgl_daftar
                 FROM pesanan p JOIN users u ON p.user_id = u.id
                 WHERE p.id = $pesanan_id"
            );
            $pesanan = mysqli_fetch_assoc($q2);
            $notif = 'sukses';
        } else {
            $notif = 'gagal';
        }
    }
}

$status_config = [
    'pending'  => ['label' => 'Menunggu DP',  'color' => '#856404', 'bg' => '#fff3cd', 'border' => '#ffc107', 'icon' => 'fa-clock'],
    'diproses' => ['label' => 'Diproses',      'color' => '#0c63e4', 'bg' => '#cfe2ff', 'border' => '#9ec5fe', 'icon' => 'fa-spinner'],
    'selesai'  => ['label' => 'Selesai',       'color' => '#0f5132', 'bg' => '#d1e7dd', 'border' => '#a3cfbb', 'icon' => 'fa-check-circle'],
    'batal'    => ['label' => 'Dibatalkan',    'color' => '#842029', 'bg' => '#f8d7da', 'border' => '#f1aeb5', 'icon' => 'fa-times-circle'],
];
$st = $status_config[$pesanan['status']] ?? $status_config['pending'];

function rupiah($n) {
    return "Rp " . number_format((float)$n, 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pesanan #<?= str_pad($pesanan_id, 5, '0', STR_PAD_LEFT) ?> - Admin Braille</title>
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

        /* PESANAN HEADER BOX */
        .pesanan-header-box {
            background:linear-gradient(135deg,var(--primary-main),var(--primary-dark));
            border-radius:18px; padding:22px 28px; color:white; margin-bottom:24px;
            display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px;
        }
        .pesanan-header-box .order-id-label { font-size:.88rem; opacity:.8; }
        .pesanan-header-box .order-id-val   { font-size:1.7rem; font-weight:800; }
        .pesanan-header-box .order-date     { font-size:.85rem; opacity:.75; margin-top:4px; }

        /* STATUS BADGE */
        .status-badge { display:inline-flex; align-items:center; gap:6px; padding:8px 18px; border-radius:25px; font-weight:700; font-size:.9rem; border:2px solid; }

        /* CARD */
        .card-custom { background:white; border-radius:16px; padding:24px; box-shadow:0 5px 15px rgba(0,0,0,.06); border:1px solid #eee; margin-bottom:22px; }
        .card-section-title { font-size:.95rem; font-weight:700; color:var(--primary-dark); margin-bottom:16px; padding-bottom:10px; border-bottom:2px solid #f0fdf9; display:flex; align-items:center; gap:8px; }
        .card-section-title i { color:var(--primary-main); }

        /* INFO ROW */
        .info-row { display:flex; padding:10px 0; border-bottom:1px solid #f5f5f5; font-size:.9rem; }
        .info-row:last-child { border-bottom:none; }
        .info-row .lbl { width:42%; font-weight:600; color:#666; display:flex; align-items:flex-start; gap:8px; }
        .info-row .lbl i { color:var(--primary-main); margin-top:2px; width:15px; }
        .info-row .val { flex:1; color:var(--dark-text); }

        /* BUKU ITEM */
        .buku-item { display:flex; align-items:center; gap:16px; padding:14px; border:1.5px solid #e8f5f2; border-radius:12px; background:#f8fffe; margin-bottom:10px; }
        .buku-cover { width:64px; height:64px; border-radius:10px; flex-shrink:0; overflow:hidden; background:linear-gradient(135deg,var(--primary-main),var(--primary-dark)); display:flex; align-items:center; justify-content:center; color:white; font-size:24px; }
        .buku-cover img { width:100%; height:100%; object-fit:cover; }
        .badge-cat { display:inline-block; background:linear-gradient(135deg,var(--primary-main),var(--primary-dark)); color:white; padding:2px 10px; border-radius:20px; font-size:.72rem; font-weight:600; margin-bottom:4px; }
        .buku-info h6 { font-weight:700; font-size:.9rem; margin-bottom:2px; }
        .buku-info small { color:#888; }
        .buku-harga { margin-left:auto; text-align:right; flex-shrink:0; }
        .buku-harga .subtotal { font-size:1rem; font-weight:700; color:var(--primary-dark); }
        .buku-harga small { color:#aaa; display:block; font-size:.78rem; }

        /* SUMMARY */
        .summary-box { background:linear-gradient(135deg,#f0fdf9,#e8f8f5); border:2px solid #c8f0e8; border-radius:14px; padding:18px; }
        .summary-row { display:flex; justify-content:space-between; padding:7px 0; font-size:.9rem; }
        .summary-row .label { color:#555; }
        .summary-row .value { font-weight:600; }
        .summary-divider { border:none; border-top:1.5px dashed #c0e8e0; margin:8px 0; }
        .summary-total { display:flex; justify-content:space-between; padding-top:8px; }
        .summary-total .label { font-size:1rem; font-weight:700; }
        .summary-total .value { font-size:1.2rem; font-weight:800; color:var(--primary-dark); }

        /* DP BOX */
        .dp-box { background:linear-gradient(135deg,#fff8e1,#fff3cd); border:2px solid #ffc107; border-radius:12px; padding:14px 18px; margin-top:14px; }
        .dp-box .dp-title { font-weight:700; color:#856404; margin-bottom:4px; font-size:.88rem; }
        .dp-box .dp-title i { color:var(--accent-gold); margin-right:5px; }
        .dp-box .dp-amount { font-size:1.2rem; font-weight:800; color:#856404; }
        .dp-box small { color:#997a00; display:block; margin-top:3px; font-size:.8rem; }

        /* UPDATE STATUS FORM */
        .status-form { background:#f8fffe; border:2px solid #c8f0e8; border-radius:14px; padding:20px; }
        .status-form h6 { font-weight:700; color:var(--primary-dark); margin-bottom:14px; font-size:.95rem; }
        .status-options { display:flex; flex-direction:column; gap:10px; }
        .status-option { display:flex; align-items:center; gap:12px; padding:12px 16px; border-radius:10px; border:1.5px solid #e0e0e0; cursor:pointer; transition:all .3s; }
        .status-option:hover { border-color:var(--primary-main); background:#f0fdf9; }
        .status-option input[type=radio] { width:18px; height:18px; accent-color:var(--primary-main); flex-shrink:0; }
        .status-option .opt-label { font-weight:600; font-size:.9rem; }
        .status-option .opt-desc  { font-size:.78rem; color:#888; margin-top:1px; }
        .status-option.selected   { border-color:var(--primary-main); background:#f0fdf9; }

        .btn-update { background:linear-gradient(135deg,var(--primary-main),var(--primary-dark)); color:white; border:none; padding:12px 24px; border-radius:10px; font-weight:700; width:100%; margin-top:14px; transition:all .3s; cursor:pointer; font-size:.95rem; display:flex; align-items:center; justify-content:center; gap:8px; }
        .btn-update:hover { transform:translateY(-2px); box-shadow:0 6px 18px rgba(28,206,172,.4); }

        /* TRACKING STEPS */
        .steps { display:flex; flex-direction:column; }
        .step { display:flex; gap:14px; }
        .step-line { display:flex; flex-direction:column; align-items:center; }
        .step-dot { width:14px; height:14px; border-radius:50%; flex-shrink:0; margin-top:3px; }
        .step-connector { width:2px; flex:1; min-height:28px; margin:3px 0; }
        .step-dot.done   { background:var(--primary-main); }
        .step-dot.active { background:var(--accent-gold); border:3px solid #856404; width:16px; height:16px; }
        .step-dot.todo   { background:#e0e0e0; }
        .step-connector.done { background:var(--primary-main); }
        .step-connector.todo { background:#e0e0e0; }
        .step-content { padding-bottom:18px; }
        .step-content .step-title { font-weight:700; font-size:.88rem; }
        .step-content .step-desc  { font-size:.78rem; color:#888; margin-top:2px; }

        /* ALERT */
        .alert-custom { border-radius:12px; padding:13px 18px; margin-bottom:20px; display:flex; align-items:center; gap:10px; font-size:.9rem; }
        .alert-sukses { background:#e8f5e9; color:#2e7d32; border-left:4px solid #4caf50; }
        .alert-gagal  { background:#ffebee; color:#c62828; border-left:4px solid #f44336; }

        /* BTN KEMBALI */
        .btn-kembali { background:white; color:var(--dark-text); border:2px solid #e0e0e0; padding:10px 20px; border-radius:10px; font-weight:600; text-decoration:none; transition:all .3s; display:inline-flex; align-items:center; gap:8px; font-size:.9rem; }
        .btn-kembali:hover { border-color:var(--primary-main); color:var(--primary-dark); }

        /* FOOTER */
        .footer-admin { margin-top:30px; padding-top:20px; border-top:1px solid #e0e0e0; text-align:center; color:#aaa; font-size:.875rem; }

        @media(max-width:768px) {
            .sidebar { transform:translateX(-100%); }
            .main-content { margin-left:0; padding:15px; }
            .buku-harga { display:none; }
            .pesanan-header-box { flex-direction:column; }
        }
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
            <h2>
                <i class="fas fa-file-invoice"></i>
                Detail Pesanan #<?= str_pad($pesanan_id, 5, '0', STR_PAD_LEFT) ?>
            </h2>
            <p class="text-muted mb-0" style="font-size:.9rem;">
                Informasi lengkap dan update status pesanan
            </p>
        </div>
        <div class="d-flex align-items-center gap-3">
            <a href="index.php" class="btn-kembali">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
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
    <div class="alert-custom alert-sukses">
        <i class="fas fa-check-circle fa-lg"></i>
        <span><strong>Berhasil!</strong> Status pesanan berhasil diperbarui menjadi <strong><?= $st['label'] ?></strong>.</span>
    </div>
    <?php elseif ($notif == 'gagal'): ?>
    <div class="alert-custom alert-gagal">
        <i class="fas fa-exclamation-triangle fa-lg"></i>
        <span><strong>Gagal!</strong> Gagal memperbarui status. Silakan coba lagi.</span>
    </div>
    <?php endif; ?>

    <!-- Header Pesanan -->
    <div class="pesanan-header-box">
        <div>
            <div class="order-id-label">Nomor Pesanan</div>
            <div class="order-id-val">#<?= str_pad($pesanan_id, 5, '0', STR_PAD_LEFT) ?></div>
            <div class="order-date">
                <i class="fas fa-calendar me-1"></i>
                <?= date('d F Y, H:i', strtotime($pesanan['created_at'])) ?> WIB
            </div>
        </div>
        <div>
            <span class="status-badge"
                  style="color:<?= $st['color'] ?>;background:<?= $st['bg'] ?>;border-color:<?= $st['border'] ?>">
                <i class="fas <?= $st['icon'] ?>"></i> <?= $st['label'] ?>
            </span>
        </div>
    </div>

    <div class="row g-4">

        <!-- KIRI: Item + Info Pelanggan + Pengiriman -->
        <div class="col-lg-7">

            <!-- Item Dipesan -->
            <div class="card-custom">
                <div class="card-section-title">
                    <i class="fas fa-book"></i> Item yang Dipesan
                </div>
                <?php
                // Re-query detail karena pointer sudah di akhir jika POST
                $q_detail2 = mysqli_query($conn,
                    "SELECT dp.*, b.judul, b.pengarang, b.kategori, b.ukuran, b.gambar
                     FROM detail_pesanan dp
                     JOIN buku b ON dp.buku_id = b.id
                     WHERE dp.pesanan_id = $pesanan_id"
                );
                while ($item = mysqli_fetch_assoc($q_detail2)):
                ?>
                <div class="buku-item">
                    <div class="buku-cover">
                        <?php if (!empty($item['gambar']) && file_exists('../../assets/img/' . $item['gambar'])): ?>
                            <img src="../../assets/img/<?= $item['gambar'] ?>"
                                 alt="<?= htmlspecialchars($item['judul']) ?>">
                        <?php else: ?>
                            <i class="fas fa-book"></i>
                        <?php endif; ?>
                    </div>
                    <div class="buku-info">
                        <span class="badge-cat"><?= $item['kategori'] ?></span>
                        <h6><?= htmlspecialchars($item['judul']) ?></h6>
                        <small>
                            <i class="fas fa-user me-1"></i><?= htmlspecialchars($item['pengarang']) ?>
                            &nbsp;|&nbsp;
                            <?= $item['ukuran'] == 'Besar' ? '25.5×30.5 cm' : '1.5×25.5 cm' ?>
                        </small><br>
                        <small class="text-muted">
                            <?= rupiah($item['harga_satuan']) ?> × <?= $item['jumlah'] ?> eks
                        </small>
                    </div>
                    <div class="buku-harga">
                        <div class="subtotal"><?= rupiah($item['subtotal']) ?></div>
                        <small><?= $item['jumlah'] ?> eksemplar</small>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>

            <!-- Info Pelanggan -->
            <div class="card-custom">
                <div class="card-section-title">
                    <i class="fas fa-user"></i> Informasi Pelanggan
                </div>
                <div class="info-row">
                    <div class="lbl"><i class="fas fa-user"></i> Nama</div>
                    <div class="val"><strong><?= htmlspecialchars($pesanan['nama_lengkap']) ?></strong></div>
                </div>
                <div class="info-row">
                    <div class="lbl"><i class="fas fa-at"></i> Username</div>
                    <div class="val">@<?= htmlspecialchars($pesanan['username']) ?></div>
                </div>
                <div class="info-row">
                    <div class="lbl"><i class="fas fa-envelope"></i> Email</div>
                    <div class="val"><?= htmlspecialchars($pesanan['email']) ?></div>
                </div>
                <div class="info-row">
                    <div class="lbl"><i class="fas fa-calendar"></i> Terdaftar</div>
                    <div class="val"><?= date('d M Y', strtotime($pesanan['tgl_daftar'])) ?></div>
                </div>
            </div>

            <!-- Info Pengiriman -->
            <div class="card-custom">
                <div class="card-section-title">
                    <i class="fas fa-map-marker-alt"></i> Informasi Pengiriman
                </div>
                <div class="info-row">
                    <div class="lbl"><i class="fas fa-map-marker-alt"></i> Alamat</div>
                    <div class="val"><?= nl2br(htmlspecialchars($pesanan['alamat'])) ?></div>
                </div>
                <div class="info-row">
                    <div class="lbl"><i class="fas fa-calendar-check"></i> Estimasi</div>
                    <div class="val">
                        <?php if ($pesanan['estimasi_selesai']): ?>
                            <strong><?= date('d M Y', strtotime($pesanan['estimasi_selesai'])) ?></strong>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if (!empty($pesanan['catatan'])): ?>
                <div class="info-row">
                    <div class="lbl"><i class="fas fa-sticky-note"></i> Catatan</div>
                    <div class="val"><?= htmlspecialchars($pesanan['catatan']) ?></div>
                </div>
                <?php endif; ?>
            </div>

        </div><!-- /kiri -->

        <!-- KANAN: Pembayaran + Update Status + Tracking -->
        <div class="col-lg-5">

            <!-- Ringkasan Pembayaran -->
            <div class="card-custom">
                <div class="card-section-title">
                    <i class="fas fa-receipt"></i> Ringkasan Pembayaran
                </div>
                <div class="summary-box">
                    <div class="summary-row">
                        <span class="label">Subtotal barang</span>
                        <span class="value"><?= rupiah($pesanan['total_harga'] - $pesanan['ongkir']) ?></span>
                    </div>
                    <div class="summary-row">
                        <span class="label">Ongkos kirim</span>
                        <span class="value"><?= rupiah($pesanan['ongkir']) ?></span>
                    </div>
                    <hr class="summary-divider">
                    <div class="summary-total">
                        <span class="label">Total</span>
                        <span class="value"><?= rupiah($pesanan['total_harga']) ?></span>
                    </div>
                </div>

                <div class="dp-box">
                    <div class="dp-title">
                        <i class="fas fa-hand-holding-usd"></i> Down Payment (DP 50%)
                    </div>
                    <div class="dp-amount"><?= rupiah($pesanan['dp_nominal']) ?></div>
                    <small>
                        Sisa <?= rupiah($pesanan['total_harga'] - $pesanan['dp_nominal']) ?>
                        dibayar saat barang tiba.
                    </small>
                </div>
            </div>

            <!-- Update Status -->
            <div class="card-custom">
                <div class="card-section-title">
                    <i class="fas fa-edit"></i> Update Status Pesanan
                </div>
                <form method="POST">
                    <div class="status-form">
                        <h6><i class="fas fa-exchange-alt me-2" style="color:var(--primary-main)"></i>Pilih Status Baru</h6>
                        <div class="status-options">
                            <?php
                            $opts = [
                                'pending'  => ['Menunggu DP',       'Menunggu konfirmasi pembayaran DP dari pelanggan'],
                                'diproses' => ['Sedang Diproses',   'DP dikonfirmasi, buku sedang dicetak'],
                                'selesai'  => ['Selesai',           'Buku selesai dicetak dan siap dikirim'],
                                'batal'    => ['Dibatalkan',        'Pesanan dibatalkan'],
                            ];
                            foreach ($opts as $val => $opt):
                                $checked  = $pesanan['status'] == $val ? 'checked' : '';
                                $selected = $pesanan['status'] == $val ? 'selected' : '';
                            ?>
                            <label class="status-option <?= $selected ?>">
                                <input type="radio" name="status_baru" value="<?= $val ?>" <?= $checked ?>
                                       onchange="this.closest('.status-options').querySelectorAll('.status-option').forEach(el=>el.classList.remove('selected')); this.closest('.status-option').classList.add('selected')">
                                <div>
                                    <div class="opt-label"><?= $opt[0] ?></div>
                                    <div class="opt-desc"><?= $opt[1] ?></div>
                                </div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <button type="submit" name="update_status" class="btn-update">
                        <i class="fas fa-save"></i> Simpan Perubahan Status
                    </button>
                </form>
            </div>

            <!-- Tracking Steps -->
            <div class="card-custom">
                <div class="card-section-title">
                    <i class="fas fa-route"></i> Tracking Pesanan
                </div>
                <?php
                $urutan = ['pending', 'diproses', 'selesai'];
                $cur_idx = array_search($pesanan['status'], $urutan);
                if ($cur_idx === false) $cur_idx = -1;
                $steps = [
                    ['Pesanan Dibuat',            'Pesanan diterima sistem'],
                    ['DP Dikonfirmasi & Diproses', 'Buku sedang dicetak oleh tim'],
                    ['Selesai & Siap Kirim',       'Buku selesai, siap dikirim'],
                ];
                ?>
                <div class="steps">
                    <?php foreach ($steps as $i => $step):
                        if ($pesanan['status'] == 'batal') {
                            $dot = $conn_class = 'todo';
                        } elseif ($i < $cur_idx) {
                            $dot = 'done'; $conn_class = 'done';
                        } elseif ($i == $cur_idx) {
                            $dot = 'active'; $conn_class = 'todo';
                        } else {
                            $dot = $conn_class = 'todo';
                        }
                    ?>
                    <div class="step">
                        <div class="step-line">
                            <div class="step-dot <?= $dot ?>"></div>
                            <?php if ($i < count($steps) - 1): ?>
                            <div class="step-connector <?= $conn_class ?>"></div>
                            <?php endif; ?>
                        </div>
                        <div class="step-content">
                            <div class="step-title"><?= $step[0] ?></div>
                            <div class="step-desc"><?= $step[1] ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <?php if ($pesanan['status'] == 'batal'): ?>
                    <div class="step">
                        <div class="step-line">
                            <div class="step-dot" style="background:#e74c3c"></div>
                        </div>
                        <div class="step-content">
                            <div class="step-title" style="color:#e74c3c">Pesanan Dibatalkan</div>
                            <div class="step-desc">Pesanan ini telah dibatalkan</div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </div><!-- /kanan -->

    </div>

    <div class="footer-admin">
        <i class="fas fa-book-open me-2" style="color:var(--primary-main)"></i>
        © 2026 Yayasan Raudlatul Makfufin — Sistem Informasi Katalog Buku Braille
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>