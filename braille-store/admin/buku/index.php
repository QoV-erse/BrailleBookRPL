<?php
require_once '../../includes/session.php'; // Cek login admin
require_once '../../includes/config.php';  // Koneksi database

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
            --white: #ffffff;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
            font-size: 1.3rem;
            font-weight: bold;
            color: white !important;
        }
        
        .navbar-brand i {
            color: var(--accent-gold);
            margin-right: 10px;
        }
        
        .navbar .text-white {
            color: rgba(255,255,255,0.95) !important;
        }
        
        .navbar .btn-outline-light {
            border-color: rgba(255,255,255,0.5);
            color: white;
        }
        
        .navbar .btn-outline-light:hover {
            background: rgba(255,255,255,0.15);
            border-color: var(--accent-gold);
            color: var(--accent-gold);
        }
        
        /* ========== TOMBOL KEMBALI NAVBAR ========== */
.btn-kembali-nav {
    background: rgba(255,255,255,0.15);
    color: white;
    border: 1px solid rgba(255,255,255,0.3);
    padding: 8px 18px;
    border-radius: 25px;
    font-weight: 500;
    font-size: 0.9rem;
    text-decoration: none;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
}

.btn-kembali-nav:hover {
    background: rgba(255,255,255,0.25);
    border-color: var(--accent-gold);
    color: var(--accent-gold);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.15);
}

        /* ========== PAGE HEADER ========== */
        .page-header {
            background: white;
            border-radius: 20px;
            padding: 25px 30px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            border: 1px solid #eee;
        }
        
        .page-title {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-dark);
            margin: 0;
        }
        
        .page-title i {
            color: var(--primary-main);
            margin-right: 10px;
        }
        
        .btn-tambah {
            background: var(--primary-main);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 5px 15px rgba(28, 206, 172, 0.3);
        }
        
        .btn-tambah:hover {
            background: var(--primary-dark);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(28, 206, 172, 0.4);
        }
        
        .btn-kembali {
            background: white;
            color: var(--primary-dark);
            border: 2px solid var(--primary-main);
            padding: 12px 25px;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-kembali:hover {
            background: var(--primary-main);
            color: white;
        }
        
        /* ========== TABLE CARD ========== */
        .table-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            border: 1px solid #eee;
        }
        
        .table-header-custom {
            background: linear-gradient(135deg, var(--primary-main) 0%, var(--primary-dark) 100%);
            color: white;
        }
        
        .table-header-custom th {
            font-weight: 600;
            padding: 15px 12px;
            border: none;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .table tbody tr {
            transition: all 0.2s;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .table tbody tr:hover {
            background-color: #f0fdf9;
        }
        
        .table tbody td {
            padding: 15px 12px;
            vertical-align: middle;
        }
        
        /* ========== BADGES ========== */
        .badge-kategori {
            background: linear-gradient(135deg, var(--primary-main), var(--primary-dark));
            color: white;
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.85rem;
        }
        
        .badge-ukuran-besar {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.85rem;
        }
        
        .badge-ukuran-kecil {
            background: #fff3e0;
            color: #e65100;
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.85rem;
        }
        
        /* ========== BUTTONS ========== */
        .btn-edit {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffc107;
            padding: 8px 15px;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-edit:hover {
            background: #ffc107;
            color: #000;
            transform: translateY(-2px);
        }
        
        .btn-delete {
            background: #fce4ec;
            color: #c62828;
            border: 1px solid #f44336;
            padding: 8px 15px;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-delete:hover {
            background: #f44336;
            color: white;
            transform: translateY(-2px);
        }
        
        /* ========== GAMBAR THUMBNAIL ========== */
        .img-thumb {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 12px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .img-placeholder {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #e0e0e0, #bdbdbd);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 28px;
        }
        
        /* ========== ALERT ========== */
        .alert-custom {
            border-radius: 15px;
            padding: 15px 20px;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 20px;
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
        
        /* ========== EMPTY STATE ========== */
        .empty-state {
            text-align: center;
            padding: 50px 20px;
        }
        
        .empty-state i {
            font-size: 64px;
            color: #ccc;
            margin-bottom: 20px;
        }
        
        .empty-state h4 {
            color: #999;
            margin-bottom: 10px;
        }
        
        /* ========== FOOTER ========== */
        .footer-admin {
            background: white;
            border-radius: 20px;
            padding: 20px;
            margin-top: 25px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            border: 1px solid #eee;
            color: #999;
            font-size: 0.9rem;
        }
        
        /* ========== RESPONSIVE ========== */
        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                gap: 15px;
            }
            
            .btn-tambah,
            .btn-kembali {
                width: 100%;
                text-align: center;
            }
            
            .table-responsive {
                font-size: 0.85rem;
            }
            
            .img-thumb,
            .img-placeholder {
                width: 50px;
                height: 50px;
            }
        }
    </style>
</head>
<body>

<!-- ========== NAVBAR ========== -->
<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="../dashboard.php">
            <i class="fas fa-book-open"></i> <strong>Admin Braille</strong>
        </a>
        <div class="d-flex align-items-center gap-3">
            <span class="text-white">
                <i class="fas fa-user-circle me-1"></i> <?= $_SESSION['nama_lengkap'] ?>
            </span>
            <a href="../dashboard.php" class="btn-kembali-nav">
                <i class="fas fa-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>
</nav>

<!-- ========== MAIN CONTENT ========== -->
<div class="container mt-4 mb-5">
    
    <!-- Page Header -->
    <div class="page-header d-flex justify-content-between align-items-center flex-wrap">
        <h2 class="page-title">
            <i class="fas fa-list"></i> Daftar Katalog Buku Braille
        </h2>
        <a href="tambah.php" class="btn btn-tambah">
            <i class="fas fa-plus-circle me-2"></i> Tambah Buku Baru
        </a>
    </div>
    
    <!-- Notifikasi -->
    <?php if (isset($_GET['status'])): ?>
        
        <?php if ($_GET['status'] == 'sukses'): ?>
            <div class="alert-custom alert-success-custom alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <strong>Berhasil!</strong> Data buku berhasil disimpan/diupdate.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($_GET['status'] == 'hapus' && $_GET['msg'] == 'sukses'): ?>
            <div class="alert-custom alert-success-custom alert-dismissible fade show" role="alert">
                <i class="fas fa-trash-alt me-2"></i>
                <strong>Berhasil!</strong> Data buku telah dihapus dari database.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($_GET['status'] == 'error'): ?>
            <div class="alert-custom alert-danger-custom alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Gagal!</strong> 
                <?php if ($_GET['msg'] == 'id_tidak_valid'): ?>
                    ID buku tidak valid.
                <?php elseif ($_GET['msg'] == 'buku_tidak_ditemukan'): ?>
                    Buku tidak ditemukan di database.
                <?php elseif ($_GET['msg'] == 'gagal_hapus'): ?>
                    Gagal menghapus buku. Silakan coba lagi.
                <?php else: ?>
                    Terjadi kesalahan.
                <?php endif; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
    <?php endif; ?>
    
    <!-- Tabel Data Buku -->
    <div class="table-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-header-custom">
                    <tr>
                        <th width="5%">No</th>
                        <th width="12%">Gambar</th>
                        <th width="25%">Judul Buku</th>
                        <th width="15%">Pengarang</th>
                        <th width="12%">Kategori</th>
                        <th width="12%">Ukuran</th>
                        <th width="19%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; ?>
                    <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                    <tr>
                        <td>
                            <strong><?= $no++ ?></strong>
                        </td>
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
                            <strong><?= htmlspecialchars($row['judul']) ?></strong>
                        </td>
                        <td>
                            <i class="fas fa-user-edit me-2" style="color: var(--primary-main);"></i>
                            <?= htmlspecialchars($row['pengarang']) ?>
                        </td>
                        <td>
                            <span class="badge-kategori"><?= $row['kategori'] ?></span>
                        </td>
                        <td>
                            <?php if ($row['ukuran'] == 'Besar'): ?>
                                <span class="badge-ukuran-besar">
                                    <i class="fas fa-expand me-1"></i> Besar
                                </span>
                            <?php else: ?>
                                <span class="badge-ukuran-kecil">
                                    <i class="fas fa-compress me-1"></i> Kecil
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-edit me-1">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="hapus.php?id=<?= $row['id'] ?>" 
                               class="btn btn-delete" 
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
                                <i class="fas fa-box-open"></i>
                                <h4>Belum Ada Data Buku</h4>
                                <p class="text-muted">Silakan klik tombol "Tambah Buku Baru" untuk menambahkan katalog.</p>
                                <a href="tambah.php" class="btn btn-tambah mt-3">
                                    <i class="fas fa-plus-circle me-2"></i> Tambah Buku Baru
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
        © 2026 Yayasan Raudlatul Makfufin - Sistem Informasi Katalog Buku Braille
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>