<?php
session_start();
require_once '../includes/config.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil daftar favorit user
$query = "SELECT b.*, f.created_at as tgl_favorit 
          FROM buku b 
          JOIN favorit f ON b.id = f.buku_id 
          WHERE f.user_id = $user_id 
          ORDER BY f.created_at DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buku Favorit - Braille Catalog</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-main: #1cceac;
            --primary-dark: #15a38a;
            --accent-gold: #FFD700;
        }
        body { background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .navbar { background: linear-gradient(135deg, var(--primary-main), var(--primary-dark)) !important; }
        .card { border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); border: 1px solid #eee; }
        .btn-primary { background: var(--primary-main); border: none; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-danger-custom { background: #e74c3c; color: white; border: none; }
        .btn-danger-custom:hover { background: #c0392b; }
        .empty-state { text-align: center; padding: 50px; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-book-open"></i> Braille Catalog
            </a>
            <div class="text-white">
                <i class="fas fa-user-circle"></i> <?= $_SESSION['nama_lengkap'] ?>
                <a href="../logout.php" class="btn btn-sm btn-outline-light ms-3">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h3><i class="fas fa-heart me-2" style="color: #e74c3c;"></i>Buku Favorit Saya</h3>
        <hr>
        
        <?php if (mysqli_num_rows($result) > 0): ?>
            <div class="row g-4">
                <?php while ($buku = mysqli_fetch_assoc($result)): ?>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="card h-100">
                        <div class="text-center p-3 bg-light">
                            <?php if ($buku['gambar'] && file_exists('../assets/img/' . $buku['gambar'])): ?>
                                <img src="../assets/img/<?= $buku['gambar'] ?>" width="100%" height="180" style="object-fit: cover; border-radius: 10px;">
                            <?php else: ?>
                                <i class="fas fa-book fa-4x" style="color: var(--primary-main);"></i>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <span class="badge" style="background: var(--primary-main);"><?= $buku['kategori'] ?></span>
                            <h6 class="mt-2"><?= htmlspecialchars($buku['judul']) ?></h6>
                            <p class="text-muted small">
                                <i class="fas fa-user"></i> <?= htmlspecialchars($buku['pengarang']) ?><br>
                                <i class="fas fa-calendar"></i> <?= date('d M Y', strtotime($buku['tgl_favorit'])) ?>
                            </p>
                        </div>
                        <div class="card-footer bg-white border-0">
                            <a href="../detail.php?id=<?= $buku['id'] ?>" class="btn btn-primary btn-sm w-100 mb-2">
                                <i class="fas fa-info-circle"></i> Detail
                            </a>
                            <a href="db_favorit.php?action=hapus&buku_id=<?= $buku['id'] ?>" 
                               class="btn btn-danger-custom btn-sm w-100"
                               onclick="return confirm('Hapus dari favorit?')">
                                <i class="fas fa-trash"></i> Hapus
                            </a>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-heart-broken fa-5x text-muted mb-3"></i>
                <h4>Belum ada buku favorit</h4>
                <p>Klik tombol hati di halaman detail buku untuk menambahkannya.</p>
                <a href="../index.php" class="btn btn-primary">Jelajahi Katalog</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>