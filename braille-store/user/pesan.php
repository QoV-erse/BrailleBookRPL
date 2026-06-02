<?php
session_start();
require_once '../includes/config.php';

if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit; }
if ($_SESSION['role'] == 'admin') { header("Location: ../admin/dashboard.php"); exit; }

$user_id = $_SESSION['user_id'];
$buku_id = isset($_GET['buku_id']) ? (int)$_GET['buku_id'] : 0;

$buku = null;
if ($buku_id > 0) {
    $q    = mysqli_query($conn, "SELECT * FROM buku WHERE id = $buku_id");
    $buku = mysqli_fetch_assoc($q);
}
if (!$buku) { header("Location: ../index.php"); exit; }

$PERSEN_DP    = 50;
$ONGKIR       = 15000;
$ESTIMASI_MIN = 2;
$ESTIMASI_MAX = 7;

$harga_buku = (isset($buku['harga']) && (float)$buku['harga'] > 0)
    ? (float)$buku['harga'] : 75000.00;

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pesan'])) {
    $qty     = max(1, min(100, (int)($_POST['qty'] ?? 1)));
    $catatan = mysqli_real_escape_string($conn, trim($_POST['catatan'] ?? ''));
    $alamat  = mysqli_real_escape_string($conn, trim($_POST['alamat']  ?? ''));

    if (empty($alamat)) {
        $error = "Alamat pengiriman tidak boleh kosong.";
    } else {
        $subtotal     = $harga_buku * $qty;
        $total_harga  = $subtotal + $ONGKIR;
        $dp_nominal   = ceil($total_harga * $PERSEN_DP / 100);
        $tgl_estimasi = date('Y-m-d', strtotime("+{$ESTIMASI_MAX} days"));

        mysqli_begin_transaction($conn);
        try {
            $sql1 = "INSERT INTO pesanan (user_id, jumlah, dp_nominal, total_harga, ongkir, status, estimasi_selesai, catatan, alamat)
                     VALUES ($user_id, $qty, $dp_nominal, $total_harga, $ONGKIR, 'pending', '$tgl_estimasi', '$catatan', '$alamat')";
            if (!mysqli_query($conn, $sql1)) throw new Exception(mysqli_error($conn));
            $pesanan_id = mysqli_insert_id($conn);

            $sql2 = "INSERT INTO detail_pesanan (pesanan_id, buku_id, jumlah, harga_satuan, subtotal)
                     VALUES ($pesanan_id, $buku_id, $qty, $harga_buku, $subtotal)";
            if (!mysqli_query($conn, $sql2)) throw new Exception(mysqli_error($conn));

            mysqli_commit($conn);
            header("Location: detail-pesanan.php?id=$pesanan_id&status=baru");
            exit;
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Gagal membuat pesanan: " . $e->getMessage();
        }
    }
}

function rupiah($n) { return "Rp " . number_format((float)$n, 0, ',', '.'); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pre-Order – <?= htmlspecialchars($buku['judul']) ?></title>
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

        .card-custom { background:white; border-radius:20px; padding:28px; box-shadow:0 8px 25px rgba(0,0,0,.07); border:1px solid #eee; margin-bottom:24px; }
        .card-section-title { font-size:1rem; font-weight:700; color:var(--primary-dark); margin-bottom:18px; padding-bottom:10px; border-bottom:2px solid #f0fdf9; display:flex; align-items:center; gap:8px; }
        .card-section-title i { color:var(--primary-main); }

        .buku-item { display:flex; align-items:center; gap:18px; padding:16px; border:1.5px solid #e8f5f2; border-radius:14px; background:#f8fffe; }
        .buku-cover { width:72px; height:72px; border-radius:12px; flex-shrink:0; overflow:hidden; background:linear-gradient(135deg,var(--primary-main),var(--primary-dark)); display:flex; align-items:center; justify-content:center; color:white; font-size:28px; }
        .buku-cover img { width:100%; height:100%; object-fit:cover; }
        .buku-info h5 { font-size:1rem; font-weight:700; margin-bottom:4px; }
        .badge-cat { display:inline-block; background:linear-gradient(135deg,var(--primary-main),var(--primary-dark)); color:white; padding:3px 12px; border-radius:20px; font-size:.75rem; font-weight:600; margin-bottom:6px; }
        .buku-info small { color:#888; font-size:.85rem; }
        .buku-harga { margin-left:auto; text-align:right; flex-shrink:0; }
        .buku-harga .harga-satuan { font-size:1.1rem; font-weight:700; color:var(--primary-dark); }
        .buku-harga small { color:#aaa; display:block; font-size:.8rem; }

        .qty-control { display:flex; align-items:center; border:2px solid #e0e0e0; border-radius:12px; overflow:hidden; width:fit-content; }
        .qty-control button { background:white; border:none; width:40px; height:40px; font-size:1.1rem; font-weight:700; color:var(--primary-dark); cursor:pointer; transition:background .2s; display:flex; align-items:center; justify-content:center; }
        .qty-control button:hover { background:#f0fdf9; }
        .qty-control input { border:none; border-left:1px solid #e0e0e0; border-right:1px solid #e0e0e0; width:55px; text-align:center; font-size:1rem; font-weight:600; color:var(--dark-text); height:40px; padding:0; }
        .qty-control input:focus { outline:none; }

        .form-label { font-weight:600; font-size:.9rem; margin-bottom:6px; }
        .form-control { border:1.5px solid #e0e0e0; border-radius:10px; padding:10px 14px; font-size:.95rem; transition:all .3s; }
        .form-control:focus { border-color:var(--primary-main); box-shadow:0 0 0 3px rgba(28,206,172,.15); }

        .summary-box { background:linear-gradient(135deg,#f0fdf9,#e8f8f5); border:2px solid #c8f0e8; border-radius:18px; padding:22px; }
        .summary-row { display:flex; justify-content:space-between; align-items:center; padding:8px 0; font-size:.95rem; }
        .summary-row .label { color:#555; }
        .summary-row .value { font-weight:600; }
        .summary-divider { border:none; border-top:1.5px dashed #c0e8e0; margin:10px 0; }
        .summary-total { display:flex; justify-content:space-between; align-items:center; padding-top:10px; }
        .summary-total .label { font-size:1.05rem; font-weight:700; }
        .summary-total .value { font-size:1.3rem; font-weight:800; color:var(--primary-dark); }

        .dp-box { background:linear-gradient(135deg,#fff8e1,#fff3cd); border:2px solid var(--accent-yellow); border-radius:14px; padding:16px 20px; margin-top:16px; }
        .dp-box .dp-title { font-weight:700; color:#856404; margin-bottom:6px; font-size:.95rem; }
        .dp-box .dp-title i { color:var(--accent-gold); margin-right:6px; }
        .dp-box .dp-amount { font-size:1.4rem; font-weight:800; color:#856404; }
        .dp-box small { color:#997a00; display:block; margin-top:4px; }

        .estimasi-box { background:#e8f4fd; border:1.5px solid #90caf9; border-radius:14px; padding:14px 18px; margin-top:14px; display:flex; align-items:center; gap:12px; }
        .estimasi-box i { color:#1565c0; font-size:1.4rem; }
        .estimasi-box p { margin:0; color:#1a3c6e; font-size:.9rem; line-height:1.5; }

        .btn-order { background:linear-gradient(135deg,var(--primary-main),var(--primary-dark)); color:white; border:none; padding:15px 30px; border-radius:14px; font-weight:700; font-size:1.05rem; width:100%; transition:all .3s; box-shadow:0 6px 20px rgba(28,206,172,.35); display:flex; align-items:center; justify-content:center; gap:10px; cursor:pointer; }
        .btn-order:hover { transform:translateY(-3px); box-shadow:0 10px 28px rgba(28,206,172,.45); color:white; }
        .btn-back { background:white; color:var(--dark-text); border:2px solid #e0e0e0; padding:12px 25px; border-radius:14px; font-weight:600; width:100%; text-align:center; text-decoration:none; display:block; transition:all .3s; margin-top:10px; }
        .btn-back:hover { border-color:var(--primary-main); color:var(--primary-dark); background:#f0fdf9; }

        .alert-error { background:#ffebee; border:1.5px solid #f44336; border-radius:12px; padding:14px 18px; color:#c62828; margin-bottom:20px; display:flex; align-items:center; gap:10px; }

        /* FOOTER */
        .footer { background:linear-gradient(135deg,var(--primary-main) 0%,var(--primary-dark) 100%); color:white; padding:40px 0; margin-top:50px; }
        .footer h5 { color:var(--accent-gold); margin-bottom:15px; }
        .footer a { color:rgba(255,255,255,.9); text-decoration:none; transition:color .3s; }
        .footer a:hover { color:var(--accent-gold); }
        .footer .social-links a { display:inline-block; width:40px; height:40px; background:rgba(255,255,255,.1); border-radius:50%; text-align:center; line-height:40px; margin-right:10px; transition:all .3s; }
        .footer .social-links a:hover { background:var(--accent-gold); color:var(--primary-dark); transform:translateY(-3px); }

        @media(max-width:768px) { .buku-harga{display:none;} .card-custom{padding:18px;} }
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
    <?php if ($error): ?>
    <div class="alert-error"><i class="fas fa-exclamation-circle fa-lg"></i><span><?= htmlspecialchars($error) ?></span></div>
    <?php endif; ?>

    <form method="POST">
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card-custom">
                <div class="card-section-title"><i class="fas fa-book"></i> Buku yang Dipesan</div>
                <div class="buku-item">
                    <div class="buku-cover">
                        <?php if (!empty($buku['gambar']) && file_exists('../assets/img/' . $buku['gambar'])): ?>
                            <img src="../assets/img/<?= $buku['gambar'] ?>" alt="<?= htmlspecialchars($buku['judul']) ?>">
                        <?php else: ?><i class="fas fa-book"></i><?php endif; ?>
                    </div>
                    <div class="buku-info">
                        <span class="badge-cat"><?= $buku['kategori'] ?></span>
                        <h5><?= htmlspecialchars($buku['judul']) ?></h5>
                        <small><i class="fas fa-user me-1"></i><?= htmlspecialchars($buku['pengarang']) ?></small><br>
                        <small><i class="fas fa-ruler me-1"></i><?= $buku['ukuran'] == 'Besar' ? '25.5 × 30.5 cm' : '1.5 × 25.5 cm' ?></small>
                    </div>
                    <div class="buku-harga">
                        <div class="harga-satuan"><?= rupiah($harga_buku) ?></div>
                        <small>per eksemplar</small>
                    </div>
                </div>
                <div class="mt-4">
                    <label class="form-label"><i class="fas fa-sort-numeric-up me-1" style="color:var(--primary-main)"></i>Jumlah Eksemplar</label>
                    <div class="d-flex align-items-center gap-3">
                        <div class="qty-control">
                            <button type="button" onclick="ubahQty(-1)">−</button>
                            <input type="number" name="qty" id="inputQty" value="1" min="1" max="100" oninput="hitungTotal()">
                            <button type="button" onclick="ubahQty(1)">+</button>
                        </div>
                        <small class="text-muted">Maks. 100 eksemplar per pesanan</small>
                    </div>
                </div>
            </div>

            <div class="card-custom">
                <div class="card-section-title"><i class="fas fa-map-marker-alt"></i> Data Pengiriman</div>
                <div class="mb-3">
                    <label class="form-label">Nama Penerima</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($_SESSION['nama_lengkap']) ?>" readonly style="background:#f8f9fa;">
                    <small class="text-muted">Sesuai nama akun yang terdaftar</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Alamat Pengiriman Lengkap *</label>
                    <textarea name="alamat" class="form-control" rows="3"
                        placeholder="Contoh: Jl. Melati No. 12, RT 03/RW 05, Kel. Ciputat, Tangerang Selatan 15412"
                        required><?= htmlspecialchars($_POST['alamat'] ?? '') ?></textarea>
                </div>
                <div class="mb-1">
                    <label class="form-label">Catatan Tambahan (Opsional)</label>
                    <textarea name="catatan" class="form-control" rows="2"
                        placeholder="Contoh: Tolong bungkus bubble wrap, jangan dilipat."
                    ><?= htmlspecialchars($_POST['catatan'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card-custom" style="position:sticky;top:80px;">
                <div class="card-section-title"><i class="fas fa-receipt"></i> Ringkasan Pembayaran</div>
                <div class="summary-box">
                    <div class="summary-row"><span class="label">Harga satuan</span><span class="value"><?= rupiah($harga_buku) ?></span></div>
                    <div class="summary-row"><span class="label">Jumlah (<span id="labelQty">1</span> eks)</span><span class="value" id="nilaiSubtotal"><?= rupiah($harga_buku) ?></span></div>
                    <div class="summary-row"><span class="label">Ongkos kirim</span><span class="value"><?= rupiah($ONGKIR) ?></span></div>
                    <hr class="summary-divider">
                    <div class="summary-total"><span class="label">Total</span><span class="value" id="nilaiTotal"><?= rupiah($harga_buku + $ONGKIR) ?></span></div>
                </div>
                <div class="dp-box">
                    <div class="dp-title"><i class="fas fa-hand-holding-usd"></i> Down Payment (DP <?= $PERSEN_DP ?>%)</div>
                    <div class="dp-amount" id="nilaiDP"><?= rupiah(ceil(($harga_buku + $ONGKIR) * $PERSEN_DP / 100)) ?></div>
                    <small>Bayar DP untuk konfirmasi pesanan. Sisa <?= 100 - $PERSEN_DP ?>% dibayar saat barang tiba.</small>
                </div>
                <div class="estimasi-box">
                    <i class="fas fa-clock"></i>
                    <p><strong>Estimasi selesai:</strong><br><?= $ESTIMASI_MIN ?>–<?= $ESTIMASI_MAX ?> hari kerja setelah DP dikonfirmasi.</p>
                </div>
                <div class="mt-4">
                    <button type="submit" name="pesan" class="btn-order"><i class="fas fa-shopping-cart"></i> Konfirmasi Pre-Order</button>
                    <a href="../detail.php?id=<?= $buku['id'] ?>" class="btn-back"><i class="fas fa-arrow-left me-2"></i> Batal & Kembali</a>
                </div>
                <div class="mt-3 text-center">
                    <small class="text-muted"><i class="fas fa-shield-alt me-1" style="color:var(--primary-main)"></i>Pesanan aman &amp; terlindungi</small>
                </div>
            </div>
        </div>
    </div>
    </form>
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
const HARGA=<?= (float)$harga_buku ?>, ONGKIR_JS=<?= (int)$ONGKIR ?>, DP_PERSEN=<?= (int)$PERSEN_DP ?>;
function formatRupiah(n){return"Rp "+Math.round(n).toLocaleString('id-ID');}
function hitungTotal(){
    const qty=Math.max(1,parseInt(document.getElementById('inputQty').value)||1);
    const subtotal=HARGA*qty, total=subtotal+ONGKIR_JS, dp=Math.ceil(total*DP_PERSEN/100);
    document.getElementById('labelQty').textContent=qty;
    document.getElementById('nilaiSubtotal').textContent=formatRupiah(subtotal);
    document.getElementById('nilaiTotal').textContent=formatRupiah(total);
    document.getElementById('nilaiDP').textContent=formatRupiah(dp);
}
function ubahQty(delta){
    const input=document.getElementById('inputQty');
    input.value=Math.min(100,Math.max(1,(parseInt(input.value)||1)+delta));
    hitungTotal();
}
document.addEventListener('DOMContentLoaded',hitungTotal);
</script>
</body>
</html>