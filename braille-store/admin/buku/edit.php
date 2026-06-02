<?php
require_once '../../includes/session.php';
require_once '../../includes/config.php';

// Cek role admin
if ($_SESSION['role'] != 'admin') {
    header("Location: ../../login.php");
    exit;
}

// Ambil ID buku dari URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Ambil data buku berdasarkan ID
$query = "SELECT * FROM buku WHERE id = $id";
$result = mysqli_query($conn, $query);
$buku = mysqli_fetch_assoc($result);

// Jika buku tidak ditemukan
if (!$buku) {
    header("Location: index.php?status=notfound");
    exit;
}

// Proses update data
if (isset($_POST['update'])) {
    $judul        = mysqli_real_escape_string($conn, $_POST['judul']);
    $pengarang    = mysqli_real_escape_string($conn, $_POST['pengarang']);
    $penerbit     = mysqli_real_escape_string($conn, $_POST['penerbit']);
    $jml_halaman  = (int)$_POST['jml_halaman'];
    $ukuran       = $_POST['ukuran'];
    $jml_lembaran = (int)$_POST['jml_lembaran'];
    $kategori     = $_POST['kategori'];
    $sinopsis     = mysqli_real_escape_string($conn, $_POST['sinopsis']);
    $stok         = (int)$_POST['stok_tersedia'];
    
    // Cek apakah ada upload gambar baru
    $gambar_sql = '';
    if ($_FILES['gambar']['error'] === 0 && $_FILES['gambar']['size'] > 0) {
        $nama_file = time() . '_' . $_FILES['gambar']['name'];
        $tmp_file  = $_FILES['gambar']['tmp_name'];
        $target    = '../../assets/img/' . $nama_file;
        
        // Buat folder jika belum ada
        if (!is_dir('../../assets/img/')) {
            mkdir('../../assets/img/', 0777, true);
        }
        
        if (move_uploaded_file($tmp_file, $target)) {
            // Hapus gambar lama jika ada
            if ($buku['gambar'] && $buku['gambar'] != 'default.jpg' && file_exists('../../assets/img/' . $buku['gambar'])) {
                unlink('../../assets/img/' . $buku['gambar']);
            }
            $gambar_sql = ", gambar = '$nama_file'";
        }
    }
    
    // Query Update
    $query_update = "UPDATE buku SET 
        judul = '$judul',
        pengarang = '$pengarang',
        penerbit = '$penerbit',
        jml_halaman = $jml_halaman,
        ukuran = '$ukuran',
        jml_lembaran = $jml_lembaran,
        kategori = '$kategori',
        sinopsis = '$sinopsis',
        stok_tersedia = $stok
        $gambar_sql
        WHERE id = $id";
    
    if (mysqli_query($conn, $query_update)) {
        header("Location: index.php?status=sukses");
        exit;
    } else {
        $error = "Gagal update: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Buku - Admin Braille</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-main: #1cceac;
            --primary-dark: #15a38a;
        }
        body { background-color: #f4f6f9; }
        .card { border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
        .btn-primary { background: var(--primary-main); border: none; }
        .btn-primary:hover { background: var(--primary-dark); }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body p-4">
                        <h3><i class="fas fa-edit me-2" style="color: var(--primary-main);"></i>Edit Buku Braille</h3>
                        <hr>
                        
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Judul Buku *</label>
                                    <input type="text" name="judul" class="form-control" 
                                           value="<?= htmlspecialchars($buku['judul']) ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Pengarang *</label>
                                    <input type="text" name="pengarang" class="form-control" 
                                           value="<?= htmlspecialchars($buku['pengarang']) ?>" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Penerbit</label>
                                    <input type="text" name="penerbit" class="form-control" 
                                           value="<?= htmlspecialchars($buku['penerbit']) ?>">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Jumlah Halaman</label>
                                    <input type="number" name="jml_halaman" class="form-control" 
                                           value="<?= $buku['jml_halaman'] ?>" min="1">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Jumlah Lembaran Braille</label>
                                    <input type="number" name="jml_lembaran" class="form-control" 
                                           value="<?= $buku['jml_lembaran'] ?>" min="1">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Ukuran Layout *</label>
                                    <select name="ukuran" class="form-select" required>
                                        <option value="Besar" <?= $buku['ukuran'] == 'Besar' ? 'selected' : '' ?>>Besar (25.5 x 30.5 cm)</option>
                                        <option value="Kecil" <?= $buku['ukuran'] == 'Kecil' ? 'selected' : '' ?>>Kecil (1.5 x 25.5 cm)</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Kategori *</label>
                                    <select name="kategori" class="form-select" required>
                                        <option value="Al-Quran" <?= $buku['kategori'] == 'Al-Quran' ? 'selected' : '' ?>>Al-Qur'an</option>
                                        <option value="Islam" <?= $buku['kategori'] == 'Islam' ? 'selected' : '' ?>>Buku Islam</option>
                                        <option value="Panduan" <?= $buku['kategori'] == 'Panduan' ? 'selected' : '' ?>>Buku Panduan</option>
                                        <option value="Solat" <?= $buku['kategori'] == 'Solat' ? 'selected' : '' ?>>Buku Tentang Solat</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Sinopsis / Deskripsi</label>
                                <textarea name="sinopsis" class="form-control" rows="4"><?= htmlspecialchars($buku['sinopsis']) ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Stok Tersedia</label>
                                    <input type="number" name="stok_tersedia" class="form-control" 
                                           value="<?= $buku['stok_tersedia'] ?>" min="0">
                                    <small class="text-muted">Default 0 untuk sistem pre-order</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Gambar Sampul (Biarkan kosong jika tidak diganti)</label>
                                    <input type="file" name="gambar" class="form-control" accept="image/*">
                                    <?php if ($buku['gambar'] && $buku['gambar'] != 'default.jpg'): ?>
                                        <div class="mt-2">
                                            <img src="../../assets/img/<?= $buku['gambar'] ?>" 
                                                 width="100" height="100" style="object-fit: cover; border-radius: 10px;">
                                            <span class="ms-2 text-muted">Gambar saat ini</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Batal
                                </a>
                                <button type="submit" name="update" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Buku
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>