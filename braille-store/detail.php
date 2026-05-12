<?php
session_start();
require_once 'includes/config.php';

// Ambil ID buku dari URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Ambil data buku berdasarkan ID
$query = "SELECT * FROM buku WHERE id = $id";
$result = mysqli_query($conn, $query);
$buku = mysqli_fetch_assoc($result);

// Jika buku tidak ditemukan, redirect ke home
if (!$buku) {
    header("Location: index.php");
    exit;
}

// Cek apakah user sudah login
$is_logged_in = isset($_SESSION['user_id']);
$is_favorit = false;

if ($is_logged_in) {
    $user_id = $_SESSION['user_id'];
    $cek_fav = mysqli_query($conn, "SELECT id FROM favorit WHERE user_id = $user_id AND buku_id = $id");
    $is_favorit = mysqli_num_rows($cek_fav) > 0;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($buku['judul']) ?> - Braille Book Catalog</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* ========== WARNA UTAMA: HIJAU TOSCA ========== */
        :root {
            --primary-main: #1cceac;
            --primary-dark: #15a38a;
            --primary-light: #4ddbc3;
            --accent-gold: #FFD700;
            --accent-yellow: #FFC107;
            --accent-red: #e74c3c;
            --light-bg: #f8f9fa;
            --dark-text: #2c3e50;
            --white: #ffffff;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 16px;
            background-color: var(--light-bg);
            color: var(--dark-text);
        }
        
        /* ========== NAVBAR ========== */
        .navbar {
            background: linear-gradient(135deg, var(--primary-main) 0%, var(--primary-dark) 100%) !important;
            box-shadow: 0 4px 15px rgba(28, 206, 172, 0.2);
            padding: 15px 0;
        }
        
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: bold;
            color: white !important;
        }
        
        .navbar-brand i {
            color: var(--accent-gold);
            margin-right: 10px;
        }
        
        .navbar-nav .nav-link {
            color: rgba(255,255,255,0.95) !important;
            font-weight: 500;
            padding: 8px 16px !important;
            transition: all 0.3s;
        }
        
        .navbar-nav .nav-link:hover {
            color: var(--accent-gold) !important;
            background: rgba(255,255,255,0.15);
            border-radius: 8px;
        }
        
        /* ========== BREADCRUMB ========== */
        .breadcrumb-section {
            background: linear-gradient(135deg, var(--primary-main) 0%, var(--primary-dark) 100%);
            padding: 30px 0;
            color: white;
        }
        
        .breadcrumb-section a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
        }
        
        .breadcrumb-section a:hover {
            color: var(--accent-gold);
        }
        
        .breadcrumb-section .active {
            color: var(--accent-gold);
        }
        
        /* ========== BOOK DETAIL SECTION ========== */
        .detail-section {
            padding: 40px 0;
        }
        
        .book-image-container {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            position: sticky;
            top: 20px;
        }
        
        .book-image-large {
            width: 100%;
            height: 400px;
            background: linear-gradient(135deg, var(--primary-main) 0%, var(--primary-dark) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 80px;
        }
        
        .book-image-large img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .book-info-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            height: 100%;
        }
        
        .category-badge {
            display: inline-block;
            background: linear-gradient(135deg, var(--primary-main), var(--primary-dark));
            color: white;
            padding: 8px 20px;
            border-radius: 30px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .book-title-detail {
            font-size: 2rem;
            font-weight: bold;
            color: var(--dark-text);
            margin-bottom: 5px;
            line-height: 1.3;
        }
        
        .book-author-detail {
            font-size: 1.1rem;
            color: #666;
            margin-bottom: 20px;
        }
        
        .book-author-detail i {
            color: var(--primary-main);
            margin-right: 5px;
        }
        
        .info-table {
            width: 100%;
            margin-bottom: 20px;
        }
        
        .info-table tr {
            border-bottom: 1px solid #eee;
        }
        
        .info-table td {
            padding: 12px 5px;
            vertical-align: top;
        }
        
        .info-table .label {
            font-weight: 600;
            color: var(--primary-dark);
            width: 40%;
        }
        
        .info-table .label i {
            width: 20px;
            color: var(--primary-main);
            margin-right: 5px;
        }
        
        .info-table .value {
            color: var(--dark-text);
        }
        
        .size-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .size-besar {
            background: #e3f2fd;
            color: #1565c0;
        }
        
        .size-kecil {
            background: #fff3e0;
            color: #e65100;
        }
        
        /* ========== SINOPSIS ========== */
        .sinopsis-box {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid var(--primary-main);
        }
        
        .sinopsis-box h5 {
            color: var(--primary-dark);
            margin-bottom: 10px;
        }
        
        .sinopsis-box p {
            color: #555;
            line-height: 1.8;
            margin-bottom: 0;
        }
        
        /* ========== ACTION BUTTONS ========== */
        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 20px;
        }
        
        .btn-favorit {
            background: white;
            color: var(--accent-red);
            border: 2px solid var(--accent-red);
            padding: 12px 25px;
            border-radius: 30px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-favorit:hover {
            background: var(--accent-red);
            color: white;
            transform: translateY(-3px);
        }
        
        .btn-favorit.active {
            background: var(--accent-red);
            color: white;
        }
        
        .btn-favorit.active:hover {
            background: #c0392b;
            border-color: #c0392b;
        }
        
        .btn-order {
            background: var(--primary-main);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 30px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-order:hover {
            background: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(28, 206, 172, 0.4);
            color: white;
        }
        
        .btn-tts {
            background: var(--accent-gold);
            color: var(--dark-text);
            border: none;
            padding: 12px 25px;
            border-radius: 30px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-tts:hover {
            background: var(--accent-yellow);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(255, 215, 0, 0.4);
        }
        
        .btn-tts.speaking {
            background: var(--accent-red);
            color: white;
            animation: pulse 1s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .btn-back {
            background: #6c757d;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 30px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-back:hover {
            background: #5a6268;
            color: white;
            transform: translateY(-3px);
        }
        
        /* ========== TTS STATUS ========== */
        .tts-status {
            display: none;
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 10px;
            padding: 10px 15px;
            margin-top: 10px;
            color: #856404;
        }
        
        .tts-status.show {
            display: block;
        }
        
        /* ========== RESPONSIVE ========== */
        @media (max-width: 768px) {
            .book-title-detail {
                font-size: 1.5rem;
            }
            
            .book-image-large {
                height: 300px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .action-buttons .btn {
                width: 100%;
                text-align: center;
            }
            
            .book-image-container {
                position: static;
                margin-bottom: 20px;
            }
        }
        
        /* ========== FOOTER ========== */
        .footer {
            background: linear-gradient(135deg, var(--primary-main) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 30px 0;
            margin-top: 50px;
        }
        
        .footer a {
            color: var(--accent-gold);
            text-decoration: none;
        }
    </style>
</head>
<body>

<!-- ========== NAVBAR ========== -->
<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-book-open"></i> 
            <strong>BRAILLE BOOK CATALOG</strong>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">
                        <i class="fas fa-home"></i> HOME
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="fas fa-info-circle"></i> ABOUT
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="fas fa-envelope"></i> CONTACT
                    </a>
                </li>
                <?php if ($is_logged_in): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="user/dashboard.php">
                            <i class="fas fa-user"></i> DASHBOARD
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt"></i> LOGOUT
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">
                            <i class="fas fa-sign-in-alt"></i> LOGIN
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- ========== BREADCRUMB ========== -->
<section class="breadcrumb-section">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="index.php?kategori=<?= $buku['kategori'] ?>"><?= $buku['kategori'] ?></a></li>
                <li class="breadcrumb-item active"><?= htmlspecialchars($buku['judul']) ?></li>
            </ol>
        </nav>
    </div>
</section>

<!-- ========== DETAIL BUKU ========== -->
<section class="detail-section">
    <div class="container">
        <div class="row g-4">
            
            <!-- Gambar Buku -->
            <div class="col-lg-5">
                <div class="book-image-container">
                    <div class="book-image-large">
                        <?php if ($buku['gambar'] && file_exists('assets/img/' . $buku['gambar'])): ?>
                            <img src="assets/img/<?= $buku['gambar'] ?>" 
                                 alt="<?= htmlspecialchars($buku['judul']) ?>">
                        <?php else: ?>
                            <i class="fas fa-book"></i>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Tombol Favorit (di bawah gambar) -->
                    <?php if ($is_logged_in): ?>
                    <div class="p-3 text-center">
                        <a href="user/db_favorit.php?action=<?= $is_favorit ? 'hapus' : 'tambah' ?>&buku_id=<?= $buku['id'] ?>" 
                           class="btn btn-favorit <?= $is_favorit ? 'active' : '' ?> w-100">
                            <i class="fas fa-heart"></i> 
                            <?= $is_favorit ? 'Hapus dari Favorit' : 'Tambah ke Favorit' ?>
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="p-3 text-center">
                        <a href="login.php" class="btn btn-favorit w-100">
                            <i class="fas fa-heart"></i> Login untuk Favorit
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Informasi Buku -->
            <div class="col-lg-7">
                <div class="book-info-card">
                    
                    <!-- Kategori -->
                    <span class="category-badge">
                        <i class="fas fa-tag me-2"></i><?= $buku['kategori'] ?>
                    </span>
                    
                    <!-- Judul -->
                    <h1 class="book-title-detail"><?= htmlspecialchars($buku['judul']) ?></h1>
                    
                    <!-- Pengarang -->
                    <p class="book-author-detail">
                        <i class="fas fa-user-edit"></i> 
                        Oleh: <strong><?= htmlspecialchars($buku['pengarang']) ?></strong>
                    </p>
                    
                    <!-- Tabel Informasi Detail -->
                    <table class="info-table">
                        <tr>
                            <td class="label">
                                <i class="fas fa-building"></i> Penerbit
                            </td>
                            <td class="value"><?= htmlspecialchars($buku['penerbit']) ?></td>
                        </tr>
                        <tr>
                            <td class="label">
                                <i class="fas fa-file-alt"></i> Jumlah Halaman
                            </td>
                            <td class="value"><?= $buku['jml_halaman'] ?> halaman</td>
                        </tr>
                        <tr>
                            <td class="label">
                                <i class="fas fa-layer-group"></i> Jumlah Lembaran Braille
                            </td>
                            <td class="value"><?= $buku['jml_lembaran'] ?> lembar</td>
                        </tr>
                        <tr>
                            <td class="label">
                                <i class="fas fa-ruler"></i> Ukuran Layout
                            </td>
                            <td class="value">
                                <?php if ($buku['ukuran'] == 'Besar'): ?>
                                    <span class="size-badge size-besar">
                                        25.5 × 30.5 cm (Besar)
                                    </span>
                                <?php else: ?>
                                    <span class="size-badge size-kecil">
                                        1.5 × 25.5 cm (Kecil)
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="label">
                                <i class="fas fa-box"></i> Stok
                            </td>
                            <td class="value">
                                <?php if ($buku['stok_tersedia'] > 0): ?>
                                    <span class="text-success fw-bold"><?= $buku['stok_tersedia'] ?> tersedia</span>
                                <?php else: ?>
                                    <span class="text-warning fw-bold">Pre-Order (2-7 hari)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                    
                    <!-- Sinopsis -->
                    <div class="sinopsis-box">
                        <h5><i class="fas fa-book-reader me-2"></i>Sinopsis / Deskripsi</h5>
                        <p id="sinopsis-text"><?= nl2br(htmlspecialchars($buku['sinopsis'])) ?></p>
                    </div>
                    
                    <!-- TTS Status -->
                    <div class="tts-status" id="ttsStatus">
                        <i class="fas fa-volume-up me-2"></i>
                        <span id="ttsMessage">Membacakan informasi buku...</span>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <!-- Tombol Text-to-Speech -->
                        <button class="btn btn-tts" id="btnTTS" onclick="toggleTTS()">
                            <i class="fas fa-volume-up"></i> Dengarkan Detail
                        </button>
                        
                        <!-- Tombol Pre-Order -->
                        <?php if ($is_logged_in): ?>
                            <a href="user/pesan.php?buku_id=<?= $buku['id'] ?>" class="btn btn-order">
                                <i class="fas fa-shopping-cart"></i> Pre-Order Sekarang
                            </a>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-order">
                                <i class="fas fa-shopping-cart"></i> Login untuk Pre-Order
                            </a>
                        <?php endif; ?>
                        
                        <!-- Tombol Kembali -->
                        <a href="index.php" class="btn btn-back">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ========== FOOTER ========== -->
<footer class="footer">
    <div class="container text-center">
        <p class="mb-0">
            © 2026 Yayasan Raudlatul Makfufin - Percetakan Buku Islam Braille<br>
            <small>
                <a href="index.php">Home</a> | 
                <a href="#">About</a> | 
                <a href="#">Contact</a>
            </small>
        </p>
    </div>
</footer>

<!-- ========== TEXT-TO-SPEECH SCRIPT ========== -->
<script>
    // Inisialisasi Speech Synthesis
    const synth = window.speechSynthesis;
    let speaking = false;
    let utterance = null;
    
    function toggleTTS() {
        if (speaking) {
            stopTTS();
        } else {
            startTTS();
        }
    }
    
    function startTTS() {
        // Ambil teks yang akan dibacakan
        const judul = "<?= addslashes(htmlspecialchars($buku['judul'])) ?>";
        const pengarang = "<?= addslashes(htmlspecialchars($buku['pengarang'])) ?>";
        const penerbit = "<?= addslashes(htmlspecialchars($buku['penerbit'])) ?>";
        const halaman = "<?= $buku['jml_halaman'] ?>";
        const lembaran = "<?= $buku['jml_lembaran'] ?>";
        const ukuran = "<?= $buku['ukuran'] == 'Besar' ? '25.5 kali 30.5 sentimeter, ukuran besar' : '1.5 kali 25.5 sentimeter, ukuran kecil' ?>";
        const kategori = "<?= $buku['kategori'] ?>";
        const sinopsis = "<?= addslashes(strip_tags($buku['sinopsis'])) ?>";
        
        const teks = `Informasi buku. Judul: ${judul}. 
                      Pengarang: ${pengarang}. 
                      Penerbit: ${penerbit}. 
                      Kategori: ${kategori}. 
                      Jumlah halaman: ${halaman} halaman. 
                      Jumlah lembaran Braille: ${lembaran} lembar. 
                      Ukuran: ${ukuran}. 
                      Sinopsis: ${sinopsis}`;
        
        // Buat utterance
        utterance = new SpeechSynthesisUtterance(teks);
        utterance.lang = 'id-ID'; // Bahasa Indonesia
        utterance.rate = 0.9;     // Kecepatan (0.1 - 10)
        utterance.pitch = 1;       // Nada (0 - 2)
        utterance.volume = 1;      // Volume (0 - 1)
        
        // Event handlers
        utterance.onstart = function() {
            speaking = true;
            updateTTSButton(true);
            showTTSStatus(true, 'Membacakan informasi buku...');
        };
        
        utterance.onend = function() {
            speaking = false;
            updateTTSButton(false);
            showTTSStatus(true, 'Selesai membacakan.');
            setTimeout(() => showTTSStatus(false), 3000);
        };
        
        utterance.onerror = function(e) {
            speaking = false;
            updateTTSButton(false);
            showTTSStatus(true, 'Gagal membacakan. Silakan coba lagi.');
            console.error('TTS Error:', e);
        };
        
        // Mulai berbicara
        synth.speak(utterance);
    }
    
    function stopTTS() {
        synth.cancel();
        speaking = false;
        updateTTSButton(false);
        showTTSStatus(false);
    }
    
    function updateTTSButton(isSpeaking) {
        const btn = document.getElementById('btnTTS');
        const icon = btn.querySelector('i');
        
        if (isSpeaking) {
            btn.classList.add('speaking');
            btn.innerHTML = '<i class="fas fa-stop-circle"></i> Berhenti';
        } else {
            btn.classList.remove('speaking');
            btn.innerHTML = '<i class="fas fa-volume-up"></i> Dengarkan Detail';
        }
    }
    
    function showTTSStatus(show, message = '') {
        const status = document.getElementById('ttsStatus');
        const msg = document.getElementById('ttsMessage');
        
        if (show) {
            status.classList.add('show');
            if (message) msg.textContent = message;
        } else {
            status.classList.remove('show');
        }
    }
    
    // Hentikan TTS saat halaman ditutup
    window.addEventListener('beforeunload', function() {
        synth.cancel();
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>