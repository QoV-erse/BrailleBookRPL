<?php
session_start();
require_once '../includes/config.php';

if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit; }
if ($_SESSION['role'] == 'admin') { header("Location: ../admin/dashboard.php"); exit; }

$user_id     = $_SESSION['user_id'];
$pesanan_id  = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$status_baru = isset($_GET['status']) && $_GET['status'] == 'baru';

$q_pesanan = mysqli_query($conn,
    "SELECT * FROM pesanan WHERE id = $pesanan_id AND user_id = $user_id"
);
$pesanan = mysqli_fetch_assoc($q_pesanan);
if (!$pesanan) { header("Location: pesanan.php"); exit; }

$q_detail = mysqli_query($conn,
    "SELECT dp.*, b.judul, b.pengarang, b.kategori, b.ukuran, b.gambar
     FROM detail_pesanan dp
     JOIN buku b ON dp.buku_id = b.id
     WHERE dp.pesanan_id = $pesanan_id"
);

// Cek apakah sudah ada konfirmasi bayar
$q_konfirmasi = mysqli_query($conn,
    "SELECT * FROM konfirmasi_bayar WHERE pesanan_id = $pesanan_id ORDER BY created_at DESC LIMIT 1"
);
$konfirmasi = mysqli_fetch_assoc($q_konfirmasi);

$status_config = [
    'pending'  => ['label'=>'Menunggu Konfirmasi DP', 'color'=>'#856404','bg'=>'#fff3cd','border'=>'#ffc107','icon'=>'fa-clock'],
    'diproses' => ['label'=>'Sedang Diproses',        'color'=>'#0c63e4','bg'=>'#cfe2ff','border'=>'#9ec5fe','icon'=>'fa-spinner'],
    'selesai'  => ['label'=>'Selesai',                'color'=>'#0f5132','bg'=>'#d1e7dd','border'=>'#a3cfbb','icon'=>'fa-check-circle'],
    'batal'    => ['label'=>'Dibatalkan',             'color'=>'#842029','bg'=>'#f8d7da','border'=>'#f1aeb5','icon'=>'fa-times-circle'],
];
$st = $status_config[$pesanan['status']] ?? $status_config['pending'];

// ============================================================
// PROSES KONFIRMASI PEMBAYARAN
// ============================================================
$notif_bayar = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['konfirmasi_bayar'])) {
    $nama_pengirim = mysqli_real_escape_string($conn, trim($_POST['nama_pengirim']));
    $jumlah_bayar  = (float)$_POST['jumlah_bayar'];
    $tgl_transfer  = mysqli_real_escape_string($conn, $_POST['tgl_transfer']);
    $bank_asal     = mysqli_real_escape_string($conn, trim($_POST['bank_asal']));
    $catatan       = mysqli_real_escape_string($conn, trim($_POST['bukti_catatan'] ?? ''));

    if (empty($nama_pengirim) || $jumlah_bayar <= 0 || empty($tgl_transfer)) {
        $notif_bayar = 'error';
    } else {
        // Hapus konfirmasi lama jika ada yang masih menunggu
        mysqli_query($conn,
            "DELETE FROM konfirmasi_bayar
             WHERE pesanan_id = $pesanan_id AND status = 'menunggu'"
        );

        $ins = "INSERT INTO konfirmasi_bayar
                    (pesanan_id, user_id, nama_pengirim, jumlah_bayar, tgl_transfer, bank_asal, bukti_catatan)
                VALUES
                    ($pesanan_id, $user_id, '$nama_pengirim', $jumlah_bayar, '$tgl_transfer', '$bank_asal', '$catatan')";

        if (mysqli_query($conn, $ins)) {
            $notif_bayar = 'sukses';
            // Refresh data konfirmasi
            $q_konfirmasi = mysqli_query($conn,
                "SELECT * FROM konfirmasi_bayar WHERE pesanan_id = $pesanan_id ORDER BY created_at DESC LIMIT 1"
            );
            $konfirmasi = mysqli_fetch_assoc($q_konfirmasi);
        } else {
            $notif_bayar = 'error';
        }
    }
}

function rupiah($n) { return "Rp " . number_format((float)$n, 0, ',', '.'); }

// Info rekening BCA yayasan (sesuaikan dengan rekening asli)
$REKENING_BCA = [
    'nomor'  => '1234567890',
    'nama'   => 'Yayasan Raudlatul Makfufin',
    'cabang' => 'KCP Ciputat',
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pesanan #<?= $pesanan_id ?> - Braille Book Catalog</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary-main:#1cceac; --primary-dark:#15a38a; --accent-gold:#FFD700; --accent-yellow:#FFC107; --light-bg:#f8f9fa; --dark-text:#2c3e50; }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',sans-serif; font-size:16px; background:var(--light-bg); color:var(--dark-text); }

        .navbar { background:linear-gradient(135deg,var(--primary-main),var(--primary-dark))!important; box-shadow:0 4px 15px rgba(28,206,172,.2); padding:15px 0; position:sticky; top:0; z-index:999; }
        .navbar-brand { font-size:1.4rem; font-weight:bold; color:white!important; }
        .navbar-brand i { color:var(--accent-gold); margin-right:8px; }
        .navbar-nav .nav-link { color:rgba(255,255,255,.9)!important; font-weight:500; padding:8px 16px!important; transition:all .3s; }
        .navbar-nav .nav-link:hover, .navbar-nav .nav-link.active { color:var(--accent-gold)!important; background:rgba(255,255,255,.15); border-radius:8px; }
        .user-badge { background:rgba(255,255,255,.2); padding:7px 15px; border-radius:50px; color:white; font-size:.95rem; display:flex; align-items:center; gap:6px; }
        .user-badge i { color:var(--accent-gold); }

        .alert-baru { background:linear-gradient(135deg,#d1e7dd,#a3cfbb); border:2px solid #0f5132; border-radius:16px; padding:20px 24px; margin-bottom:28px; display:flex; align-items:center; gap:16px; }
        .alert-baru i { color:#0f5132; font-size:2rem; flex-shrink:0; }
        .alert-baru h5 { color:#0f5132; font-weight:700; margin-bottom:4px; }
        .alert-baru p { color:#0a3622; margin:0; font-size:.95rem; }

        .pesanan-id-box { background:linear-gradient(135deg,var(--primary-main),var(--primary-dark)); border-radius:16px; padding:20px 24px; color:white; margin-bottom:24px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; }
        .pesanan-id-box .id-label { font-size:.9rem; opacity:.85; }
        .pesanan-id-box .id-value { font-size:1.5rem; font-weight:800; }
        .pesanan-id-box .tgl { font-size:.85rem; opacity:.8; }

        .card-custom { background:white; border-radius:20px; padding:28px; box-shadow:0 8px 25px rgba(0,0,0,.07); border:1px solid #eee; margin-bottom:24px; }
        .card-section-title { font-size:1rem; font-weight:700; color:var(--primary-dark); margin-bottom:18px; padding-bottom:10px; border-bottom:2px solid #f0fdf9; display:flex; align-items:center; gap:8px; }
        .card-section-title i { color:var(--primary-main); }

        .status-badge { display:inline-flex; align-items:center; gap:6px; padding:8px 18px; border-radius:25px; font-weight:700; font-size:.9rem; border:2px solid; }

        .buku-item { display:flex; align-items:center; gap:16px; padding:16px; border:1.5px solid #e8f5f2; border-radius:14px; background:#f8fffe; margin-bottom:12px; }
        .buku-cover { width:64px; height:64px; border-radius:10px; flex-shrink:0; overflow:hidden; background:linear-gradient(135deg,var(--primary-main),var(--primary-dark)); display:flex; align-items:center; justify-content:center; color:white; font-size:24px; }
        .buku-cover img { width:100%; height:100%; object-fit:cover; }
        .badge-cat { display:inline-block; background:linear-gradient(135deg,var(--primary-main),var(--primary-dark)); color:white; padding:2px 10px; border-radius:20px; font-size:.72rem; font-weight:600; margin-bottom:4px; }
        .buku-detail h6 { font-weight:700; font-size:.9rem; margin-bottom:2px; }
        .buku-detail small { color:#888; }
        .buku-harga { margin-left:auto; text-align:right; flex-shrink:0; }
        .buku-harga .subtotal { font-size:1rem; font-weight:700; color:var(--primary-dark); }
        .buku-harga small { color:#aaa; display:block; font-size:.78rem; }

        .info-row { display:flex; padding:10px 0; border-bottom:1px solid #f5f5f5; font-size:.9rem; }
        .info-row:last-child { border-bottom:none; }
        .info-row .lbl { width:45%; font-weight:600; color:#555; display:flex; align-items:flex-start; gap:8px; }
        .info-row .lbl i { color:var(--primary-main); margin-top:2px; width:16px; }
        .info-row .val { flex:1; color:var(--dark-text); }

        .summary-box { background:linear-gradient(135deg,#f0fdf9,#e8f8f5); border:2px solid #c8f0e8; border-radius:16px; padding:20px; }
        .summary-row { display:flex; justify-content:space-between; align-items:center; padding:8px 0; font-size:.95rem; }
        .summary-row .label { color:#555; }
        .summary-row .value { font-weight:600; }
        .summary-divider { border:none; border-top:1.5px dashed #c0e8e0; margin:8px 0; }
        .summary-total { display:flex; justify-content:space-between; align-items:center; padding-top:10px; }
        .summary-total .label { font-size:1.05rem; font-weight:700; }
        .summary-total .value { font-size:1.3rem; font-weight:800; color:var(--primary-dark); }

        .dp-box { background:linear-gradient(135deg,#fff8e1,#fff3cd); border:2px solid var(--accent-yellow); border-radius:14px; padding:16px 20px; margin-top:16px; }
        .dp-box .dp-title { font-weight:700; color:#856404; margin-bottom:6px; font-size:.9rem; }
        .dp-box .dp-title i { color:var(--accent-gold); margin-right:5px; }
        .dp-box .dp-amount { font-size:1.3rem; font-weight:800; color:#856404; }
        .dp-box small { color:#997a00; display:block; margin-top:4px; font-size:.85rem; }

        /* ===== BCA PAYMENT BOX ===== */
        .bca-box {
            background:linear-gradient(135deg,#003d82,#005bb5);
            border-radius:16px; padding:20px 24px; margin-top:16px; color:white;
        }
        .bca-box .bca-header { display:flex; align-items:center; gap:12px; margin-bottom:16px; }
        .bca-logo { background:white; border-radius:8px; padding:6px 12px; font-weight:900; font-size:1rem; color:#003d82; letter-spacing:1px; }
        .bca-box h6 { font-weight:700; margin:0; font-size:.95rem; }
        .bca-rekening { background:rgba(255,255,255,.12); border-radius:10px; padding:14px 16px; margin-bottom:12px; }
        .bca-rekening .label { font-size:.78rem; opacity:.75; margin-bottom:2px; }
        .bca-rekening .value { font-size:1.05rem; font-weight:700; letter-spacing:1px; }
        .bca-rekening .value.nomor { font-size:1.3rem; letter-spacing:3px; }
        .btn-copy { background:rgba(255,255,255,.2); border:1px solid rgba(255,255,255,.4); color:white; padding:4px 12px; border-radius:8px; font-size:.78rem; cursor:pointer; transition:all .2s; margin-left:8px; }
        .btn-copy:hover { background:rgba(255,255,255,.3); }
        .bca-note { font-size:.82rem; opacity:.85; margin-top:8px; }
        .bca-total-dp { background:rgba(255,215,0,.2); border:1.5px solid rgba(255,215,0,.5); border-radius:10px; padding:12px 16px; text-align:center; margin-bottom:14px; }
        .bca-total-dp .label-dp { font-size:.82rem; opacity:.85; }
        .bca-total-dp .amount-dp { font-size:1.4rem; font-weight:800; color:var(--accent-gold); }

        /* ===== FORM KONFIRMASI ===== */
        .konfirmasi-box { background:#f8fffe; border:2px solid #c8f0e8; border-radius:16px; padding:20px; margin-top:16px; }
        .konfirmasi-box .form-label { font-weight:600; font-size:.88rem; margin-bottom:4px; }
        .konfirmasi-box .form-control, .konfirmasi-box .form-select {
            border:1.5px solid #e0e0e0; border-radius:10px; padding:9px 12px; font-size:.9rem; transition:all .3s;
        }
        .konfirmasi-box .form-control:focus, .konfirmasi-box .form-select:focus {
            border-color:var(--primary-main); box-shadow:0 0 0 3px rgba(28,206,172,.15);
        }
        .btn-konfirmasi { background:linear-gradient(135deg,var(--primary-main),var(--primary-dark)); color:white; border:none; padding:12px; border-radius:12px; font-weight:700; width:100%; transition:all .3s; cursor:pointer; font-size:.95rem; display:flex; align-items:center; justify-content:center; gap:8px; margin-top:14px; }
        .btn-konfirmasi:hover { transform:translateY(-2px); box-shadow:0 6px 18px rgba(28,206,172,.4); color:white; }

        /* Status konfirmasi */
        .konfirmasi-status { border-radius:12px; padding:14px 16px; margin-top:14px; display:flex; align-items:center; gap:10px; font-size:.9rem; }
        .konfirmasi-menunggu { background:#fff3cd; border:1.5px solid #ffc107; color:#856404; }
        .konfirmasi-dikonfirmasi { background:#d1e7dd; border:1.5px solid #a3cfbb; color:#0f5132; }
        .konfirmasi-ditolak { background:#f8d7da; border:1.5px solid #f1aeb5; color:#842029; }

        /* TRACKING STEPS */
        .steps { display:flex; flex-direction:column; }
        .step { display:flex; gap:16px; }
        .step-line { display:flex; flex-direction:column; align-items:center; }
        .step-dot { width:14px; height:14px; border-radius:50%; flex-shrink:0; margin-top:4px; }
        .step-connector { width:2px; flex:1; min-height:30px; margin:4px 0; }
        .step-dot.done   { background:var(--primary-main); }
        .step-dot.active { background:var(--accent-gold); border:3px solid #856404; width:16px; height:16px; }
        .step-dot.todo   { background:#e0e0e0; }
        .step-connector.done { background:var(--primary-main); }
        .step-connector.todo { background:#e0e0e0; }
        .step-content { padding-bottom:20px; }
        .step-content .step-title { font-weight:700; font-size:.9rem; }
        .step-content .step-desc  { font-size:.82rem; color:#888; margin-top:2px; }

        .btn-primary-custom { background:linear-gradient(135deg,var(--primary-main),var(--primary-dark)); color:white; border:none; padding:12px 24px; border-radius:12px; font-weight:600; transition:all .3s; text-decoration:none; display:inline-flex; align-items:center; gap:8px; }
        .btn-primary-custom:hover { transform:translateY(-2px); box-shadow:0 6px 18px rgba(28,206,172,.4); color:white; }
        .btn-outline-custom { background:white; color:var(--primary-dark); border:2px solid var(--primary-main); padding:12px 24px; border-radius:12px; font-weight:600; transition:all .3s; text-decoration:none; display:inline-flex; align-items:center; gap:8px; }
        .btn-outline-custom:hover { background:var(--primary-main); color:white; }

        .footer { background:linear-gradient(135deg,var(--primary-main) 0%,var(--primary-dark) 100%); color:white; padding:40px 0; margin-top:50px; }
        .footer h5 { color:var(--accent-gold); margin-bottom:15px; }
        .footer a { color:rgba(255,255,255,.9); text-decoration:none; transition:color .3s; }
        .footer a:hover { color:var(--accent-gold); }
        .footer .social-links a { display:inline-block; width:40px; height:40px; background:rgba(255,255,255,.1); border-radius:50%; text-align:center; line-height:40px; margin-right:10px; transition:all .3s; }
        .footer .social-links a:hover { background:var(--accent-gold); color:var(--primary-dark); transform:translateY(-3px); }

        @media(max-width:768px) { .buku-harga{display:none;} .card-custom{padding:18px;} .pesanan-id-box{flex-direction:column;} }
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
            <ul class="navbar-nav mx-auto">
                <li class="nav-item"><a class="nav-link" href="../index.php"><i class="fas fa-home"></i> KATALOG</a></li>
                <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="fas fa-th-large"></i> DASHBOARD</a></li>
                <li class="nav-item"><a class="nav-link active" href="pesanan.php"><i class="fas fa-shopping-bag"></i> PESANAN</a></li>
                <li class="nav-item"><a class="nav-link" href="profil.php"><i class="fas fa-user-circle"></i> PROFIL</a></li>
            </ul>
            <div class="user-badge">
                <i class="fas fa-user-circle"></i>
                <?= htmlspecialchars($_SESSION['nama_lengkap']) ?>
                <a href="../logout.php" class="btn btn-sm btn-outline-light ms-2">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </div>
</nav>

<!-- MAIN -->
<div class="container py-5">

    <!-- Alert pesanan baru -->
    <?php if ($status_baru): ?>
    <div class="alert-baru">
        <i class="fas fa-check-circle"></i>
        <div>
            <h5>Pesanan Berhasil Dibuat!</h5>
            <p>Pesanan Anda telah diterima. Silakan lakukan pembayaran DP sebesar
               <strong><?= rupiah($pesanan['dp_nominal']) ?></strong> ke rekening BCA di bawah
               dan konfirmasikan pembayaran Anda.</p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Alert konfirmasi bayar -->
    <?php if ($notif_bayar === 'sukses'): ?>
    <div class="alert alert-success border-0" style="border-radius:12px;">
        <i class="fas fa-check-circle me-2"></i>
        <strong>Konfirmasi pembayaran berhasil dikirim!</strong>
        Admin akan memverifikasi transfer Anda dalam 1×24 jam.
    </div>
    <?php elseif ($notif_bayar === 'error'): ?>
    <div class="alert alert-danger border-0" style="border-radius:12px;">
        <i class="fas fa-exclamation-circle me-2"></i>
        Gagal mengirim konfirmasi. Pastikan semua data terisi dengan benar.
    </div>
    <?php endif; ?>

    <!-- Header Pesanan -->
    <div class="pesanan-id-box">
        <div>
            <div class="id-label">Nomor Pesanan</div>
            <div class="id-value">#<?= str_pad($pesanan_id, 5, '0', STR_PAD_LEFT) ?></div>
        </div>
        <div class="text-end">
            <div class="tgl">
                <i class="fas fa-calendar me-1"></i>
                <?= date('d M Y, H:i', strtotime($pesanan['created_at'])) ?> WIB
            </div>
            <div class="mt-2">
                <span class="status-badge" style="color:<?= $st['color'] ?>;background:<?= $st['bg'] ?>;border-color:<?= $st['border'] ?>">
                    <i class="fas <?= $st['icon'] ?>"></i> <?= $st['label'] ?>
                </span>
            </div>
        </div>
    </div>

    <div class="row g-4">

        <!-- KIRI -->
        <div class="col-lg-7">

            <!-- Item Dipesan -->
            <div class="card-custom">
                <div class="card-section-title"><i class="fas fa-book"></i> Item yang Dipesan</div>
                <?php while ($item = mysqli_fetch_assoc($q_detail)): ?>
                <div class="buku-item">
                    <div class="buku-cover">
                        <?php if (!empty($item['gambar']) && file_exists('../assets/img/' . $item['gambar'])): ?>
                            <img src="../assets/img/<?= $item['gambar'] ?>" alt="<?= htmlspecialchars($item['judul']) ?>">
                        <?php else: ?><i class="fas fa-book"></i><?php endif; ?>
                    </div>
                    <div class="buku-detail">
                        <span class="badge-cat"><?= $item['kategori'] ?></span>
                        <h6><?= htmlspecialchars($item['judul']) ?></h6>
                        <small><i class="fas fa-user me-1"></i><?= htmlspecialchars($item['pengarang']) ?>
                            &nbsp;|&nbsp;<?= $item['ukuran'] == 'Besar' ? '25.5×30.5 cm' : '1.5×25.5 cm' ?></small><br>
                        <small class="text-muted"><?= rupiah($item['harga_satuan']) ?> × <?= $item['jumlah'] ?> eks</small>
                    </div>
                    <div class="buku-harga">
                        <div class="subtotal"><?= rupiah($item['subtotal']) ?></div>
                        <small><?= $item['jumlah'] ?> eksemplar</small>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>

            <!-- Info Pengiriman -->
            <div class="card-custom">
                <div class="card-section-title"><i class="fas fa-map-marker-alt"></i> Informasi Pengiriman</div>
                <div class="info-row">
                    <div class="lbl"><i class="fas fa-user"></i> Penerima</div>
                    <div class="val"><?= htmlspecialchars($_SESSION['nama_lengkap']) ?></div>
                </div>
                <div class="info-row">
                    <div class="lbl"><i class="fas fa-map-marker-alt"></i> Alamat</div>
                    <div class="val"><?= nl2br(htmlspecialchars($pesanan['alamat'])) ?></div>
                </div>
                <?php if (!empty($pesanan['catatan'])): ?>
                <div class="info-row">
                    <div class="lbl"><i class="fas fa-sticky-note"></i> Catatan</div>
                    <div class="val"><?= htmlspecialchars($pesanan['catatan']) ?></div>
                </div>
                <?php endif; ?>
                <div class="info-row">
                    <div class="lbl"><i class="fas fa-calendar-check"></i> Estimasi</div>
                    <div class="val">
                        <?php if ($pesanan['estimasi_selesai']): ?>
                            <strong><?= date('d M Y', strtotime($pesanan['estimasi_selesai'])) ?></strong>
                            <small class="text-muted ms-1">(setelah DP dikonfirmasi)</small>
                        <?php else: ?><span class="text-muted">-</span><?php endif; ?>
                    </div>
                </div>
            </div>

        </div><!-- /kiri -->

        <!-- KANAN -->
        <div class="col-lg-5">

            <!-- Ringkasan Pembayaran -->
            <div class="card-custom">
                <div class="card-section-title"><i class="fas fa-receipt"></i> Ringkasan Pembayaran</div>
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

                <!-- DP Box -->
                <div class="dp-box">
                    <div class="dp-title"><i class="fas fa-hand-holding-usd"></i> Down Payment (DP 50%)</div>
                    <div class="dp-amount"><?= rupiah($pesanan['dp_nominal']) ?></div>
                    <small>Sisa <?= rupiah($pesanan['total_harga'] - $pesanan['dp_nominal']) ?> dibayar saat barang tiba.</small>
                </div>

                <!-- ===== BCA PAYMENT BOX (tampil jika status pending) ===== -->
                <?php if ($pesanan['status'] === 'pending'): ?>
                <div class="bca-box">
                    <div class="bca-header">
                        <div class="bca-logo">BCA</div>
                        <div>
                            <h6>Pembayaran via Transfer BCA</h6>
                            <small style="opacity:.8;font-size:.8rem;">Bayarkan DP sesuai nominal di bawah</small>
                        </div>
                    </div>

                    <!-- Nominal DP -->
                    <div class="bca-total-dp">
                        <div class="label-dp">Nominal yang harus ditransfer (DP 50%)</div>
                        <div class="amount-dp"><?= rupiah($pesanan['dp_nominal']) ?></div>
                    </div>

                    <!-- Info Rekening -->
                    <div class="bca-rekening">
                        <div class="label">Nomor Rekening</div>
                        <div class="value nomor">
                            <?= $REKENING_BCA['nomor'] ?>
                            <button class="btn-copy" onclick="copyRekening('<?= $REKENING_BCA['nomor'] ?>')">
                                <i class="fas fa-copy"></i> Salin
                            </button>
                        </div>
                    </div>
                    <div class="bca-rekening">
                        <div class="label">Atas Nama</div>
                        <div class="value"><?= $REKENING_BCA['nama'] ?></div>
                    </div>
                    <div class="bca-rekening">
                        <div class="label">Cabang</div>
                        <div class="value"><?= $REKENING_BCA['cabang'] ?></div>
                    </div>

                    <div class="bca-note">
                        <i class="fas fa-info-circle me-1"></i>
                        Gunakan nomor pesanan <strong>#<?= str_pad($pesanan_id, 5, '0', STR_PAD_LEFT) ?></strong>
                        sebagai berita transfer / keterangan.
                    </div>
                </div>

                <!-- Form Konfirmasi Transfer -->
                <?php if (!$konfirmasi || $konfirmasi['status'] === 'ditolak'): ?>
                <div class="konfirmasi-box">
                    <p style="font-weight:700;color:var(--primary-dark);margin-bottom:14px;font-size:.95rem;">
                        <i class="fas fa-check-circle me-2" style="color:var(--primary-main)"></i>
                        Sudah Transfer? Konfirmasikan di sini
                    </p>
                    <?php if ($konfirmasi && $konfirmasi['status'] === 'ditolak'): ?>
                    <div class="alert alert-danger border-0 mb-3" style="border-radius:10px;font-size:.85rem;">
                        <i class="fas fa-times-circle me-1"></i>
                        Konfirmasi sebelumnya ditolak admin. Silakan kirim ulang dengan data yang benar.
                    </div>
                    <?php endif; ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Nama Pengirim Transfer *</label>
                            <input type="text" name="nama_pengirim" class="form-control"
                                   placeholder="Nama sesuai rekening pengirim" required
                                   value="<?= htmlspecialchars($_SESSION['nama_lengkap']) ?>">
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-7">
                                <label class="form-label">Jumlah yang Ditransfer *</label>
                                <div class="input-group">
                                    <span class="input-group-text" style="font-size:.85rem;">Rp</span>
                                    <input type="number" name="jumlah_bayar" class="form-control"
                                           placeholder="<?= $pesanan['dp_nominal'] ?>"
                                           value="<?= $pesanan['dp_nominal'] ?>" required min="1">
                                </div>
                            </div>
                            <div class="col-5">
                                <label class="form-label">Tanggal Transfer *</label>
                                <input type="date" name="tgl_transfer" class="form-control"
                                       value="<?= date('Y-m-d') ?>" required
                                       max="<?= date('Y-m-d') ?>">
                            </div>
                        </div>
                        <div class="mb-1">
                            <label class="form-label">Bank Asal Transfer</label>
                            <select name="bank_asal" class="form-select">
                                <option value="BCA">BCA</option>
                                <option value="BRI">BRI</option>
                                <option value="BNI">BNI</option>
                                <option value="Mandiri">Mandiri</option>
                                <option value="BSI">BSI (Bank Syariah Indonesia)</option>
                                <option value="CIMB">CIMB Niaga</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>
                        <div class="mb-1 mt-3">
                            <label class="form-label">Catatan Tambahan (Opsional)</label>
                            <textarea name="bukti_catatan" class="form-control" rows="2"
                                placeholder="Contoh: Transfer dari rekening a.n. Budi Santoso, nomor referensi 123456"></textarea>
                        </div>
                        <button type="submit" name="konfirmasi_bayar" class="btn-konfirmasi">
                            <i class="fas fa-paper-plane"></i> Kirim Konfirmasi Pembayaran
                        </button>
                    </form>
                </div>

                <?php elseif ($konfirmasi && $konfirmasi['status'] === 'menunggu'): ?>
                <!-- Sudah konfirmasi, menunggu verifikasi admin -->
                <div class="konfirmasi-status konfirmasi-menunggu">
                    <i class="fas fa-clock fa-lg"></i>
                    <div>
                        <strong>Konfirmasi pembayaran terkirim</strong><br>
                        <small>
                            Dikirim <?= date('d M Y, H:i', strtotime($konfirmasi['created_at'])) ?> WIB —
                            Nominal: <strong><?= rupiah($konfirmasi['jumlah_bayar']) ?></strong>
                            dari <?= htmlspecialchars($konfirmasi['bank_asal']) ?>
                            a.n. <?= htmlspecialchars($konfirmasi['nama_pengirim']) ?><br>
                            Admin akan memverifikasi dalam 1×24 jam.
                        </small>
                    </div>
                </div>

                <?php elseif ($konfirmasi && $konfirmasi['status'] === 'dikonfirmasi'): ?>
                <div class="konfirmasi-status konfirmasi-dikonfirmasi">
                    <i class="fas fa-check-circle fa-lg"></i>
                    <div>
                        <strong>Pembayaran DP telah dikonfirmasi!</strong><br>
                        <small>Pesanan Anda sedang dalam proses cetak.</small>
                    </div>
                </div>
                <?php endif; ?>

                <?php endif; /* end if status === 'pending' */ ?>

            </div>

            <!-- Tracking Status -->
            <div class="card-custom">
                <div class="card-section-title"><i class="fas fa-route"></i> Status Pesanan</div>
                <?php
                $urutan  = ['pending', 'diproses', 'selesai'];
                $cur_idx = array_search($pesanan['status'], $urutan);
                if ($cur_idx === false) $cur_idx = -1;
                $steps = [
                    ['Pesanan Dibuat',                 'Pesanan berhasil diterima sistem'],
                    ['DP Dikonfirmasi & Proses Cetak', 'Buku sedang dicetak oleh tim'],
                    ['Selesai & Dikirim',              'Buku siap dikirim ke alamat Anda'],
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
                        <div class="step-line"><div class="step-dot" style="background:#e74c3c"></div></div>
                        <div class="step-content">
                            <div class="step-title" style="color:#e74c3c">Pesanan Dibatalkan</div>
                            <div class="step-desc">Pesanan ini telah dibatalkan</div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tombol -->
            <div class="d-flex flex-column gap-3">
                <a href="pesanan.php" class="btn-primary-custom justify-content-center">
                    <i class="fas fa-list"></i> Lihat Semua Pesanan
                </a>
                <a href="../index.php" class="btn-outline-custom justify-content-center">
                    <i class="fas fa-book-open"></i> Lanjut Belanja
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
                <h5><i class="fas fa-book-open me-2"></i>Braille Book Catalog</h5>
                <p style="opacity:.9;">Yayasan Raudlatul Makfufin - Percetakan Buku Islam Braille<br>Menyediakan Al-Qur'an Braille dan literasi Islami untuk penyandang disabilitas netra sejak 1997.</p>
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
                    <li class="mb-2"><a href="../index.php"><i class="fas fa-chevron-right me-2" style="font-size:12px"></i>Home</a></li>
                    <li class="mb-2"><a href="dashboard.php"><i class="fas fa-chevron-right me-2" style="font-size:12px"></i>Dashboard</a></li>
                    <li class="mb-2"><a href="pesanan.php"><i class="fas fa-chevron-right me-2" style="font-size:12px"></i>Pesanan Saya</a></li>
                    <li class="mb-2"><a href="profil.php"><i class="fas fa-chevron-right me-2" style="font-size:12px"></i>Profil</a></li>
                </ul>
            </div>
            <div class="col-md-4">
                <h5>Kontak Kami</h5>
                <ul class="list-unstyled">
                    <li class="mb-2"><i class="fas fa-map-marker-alt me-2" style="color:var(--accent-gold)"></i>Jl. ... (alamat yayasan)</li>
                    <li class="mb-2"><i class="fas fa-phone me-2" style="color:var(--accent-gold)"></i>+62 xxx-xxxx-xxxx</li>
                    <li class="mb-2"><i class="fas fa-envelope me-2" style="color:var(--accent-gold)"></i>info@raudlatulmakfufin.org</li>
                    <li class="mb-2"><i class="fas fa-clock me-2" style="color:var(--accent-gold)"></i>Senin - Jumat: 08:00 - 16:00 WIB</li>
                </ul>
            </div>
        </div>
        <hr style="border-color:rgba(255,255,255,.2);margin:30px 0 20px;">
        <div class="row">
            <div class="col-md-6"><p class="mb-0" style="opacity:.8;">© 2026 Yayasan Raudlatul Makfufin. All rights reserved.</p></div>
            <div class="col-md-6 text-md-end"><p class="mb-0" style="opacity:.8;">Developed with <i class="fas fa-heart" style="color:#ff6b6b"></i> for Accessibility</p></div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function copyRekening(nomor) {
    navigator.clipboard.writeText(nomor).then(() => {
        const btn = event.target.closest('.btn-copy');
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i> Tersalin!';
        btn.style.background = 'rgba(255,255,255,.35)';
        setTimeout(() => { btn.innerHTML = orig; btn.style.background = ''; }, 2000);
    }).catch(() => {
        // Fallback untuk browser lama
        const el = document.createElement('input');
        el.value = nomor;
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
        alert('Nomor rekening tersalin: ' + nomor);
    });
}
</script>
</body>
</html>