<?php
session_start();
require_once 'includes/config.php';

// Ambil keyword pencarian
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$ukuran_filter = isset($_GET['ukuran']) ? $_GET['ukuran'] : '';

// Validasi ukuran
$ukuran_valid = ['Besar', 'Kecil'];
if (!in_array($ukuran_filter, $ukuran_valid)) {
    $ukuran_filter = '';
}

// Bangun query pencarian
$where_parts = [];

if ($keyword !== '') {
    $keyword_esc = mysqli_real_escape_string($conn, $keyword);
    $where_parts[] = "(judul LIKE '%$keyword_esc%' OR pengarang LIKE '%$keyword_esc%')";
}

if ($ukuran_filter !== '') {
    $where_parts[] = "ukuran = '$ukuran_filter'";
}

$where_clause = '';
if (!empty($where_parts)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_parts);
}

// Sesuai wawancara No.6: pencarian berdasarkan judul & ukuran, urutan abjad (No.7)
$query = "SELECT * FROM buku $where_clause ORDER BY judul ASC";
$result = mysqli_query($conn, $query);
$jumlah_hasil = mysqli_num_rows($result);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Pencarian<?= $keyword ? ' - ' . htmlspecialchars($keyword) : '' ?> - Braille Book Catalog</title>

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
            font-size: 1rem;
        }

        .navbar-nav .nav-link:hover,
        .navbar-nav .nav-link.active {
            color: var(--accent-gold) !important;
            background: rgba(255,255,255,0.15);
            border-radius: 8px;
        }

        /* ========== SEARCH SECTION ========== */
        .search-section {
            background: linear-gradient(135deg, var(--primary-main) 0%, var(--primary-dark) 100%);
            padding: 40px 0;
            border-radius: 0 0 30px 30px;
            margin-bottom: 35px;
        }

        .search-section h2 {
            color: white;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .search-section h2 span {
            color: var(--accent-gold);
        }

        .search-box {
            background: white;
            border-radius: 50px;
            padding: 5px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        .search-box input {
            border: none;
            padding: 15px 20px;
            font-size: 1.1rem;
            border-radius: 50px 0 0 50px;
            width: 100%;
        }

        .search-box input:focus {
            outline: none;
            box-shadow: none;
        }

        .search-box button {
            background: var(--accent-gold);
            color: var(--dark-text);
            border: none;
            padding: 15px 30px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s;
            white-space: nowrap;
        }

        .search-box button:hover {
            background: var(--accent-yellow);
            transform: scale(1.02);
        }

        /* ========== FILTER UKURAN ========== */
        .filter-bar {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 25px;
        }

        .filter-label {
            font-weight: 600;
            color: var(--primary-dark);
            font-size: 0.95rem;
        }

        .filter-label i {
            color: var(--primary-main);
            margin-right: 5px;
        }

        .btn-filter {
            background: white;
            color: var(--dark-text);
            border: 2px solid #e0e0e0;
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.3s;
            text-decoration: none;
        }

        .btn-filter:hover {
            border-color: var(--primary-main);
            color: var(--primary-dark);
            background: #f0fdf9;
        }

        .btn-filter.active {
            background: var(--primary-main);
            color: white;
            border-color: var(--primary-main);
        }

        .btn-filter.active:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
            color: white;
        }

        /* ========== SECTION TITLE ========== */
        .section-title {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
        }

        .section-title h3 {
            font-weight: bold;
            color: var(--primary-dark);
            margin: 0;
            font-size: 1.4rem;
        }

        .section-title i {
            color: var(--primary-main);
            margin-right: 12px;
            font-size: 26px;
        }

        .section-title .line {
            flex: 1;
            height: 3px;
            background: linear-gradient(90deg, var(--primary-main), transparent);
            margin-left: 20px;
        }

        .hasil-count {
            background: var(--primary-main);
            color: white;
            padding: 5px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-left: 12px;
        }

        /* ========== BOOK CARD ========== */
        .book-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
            transition: all 0.3s;
            height: 100%;
            border: 2px solid #eee;
            display: flex;
            flex-direction: column;
        }

        .book-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(28, 206, 172, 0.2);
            border-color: var(--primary-main);
        }

        .book-img {
            height: 220px;
            background: linear-gradient(135deg, var(--primary-main) 0%, var(--primary-dark) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 48px;
            position: relative;
            overflow: hidden;
        }

        .book-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }

        .book-card:hover .book-img img {
            transform: scale(1.05);
        }

        .book-body {
            padding: 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .book-category {
            display: inline-block;
            background: linear-gradient(135deg, var(--primary-main), var(--primary-dark));
            color: white;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 12px;
            align-self: flex-start;
        }

        .book-title {
            font-weight: bold;
            font-size: 1.1rem;
            color: var(--dark-text);
            margin-bottom: 8px;
            line-height: 1.4;
        }

        /* Highlight keyword di judul buku */
        .book-title mark {
            background: #fff3cd;
            color: var(--dark-text);
            border-radius: 3px;
            padding: 0 2px;
        }

        .book-author {
            color: #666;
            font-size: 0.95rem;
            margin-bottom: 8px;
        }

        .book-author i {
            color: var(--primary-main);
            margin-right: 5px;
        }

        .book-size {
            color: #888;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }

        .book-size i {
            color: var(--primary-main);
            margin-right: 5px;
        }

        .btn-detail {
            background: var(--primary-main);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 30px;
            font-weight: 600;
            transition: all 0.3s;
            width: 100%;
            margin-top: auto;
            font-size: 1rem;
            text-decoration: none;
            display: block;
            text-align: center;
        }

        .btn-detail:hover {
            background: var(--accent-gold);
            color: var(--primary-dark);
            transform: scale(1.02);
        }

        /* ========== EMPTY STATE ========== */
        .empty-state {
            background: white;
            border-radius: 20px;
            padding: 60px 40px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            border: 2px dashed #e0e0e0;
        }

        .empty-state i {
            color: var(--primary-main);
            opacity: 0.4;
            margin-bottom: 20px;
        }

        .empty-state h4 {
            color: var(--dark-text);
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #888;
            margin-bottom: 25px;
        }

        .btn-back-catalog {
            background: var(--primary-main);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 30px;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-back-catalog:hover {
            background: var(--primary-dark);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(28, 206, 172, 0.4);
        }

        /* ========== SARAN PENCARIAN ========== */
        .saran-box {
            background: white;
            border-radius: 15px;
            padding: 20px 25px;
            margin-bottom: 30px;
            border-left: 4px solid var(--accent-gold);
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .saran-box h6 {
            color: var(--primary-dark);
            font-weight: 600;
            margin-bottom: 10px;
        }

        .saran-box ul {
            margin: 0;
            padding-left: 20px;
            color: #666;
        }

        .saran-box ul li {
            margin-bottom: 4px;
        }

        /* ========== FOOTER ========== */
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

        /* ========== RESPONSIVE ========== */
        @media (max-width: 768px) {
            .search-box input {
                font-size: 1rem;
                padding: 12px 15px;
            }

            .search-box button {
                padding: 12px 18px;
                font-size: 0.95rem;
            }

            .book-img {
                height: 180px;
            }

            .book-title {
                font-size: 1rem;
            }

            .section-title h3 {
                font-size: 1.1rem;
            }

            .filter-bar {
                gap: 8px;
            }
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
                        <i class="fas fa-home"></i> KATALOG
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
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="user/dashboard.php">
                            <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['nama_lengkap']) ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php" style="color: #ff6b6b !important;">
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

<!-- ========== SEARCH SECTION ========== -->
<section class="search-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-7">
                <?php if ($keyword): ?>
                    <h2 class="text-center">
                        <i class="fas fa-search me-2"></i>
                        Hasil untuk: <span>"<?= htmlspecialchars($keyword) ?>"</span>
                    </h2>
                <?php else: ?>
                    <h2 class="text-center">
                        <i class="fas fa-search me-2"></i>
                        Cari Buku Braille
                    </h2>
                <?php endif; ?>

                <form action="cari.php" method="GET" class="mt-3">
                    <!-- Pertahankan filter ukuran saat re-search -->
                    <?php if ($ukuran_filter): ?>
                        <input type="hidden" name="ukuran" value="<?= htmlspecialchars($ukuran_filter) ?>">
                    <?php endif; ?>
                    <div class="search-box d-flex">
                        <input type="search" name="keyword" class="form-control"
                               placeholder="Tulis nama buku atau pengarang..."
                               value="<?= htmlspecialchars($keyword) ?>"
                               aria-label="Cari buku">
                        <button type="submit">
                            <i class="fas fa-search me-2"></i> Cari
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- ========== MAIN CONTENT ========== -->
<div class="container">

    <!-- Filter Ukuran (Wawancara No.6: filter berdasarkan ukuran) -->
    <div class="filter-bar">
        <span class="filter-label">
            <i class="fas fa-filter"></i> Filter Ukuran:
        </span>
        <a href="cari.php?keyword=<?= urlencode($keyword) ?>"
           class="btn-filter <?= $ukuran_filter == '' ? 'active' : '' ?>">
            Semua Ukuran
        </a>
        <a href="cari.php?keyword=<?= urlencode($keyword) ?>&ukuran=Besar"
           class="btn-filter <?= $ukuran_filter == 'Besar' ? 'active' : '' ?>">
            <i class="fas fa-expand me-1"></i> Besar (25.5 × 30.5 cm)
        </a>
        <a href="cari.php?keyword=<?= urlencode($keyword) ?>&ukuran=Kecil"
           class="btn-filter <?= $ukuran_filter == 'Kecil' ? 'active' : '' ?>">
            <i class="fas fa-compress me-1"></i> Kecil (1.5 × 25.5 cm)
        </a>
    </div>

    <!-- Section Title + Jumlah Hasil -->
    <div class="section-title">
        <i class="fas fa-list-ul"></i>
        <h3>
            <?php if ($keyword || $ukuran_filter): ?>
                HASIL PENCARIAN
            <?php else: ?>
                SEMUA BUKU
            <?php endif; ?>
        </h3>
        <span class="hasil-count"><?= $jumlah_hasil ?> buku ditemukan</span>
        <span class="line"></span>
    </div>

    <!-- Saran jika hasil kosong -->
    <?php if ($jumlah_hasil == 0 && ($keyword || $ukuran_filter)): ?>
        <div class="saran-box">
            <h6><i class="fas fa-lightbulb me-2" style="color: var(--accent-gold);"></i>Tips Pencarian</h6>
            <ul>
                <li>Periksa ejaan kata kunci yang Anda masukkan</li>
                <li>Coba kata yang lebih umum, misalnya "Quran" bukan "Al-Qur'an Braille Juz 1"</li>
                <li>Coba hapus filter ukuran untuk memperluas pencarian</li>
                <li>Coba cari berdasarkan nama pengarang</li>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Grid Hasil Buku -->
    <div class="row g-4">
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
        <div class="col-lg-3 col-md-4 col-sm-6">
            <div class="book-card">
                <div class="book-img">
                    <?php if ($row['gambar'] && file_exists('assets/img/' . $row['gambar'])): ?>
                        <img src="assets/img/<?= $row['gambar'] ?>"
                             alt="<?= htmlspecialchars($row['judul']) ?>">
                    <?php else: ?>
                        <i class="fas fa-book"></i>
                    <?php endif; ?>
                </div>
                <div class="book-body">
                    <span class="book-category"><?= $row['kategori'] ?></span>

                    <h5 class="book-title">
                        <?php
                        // Highlight keyword pada judul
                        if ($keyword) {
                            echo preg_replace(
                                '/(' . preg_quote(htmlspecialchars($keyword), '/') . ')/i',
                                '<mark>$1</mark>',
                                htmlspecialchars($row['judul'])
                            );
                        } else {
                            echo htmlspecialchars($row['judul']);
                        }
                        ?>
                    </h5>

                    <p class="book-author">
                        <i class="fas fa-user"></i>
                        <?= htmlspecialchars($row['pengarang']) ?>
                    </p>
                    <p class="book-size">
                        <i class="fas fa-ruler"></i>
                        <?= $row['ukuran'] == 'Besar' ? '25.5 × 30.5 cm (Besar)' : '1.5 × 25.5 cm (Kecil)' ?>
                    </p>

                    <a href="detail.php?id=<?= $row['id'] ?>" class="btn-detail">
                        <i class="fas fa-info-circle me-2"></i> DETAIL
                    </a>
                </div>
            </div>
        </div>
        <?php endwhile; ?>

        <!-- Empty State -->
        <?php if ($jumlah_hasil == 0): ?>
        <div class="col-12">
            <div class="empty-state">
                <i class="fas fa-search fa-5x d-block mb-4"></i>
                <?php if ($keyword): ?>
                    <h4>Buku "<strong><?= htmlspecialchars($keyword) ?></strong>" tidak ditemukan</h4>
                    <p>
                        Tidak ada buku yang cocok dengan pencarian Anda
                        <?= $ukuran_filter ? ' dengan ukuran ' . $ukuran_filter : '' ?>.
                    </p>
                <?php else: ?>
                    <h4>Belum ada buku dalam katalog</h4>
                    <p>Silakan hubungi admin untuk menambahkan data buku.</p>
                <?php endif; ?>
                <a href="index.php" class="btn-back-catalog">
                    <i class="fas fa-arrow-left me-2"></i> Kembali ke Katalog
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Tombol kembali ke katalog (jika ada hasil) -->
    <?php if ($jumlah_hasil > 0): ?>
    <div class="text-center mt-5">
        <a href="index.php" class="btn-back-catalog">
            <i class="fas fa-th-large me-2"></i> Lihat Semua Katalog
        </a>
    </div>
    <?php endif; ?>

</div>

<!-- ========== FOOTER ========== -->
<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="col-md-5 mb-4 mb-md-0">
                <h5>
                    <i class="fas fa-book-open me-2"></i>
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
                    <li class="mb-2"><a href="index.php"><i class="fas fa-chevron-right me-2" style="font-size: 12px;"></i>Home</a></li>
                    <li class="mb-2"><a href="#"><i class="fas fa-chevron-right me-2" style="font-size: 12px;"></i>About Us</a></li>
                    <li class="mb-2"><a href="#"><i class="fas fa-chevron-right me-2" style="font-size: 12px;"></i>Contact</a></li>
                    <li class="mb-2"><a href="login.php"><i class="fas fa-chevron-right me-2" style="font-size: 12px;"></i>Login</a></li>
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