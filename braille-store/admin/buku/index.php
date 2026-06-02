<?php
require_once '../../includes/session.php';
require_once '../../includes/config.php';

// Cek role admin
if ($_SESSION['role'] != 'admin') {
    header("Location: ../../login.php");
    exit;
}

// Ambil semua data buku dari database, urutkan berdasarkan judul (Sesuai Wawancara No.7)
$query = "SELECT * FROM buku ORDER BY judul ASC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Buku Braille - Admin</title>

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
            --light-bg: #f4f6f9;
            --dark-text: #2c3e50;
            --sidebar-width: 280px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-bg);
            color: var(--dark-text);
        }

        /* ========== SIDEBAR (identik dengan dashboard.php) ========== */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--primary-main) 0%, var(--primary-dark) 100%);
            box-shadow: 4px 0 20px rgba(28, 206, 172, 0.15);
            z-index: 1000;
            transition: all 0.3s;
            overflow-y: auto;
        }

        .sidebar-brand {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.2);
            text-align: center;
        }

        .sidebar-brand i {
            font-size: 40px;
            color: var(--accent-gold);
        }

        .sidebar-brand h4 {
            color: white;
            margin-top: 10px;
            font-weight: bold;
        }

        .sidebar-nav {
            padding: 20px 0;
        }

        .sidebar-nav .nav-link {
            color: rgba(255,255,255,0.85);
            padding: 15px 25px;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s;
            border-left: 4px solid transparent;
            display: flex;
            align-items: center;
        }

        .sidebar-nav .nav-link:hover {
            color: white;
            background: rgba(255,255,255,0.15);
            border-left-color: var(--accent-gold);
        }

        .sidebar-nav .nav-link.active {
            color: white;
            background: rgba(255,255,255,0.2);
            border-left-color: var(--accent-gold);
        }

        .sidebar-nav .nav-link i {
            width: 25px;
            margin-right: 10px;
            color: var(--accent-gold);
        }

        .sidebar-footer {
            position: absolute;
            bottom: 0;
            width: 100%;
            padding: 20px;
            border-top: 1px solid rgba(255,255,255,0.2);
            color: rgba(255,255,255,0.8);
        }

        /* ========== MAIN CONTENT ========== */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            padding: 25px 30px;
        }

        /* ========== TOP BAR ========== */
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e0e0e0;
        }

        .top-bar h2 {
            color: var(--primary-dark);
            margin: 0;
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .admin-avatar {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, var(--primary-main), var(--primary-dark));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            font-weight: bold;
        }

        /* ========== PAGE HEADER CARD ========== */
        .page-header-card {
            background: white;
            border-radius: 15px;
            padding: 20px 25px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            border: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .page-header-card h4 {
            color: var(--primary-dark);
            font-weight: bold;
            margin: 0;
        }

        .page-header-card h4 i {
            color: var(--accent-gold);
            margin-right: 8px;
        }

        .btn-tambah {
            background: var(--primary-main);
            color: white;
            border: none;
            padding: 10px 22px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(28, 206, 172, 0.3);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-tambah:hover {
            background: var(--primary-dark);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(28, 206, 172, 0.4);
        }

        /* ========== ALERT ========== */
        .alert-custom {
            border-radius: 12px;
            padding: 14px 20px;
            border: none;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success-custom {
            background: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #4caf50;
        }

        .alert-danger-custom {
            background: #ffebee;
            color: #c62828;
            border-left: 4px solid #f44336;
        }

        /* ========== TABLE CARD ========== */
        .table-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            border: 1px solid #eee;
        }

        .table thead tr th {
            background: linear-gradient(135deg, var(--primary-main), var(--primary-dark));
            color: white;
            font-weight: 600;
            padding: 14px 12px;
            border: none;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        .table tbody tr {
            transition: background 0.2s;
            border-bottom: 1px solid #f5f5f5;
        }

        .table tbody tr:hover {
            background: #f0fdf9;
        }

        .table tbody td {
            padding: 14px 12px;
            vertical-align: middle;
        }

        /* ========== BADGES ========== */
        .badge-kategori {
            background: linear-gradient(135deg, var(--primary-main), var(--primary-dark));
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .badge-besar {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .badge-kecil {
            background: #fff3e0;
            color: #e65100;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        /* ========== GAMBAR THUMBNAIL ========== */
        .img-thumb {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 10px;
            box-shadow: 0 3px 8px rgba(0,0,0,0.1);
        }

        .img-placeholder {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--primary-main), var(--primary-dark));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
        }

        /* ========== ACTION BUTTONS ========== */
        .btn-edit {
            background: #fff8e1;
            color: #856404;
            border: 1px solid #ffc107;
            padding: 7px 14px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-edit:hover {
            background: #ffc107;
            color: #333;
            transform: translateY(-2px);
        }

        .btn-delete {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #f44336;
            padding: 7px 14px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-delete:hover {
            background: #f44336;
            color: white;
            transform: translateY(-2px);
        }

        /* ========== EMPTY STATE ========== */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-state i {
            font-size: 60px;
            color: #ccc;
            margin-bottom: 20px;
        }

        .empty-state h5 {
            color: #999;
            margin-bottom: 8px;
        }

        /* ========== FOOTER ========== */
        .footer-admin {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            text-align: center;
            color: #aaa;
            font-size: 0.875rem;
        }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
            .page-header-card {
                flex-direction: column;
                align-items: flex-start;
            }
            .btn-tambah {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

<!-- ========== SIDEBAR ========== -->
<div class="sidebar" id="sidebar">
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
            <a class="nav-link active" href="index.php">
                <i class="fas fa-book"></i> Kelola Buku
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="../pesanan/index.php">
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
        <small>
            <i class="far fa-clock me-1"></i>
            <?= date('d M Y, H:i') ?> WIB
        </small>
    </div>
</div>

<!-- ========== MAIN CONTENT ========== -->
<div class="main-content">

    <!-- Top Bar -->
    <div class="top-bar">
        <div>
            <h2>
                <i class="fas fa-book me-2" style="color: var(--accent-gold);"></i>
                Kelola Buku Braille
            </h2>
            <p class="text-muted mb-0">Daftar seluruh buku dalam katalog</p>
        </div>
        <div class="admin-profile">
            <div class="text-end">
                <strong><?= htmlspecialchars($_SESSION['nama_lengkap']) ?></strong><br>
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

    <!-- Page Header Card -->
    <div class="page-header-card">
        <h4>
            <i class="fas fa-list"></i>
            Daftar Katalog Buku (<?= mysqli_num_rows($result) ?> buku)
        </h4>
        <a href="tambah.php" class="btn-tambah">
            <i class="fas fa-plus-circle"></i> Tambah Buku Baru
        </a>
    </div>

    <!-- Notifikasi -->
    <?php if (isset($_GET['status'])): ?>
        <?php if ($_GET['status'] == 'sukses'): ?>
            <div class="alert-custom alert-success-custom">
                <i class="fas fa-check-circle fa-lg"></i>
                <span><strong>Berhasil!</strong> Data buku berhasil disimpan / diupdate.</span>
            </div>
        <?php elseif ($_GET['status'] == 'hapus' && isset($_GET['msg']) && $_GET['msg'] == 'sukses'): ?>
            <div class="alert-custom alert-success-custom">
                <i class="fas fa-trash-alt fa-lg"></i>
                <span><strong>Berhasil!</strong> Data buku telah dihapus dari database.</span>
            </div>
        <?php elseif ($_GET['status'] == 'error'): ?>
            <div class="alert-custom alert-danger-custom">
                <i class="fas fa-exclamation-triangle fa-lg"></i>
                <span><strong>Gagal!</strong>
                    <?php
                    $msg = $_GET['msg'] ?? '';
                    if ($msg == 'id_tidak_valid') echo 'ID buku tidak valid.';
                    elseif ($msg == 'buku_tidak_ditemukan') echo 'Buku tidak ditemukan.';
                    elseif ($msg == 'gagal_hapus') echo 'Gagal menghapus buku. Coba lagi.';
                    else echo 'Terjadi kesalahan.';
                    ?>
                </span>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Tabel Buku -->
    <div class="table-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th width="10%">Gambar</th>
                        <th width="28%">Judul Buku</th>
                        <th width="15%">Pengarang</th>
                        <th width="13%">Kategori</th>
                        <th width="11%">Ukuran</th>
                        <th width="18%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><strong><?= $no++ ?></strong></td>
                        <td>
                            <?php if ($row['gambar'] && file_exists('../../assets/img/' . $row['gambar'])): ?>
                                <img src="../../assets/img/<?= $row['gambar'] ?>"
                                     alt="<?= htmlspecialchars($row['judul']) ?>"
                                     class="img-thumb">
                            <?php else: ?>
                                <div class="img-placeholder">
                                    <i class="fas fa-book"></i>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($row['judul']) ?></strong><br>
                            <small class="text-muted">
                                <i class="fas fa-building me-1"></i>
                                <?= htmlspecialchars($row['penerbit']) ?>
                            </small>
                        </td>
                        <td>
                            <i class="fas fa-user-edit me-1" style="color: var(--primary-main);"></i>
                            <?= htmlspecialchars($row['pengarang']) ?>
                        </td>
                        <td>
                            <span class="badge-kategori"><?= $row['kategori'] ?></span>
                        </td>
                        <td>
                            <?php if ($row['ukuran'] == 'Besar'): ?>
                                <span class="badge-besar">
                                    <i class="fas fa-expand me-1"></i> Besar
                                </span>
                            <?php else: ?>
                                <span class="badge-kecil">
                                    <i class="fas fa-compress me-1"></i> Kecil
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="edit.php?id=<?= $row['id'] ?>" class="btn-edit">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="hapus.php?id=<?= $row['id'] ?>"
                               class="btn-delete"
                               onclick="return confirm('Yakin ingin menghapus buku ini?\n\nData yang sudah dihapus TIDAK BISA dikembalikan.')">
                                <i class="fas fa-trash"></i> Hapus
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>

                    <?php if (mysqli_num_rows($result) == 0): ?>
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <i class="fas fa-box-open d-block mb-3"></i>
                                <h5>Belum Ada Data Buku</h5>
                                <p class="text-muted">Klik tombol "Tambah Buku Baru" untuk mulai mengisi katalog.</p>
                                <a href="tambah.php" class="btn-tambah d-inline-flex mt-2">
                                    <i class="fas fa-plus-circle"></i> Tambah Buku Baru
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer-admin">
        <i class="fas fa-book-open me-2" style="color: var(--primary-main);"></i>
        © 2026 Yayasan Raudlatul Makfufin — Sistem Informasi Katalog Buku Braille
    </div>

</div><!-- end main-content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Toggle sidebar di mobile
    document.addEventListener('DOMContentLoaded', function () {
        const toggleBtn = document.getElementById('sidebarToggle');
        const sidebar   = document.getElementById('sidebar');
        if (toggleBtn && sidebar) {
            toggleBtn.addEventListener('click', () => sidebar.classList.toggle('show'));
        }
    });
</script>
</body>
</html>