<?php
session_start();
require_once 'includes/config.php';

$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$q      = mysqli_query($conn, "SELECT * FROM buku WHERE id = $id");
$buku   = mysqli_fetch_assoc($q);
if (!$buku) { header("Location: index.php"); exit; }

$is_logged_in = isset($_SESSION['user_id']);
$is_favorit   = false;

if ($is_logged_in) {
    $user_id  = $_SESSION['user_id'];
    $cek_fav  = mysqli_query($conn, "SELECT id FROM favorit WHERE user_id = $user_id AND buku_id = $id");
    $is_favorit = mysqli_num_rows($cek_fav) > 0;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($buku['judul']) ?> - Braille Book Catalog</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-main: #1cceac;
            --primary-dark: #15a38a;
            --primary-light:#4ddbc3;
            --accent-gold:  #FFD700;
            --accent-yellow:#FFC107;
            --accent-red:   #e74c3c;
            --light-bg:     #f8f9fa;
            --dark-text:    #2c3e50;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',sans-serif; font-size:16px; background:var(--light-bg); color:var(--dark-text); }

        /* NAVBAR */
        .navbar { background:linear-gradient(135deg,var(--primary-main) 0%,var(--primary-dark) 100%)!important; box-shadow:0 4px 15px rgba(28,206,172,.2); padding:15px 0; position:sticky; top:0; z-index:999; }
        .navbar-brand { font-size:1.5rem; font-weight:bold; color:white!important; }
        .navbar-brand i { color:var(--accent-gold); margin-right:10px; }
        .navbar-nav .nav-link { color:rgba(255,255,255,.95)!important; font-weight:500; padding:8px 16px!important; transition:all .3s; }
        .navbar-nav .nav-link:hover, .navbar-nav .nav-link.active { color:var(--accent-gold)!important; background:rgba(255,255,255,.15); border-radius:8px; }
        .user-badge { background:rgba(255,255,255,.2); padding:7px 15px; border-radius:50px; color:white; font-size:.95rem; display:flex; align-items:center; gap:6px; }
        .user-badge i { color:var(--accent-gold); }

        /* DETAIL SECTION */
        .detail-section { padding:40px 0; }
        .book-image-container { background:white; border-radius:20px; overflow:hidden; box-shadow:0 10px 30px rgba(0,0,0,.1); position:sticky; top:80px; }
        .book-image-large { width:100%; height:400px; background:linear-gradient(135deg,var(--primary-main) 0%,var(--primary-dark) 100%); display:flex; align-items:center; justify-content:center; color:white; font-size:80px; }
        .book-image-large img { width:100%; height:100%; object-fit:cover; }
        .book-info-card { background:white; border-radius:20px; padding:30px; box-shadow:0 10px 30px rgba(0,0,0,.1); height:100%; }
        .category-badge { display:inline-block; background:linear-gradient(135deg,var(--primary-main),var(--primary-dark)); color:white; padding:8px 20px; border-radius:30px; font-size:.9rem; font-weight:600; margin-bottom:15px; }
        .book-title-detail { font-size:2rem; font-weight:bold; color:var(--dark-text); margin-bottom:5px; line-height:1.3; }
        .book-author-detail { font-size:1.1rem; color:#666; margin-bottom:20px; }
        .book-author-detail i { color:var(--primary-main); margin-right:5px; }

        .info-table { width:100%; margin-bottom:20px; }
        .info-table tr { border-bottom:1px solid #eee; }
        .info-table td { padding:12px 5px; vertical-align:top; }
        .info-table .label { font-weight:600; color:var(--primary-dark); width:40%; }
        .info-table .label i { width:20px; color:var(--primary-main); margin-right:5px; }

        .size-badge { display:inline-block; padding:5px 15px; border-radius:20px; font-weight:600; font-size:.9rem; }
        .size-besar { background:#e3f2fd; color:#1565c0; }
        .size-kecil { background:#fff3e0; color:#e65100; }

        .sinopsis-box { background:#f8f9fa; border-radius:15px; padding:20px; margin:20px 0; border-left:4px solid var(--primary-main); }
        .sinopsis-box h5 { color:var(--primary-dark); margin-bottom:10px; }

        .tts-status { display:none; background:#fff3cd; border:1px solid #ffc107; border-radius:10px; padding:10px 15px; margin-top:10px; color:#856404; }
        .tts-status.show { display:block; }

        /* ACTION BUTTONS */
        .action-buttons { display:flex; gap:10px; flex-wrap:wrap; margin-top:20px; }
        .btn-favorit { background:white; color:var(--accent-red); border:2px solid var(--accent-red); padding:12px 25px; border-radius:30px; font-weight:600; transition:all .3s; text-decoration:none; }
        .btn-favorit:hover, .btn-favorit.active { background:var(--accent-red); color:white; transform:translateY(-3px); }
        .btn-order { background:var(--primary-main); color:white; border:none; padding:12px 25px; border-radius:30px; font-weight:600; transition:all .3s; text-decoration:none; }
        .btn-order:hover { background:var(--primary-dark); color:white; transform:translateY(-3px); box-shadow:0 5px 15px rgba(28,206,172,.4); }
        .btn-tts { background:var(--accent-gold); color:var(--dark-text); border:none; padding:12px 25px; border-radius:30px; font-weight:600; transition:all .3s; }
        .btn-tts:hover { background:var(--accent-yellow); transform:translateY(-3px); }
        .btn-tts.speaking { background:var(--accent-red); color:white; animation:pulse 1s infinite; }
        .btn-back { background:#6c757d; color:white; border:none; padding:12px 25px; border-radius:30px; font-weight:600; transition:all .3s; text-decoration:none; }
        .btn-back:hover { background:#5a6268; color:white; transform:translateY(-3px); }
        @keyframes pulse { 0%,100%{transform:scale(1)} 50%{transform:scale(1.05)} }

        /* FOOTER */
        .footer { background:linear-gradient(135deg,var(--primary-main) 0%,var(--primary-dark) 100%); color:white; padding:40px 0; margin-top:50px; }
        .footer h5 { color:var(--accent-gold); margin-bottom:15px; }
        .footer a { color:rgba(255,255,255,.9); text-decoration:none; transition:color .3s; }
        .footer a:hover { color:var(--accent-gold); }
        .footer .social-links a { display:inline-block; width:40px; height:40px; background:rgba(255,255,255,.1); border-radius:50%; text-align:center; line-height:40px; margin-right:10px; transition:all .3s; }
        .footer .social-links a:hover { background:var(--accent-gold); color:var(--primary-dark); transform:translateY(-3px); }

        @media(max-width:768px) {
            .book-title-detail { font-size:1.5rem; }
            .book-image-large { height:300px; }
            .action-buttons { flex-direction:column; }
            .action-buttons .btn, .action-buttons a { width:100%; text-align:center; }
            .book-image-container { position:static; margin-bottom:20px; }
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-book-open"></i> <strong>BRAILLE BOOK CATALOG</strong>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php"><i class="fas fa-home"></i> KATALOG</a>
                </li>
                <?php if ($is_logged_in): ?>
                <li class="nav-item">
                    <a class="nav-link" href="user/dashboard.php"><i class="fas fa-th-large"></i> DASHBOARD</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="user/pesanan.php"><i class="fas fa-shopping-bag"></i> PESANAN</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="user/profil.php"><i class="fas fa-user-circle"></i> PROFIL</a>
                </li>
                <?php endif; ?>
            </ul>
            <?php if ($is_logged_in): ?>
            <div class="user-badge">
                <i class="fas fa-user-circle"></i>
                <?= htmlspecialchars($_SESSION['nama_lengkap']) ?>
                <a href="logout.php" class="btn btn-sm btn-outline-light ms-2">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
            <?php else: ?>
            <a href="login.php" class="btn btn-outline-light btn-sm">
                <i class="fas fa-sign-in-alt me-1"></i> LOGIN
            </a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- DETAIL BUKU -->
<section class="detail-section">
    <div class="container">
        <div class="row g-4">
            <!-- Gambar -->
            <div class="col-lg-5">
                <div class="book-image-container">
                    <div class="book-image-large">
                        <?php if ($buku['gambar'] && file_exists('assets/img/' . $buku['gambar'])): ?>
                            <img src="assets/img/<?= $buku['gambar'] ?>" alt="<?= htmlspecialchars($buku['judul']) ?>">
                        <?php else: ?>
                            <i class="fas fa-book"></i>
                        <?php endif; ?>
                    </div>
                    <div class="p-3 text-center">
                        <?php if ($is_logged_in): ?>
                        <a href="user/db_favorit.php?action=<?= $is_favorit ? 'hapus' : 'tambah' ?>&buku_id=<?= $buku['id'] ?>"
                           class="btn btn-favorit <?= $is_favorit ? 'active' : '' ?> w-100">
                            <i class="fas fa-heart"></i>
                            <?= $is_favorit ? 'Hapus dari Favorit' : 'Tambah ke Favorit' ?>
                        </a>
                        <?php else: ?>
                        <a href="login.php" class="btn btn-favorit w-100">
                            <i class="fas fa-heart"></i> Login untuk Favorit
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Info -->
            <div class="col-lg-7">
                <div class="book-info-card">
                    <span class="category-badge"><i class="fas fa-tag me-2"></i><?= $buku['kategori'] ?></span>
                    <h1 class="book-title-detail"><?= htmlspecialchars($buku['judul']) ?></h1>
                    <p class="book-author-detail">
                        <i class="fas fa-user-edit"></i> Oleh: <strong><?= htmlspecialchars($buku['pengarang']) ?></strong>
                    </p>

                    <table class="info-table">
                        <tr>
                            <td class="label"><i class="fas fa-building"></i> Penerbit</td>
                            <td><?= htmlspecialchars($buku['penerbit']) ?></td>
                        </tr>
                        <tr>
                            <td class="label"><i class="fas fa-file-alt"></i> Jumlah Halaman</td>
                            <td><?= $buku['jml_halaman'] ?> halaman</td>
                        </tr>
                        <tr>
                            <td class="label"><i class="fas fa-layer-group"></i> Jumlah Lembaran</td>
                            <td><?= $buku['jml_lembaran'] ?> lembar</td>
                        </tr>
                        <tr>
                            <td class="label"><i class="fas fa-ruler"></i> Ukuran Layout</td>
                            <td>
                                <?php if ($buku['ukuran'] == 'Besar'): ?>
                                    <span class="size-badge size-besar">25.5 × 30.5 cm (Besar)</span>
                                <?php else: ?>
                                    <span class="size-badge size-kecil">1.5 × 25.5 cm (Kecil)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="label"><i class="fas fa-tag"></i> Harga</td>
                            <td><strong style="color:var(--primary-dark);font-size:1.1rem;">
                                Rp <?= number_format($buku['harga'] ?? 75000, 0, ',', '.') ?>
                            </strong></td>
                        </tr>
                        <tr>
                            <td class="label"><i class="fas fa-box"></i> Stok</td>
                            <td>
                                <?php if ($buku['stok_tersedia'] > 0): ?>
                                    <span class="text-success fw-bold"><?= $buku['stok_tersedia'] ?> tersedia</span>
                                <?php else: ?>
                                    <span class="text-warning fw-bold">Pre-Order (2–7 hari)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>

                    <div class="sinopsis-box">
                        <h5><i class="fas fa-book-reader me-2"></i>Sinopsis / Deskripsi</h5>
                        <p id="sinopsis-text"><?= nl2br(htmlspecialchars($buku['sinopsis'])) ?></p>
                    </div>

                    <div class="tts-status" id="ttsStatus">
                        <i class="fas fa-volume-up me-2"></i>
                        <span id="ttsMessage">Membacakan informasi buku...</span>
                    </div>

                    <div class="action-buttons">
                        <button class="btn btn-tts" id="btnTTS" onclick="toggleTTS()">
                            <i class="fas fa-volume-up"></i> Dengarkan Detail
                        </button>
                        <?php if ($is_logged_in): ?>
                            <a href="user/pesan.php?buku_id=<?= $buku['id'] ?>" class="btn btn-order">
                                <i class="fas fa-shopping-cart"></i> Pre-Order Sekarang
                            </a>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-order">
                                <i class="fas fa-shopping-cart"></i> Login untuk Pre-Order
                            </a>
                        <?php endif; ?>
                        <a href="index.php" class="btn btn-back">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="col-md-5 mb-4 mb-md-0">
                <h5><i class="fas fa-book-open me-2"></i>Braille Book Catalog</h5>
                <p style="opacity:.9;">
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
                    <li class="mb-2"><a href="index.php"><i class="fas fa-chevron-right me-2" style="font-size:12px"></i>Home</a></li>
                    <li class="mb-2"><a href="login.php"><i class="fas fa-chevron-right me-2" style="font-size:12px"></i>Login</a></li>
                    <?php if ($is_logged_in): ?>
                    <li class="mb-2"><a href="user/dashboard.php"><i class="fas fa-chevron-right me-2" style="font-size:12px"></i>Dashboard</a></li>
                    <li class="mb-2"><a href="user/pesanan.php"><i class="fas fa-chevron-right me-2" style="font-size:12px"></i>Pesanan Saya</a></li>
                    <?php endif; ?>
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
            <div class="col-md-6">
                <p class="mb-0" style="opacity:.8;">© 2026 Yayasan Raudlatul Makfufin. All rights reserved.</p>
            </div>
            <div class="col-md-6 text-md-end">
                <p class="mb-0" style="opacity:.8;">Developed with <i class="fas fa-heart" style="color:#ff6b6b"></i> for Accessibility</p>
            </div>
        </div>
    </div>
</footer>

<script>
const synth = window.speechSynthesis;
let speaking = false, utterance = null;
function toggleTTS() { speaking ? stopTTS() : startTTS(); }
function startTTS() {
    const judul    = "<?= addslashes(htmlspecialchars($buku['judul'])) ?>";
    const pengarang= "<?= addslashes(htmlspecialchars($buku['pengarang'])) ?>";
    const penerbit = "<?= addslashes(htmlspecialchars($buku['penerbit'])) ?>";
    const halaman  = "<?= $buku['jml_halaman'] ?>";
    const lembaran = "<?= $buku['jml_lembaran'] ?>";
    const ukuran   = "<?= $buku['ukuran'] == 'Besar' ? '25.5 kali 30.5 sentimeter, ukuran besar' : '1.5 kali 25.5 sentimeter, ukuran kecil' ?>";
    const kategori = "<?= $buku['kategori'] ?>";
    const sinopsis = "<?= addslashes(strip_tags($buku['sinopsis'])) ?>";
    const teks = `Informasi buku. Judul: ${judul}. Pengarang: ${pengarang}. Penerbit: ${penerbit}. Kategori: ${kategori}. Jumlah halaman: ${halaman} halaman. Jumlah lembaran Braille: ${lembaran} lembar. Ukuran: ${ukuran}. Sinopsis: ${sinopsis}`;
    utterance = new SpeechSynthesisUtterance(teks);
    utterance.lang = 'id-ID'; utterance.rate = 0.9; utterance.pitch = 1; utterance.volume = 1;
    utterance.onstart = () => { speaking = true; updateTTSButton(true); showTTSStatus(true, 'Membacakan informasi buku...'); };
    utterance.onend   = () => { speaking = false; updateTTSButton(false); showTTSStatus(true, 'Selesai membacakan.'); setTimeout(() => showTTSStatus(false), 3000); };
    utterance.onerror = () => { speaking = false; updateTTSButton(false); showTTSStatus(true, 'Gagal membacakan. Silakan coba lagi.'); };
    synth.speak(utterance);
}
function stopTTS() { synth.cancel(); speaking = false; updateTTSButton(false); showTTSStatus(false); }
function updateTTSButton(isSpeaking) {
    const btn = document.getElementById('btnTTS');
    btn.classList.toggle('speaking', isSpeaking);
    btn.innerHTML = isSpeaking ? '<i class="fas fa-stop-circle"></i> Berhenti' : '<i class="fas fa-volume-up"></i> Dengarkan Detail';
}
function showTTSStatus(show, message = '') {
    const s = document.getElementById('ttsStatus');
    s.classList.toggle('show', show);
    if (message) document.getElementById('ttsMessage').textContent = message;
}
window.addEventListener('beforeunload', () => synth.cancel());
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>