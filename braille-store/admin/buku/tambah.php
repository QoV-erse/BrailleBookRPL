<?php
require_once '../../includes/session.php';
require_once '../../includes/config.php';

// Proses jika form disubmit
if (isset($_POST['simpan'])) {
    // Ambil data dari form
    $judul      = mysqli_real_escape_string($conn, $_POST['judul']);
    $pengarang  = mysqli_real_escape_string($conn, $_POST['pengarang']);
    $penerbit   = mysqli_real_escape_string($conn, $_POST['penerbit']);
    $jml_halaman = (int)$_POST['jml_halaman'];
    $ukuran     = $_POST['ukuran']; // Besar atau Kecil
    $jml_lembaran = (int)$_POST['jml_lembaran'];
    $kategori   = $_POST['kategori'];
    $sinopsis   = mysqli_real_escape_string($conn, $_POST['sinopsis']);
    
    // Proses Upload Gambar Sederhana
    $gambar = 'default.jpg'; // Default jika tidak upload
    if ($_FILES['gambar']['error'] === 0) {
        $nama_file = time() . '_' . $_FILES['gambar']['name'];
        $tmp_file = $_FILES['gambar']['tmp_name'];
        $target_dir = '../../assets/img/';
        
        // Buat folder img jika belum ada
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        if (move_uploaded_file($tmp_file, $target_dir . $nama_file)) {
            $gambar = $nama_file;
        }
    }
    
    // Query Insert
    $query = "INSERT INTO buku (judul, pengarang, penerbit, jml_halaman, ukuran, jml_lembaran, kategori, sinopsis, gambar) 
              VALUES ('$judul', '$pengarang', '$penerbit', $jml_halaman, '$ukuran', $jml_lembaran, '$kategori', '$sinopsis', '$gambar')";
    
    if (mysqli_query($conn, $query)) {
        header("Location: index.php?status=sukses");
        exit;
    } else {
        $error = "Gagal menyimpan: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Buku Braille</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2><i class="fas fa-plus-circle"></i> Tambah Data Buku Baru</h2>
        <hr>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label>Judul Buku *</label>
                    <input type="text" name="judul" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label>Pengarang *</label>
                    <input type="text" name="pengarang" class="form-control" required>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label>Penerbit</label>
                    <input type="text" name="penerbit" class="form-control" value="Yayasan Raudlatul Makfufin">
                </div>
                <div class="col-md-3 mb-3">
                    <label>Jumlah Halaman</label>
                    <input type="number" name="jml_halaman" class="form-control" min="1">
                </div>
                <div class="col-md-3 mb-3">
                    <label>Jumlah Lembaran Braille</label>
                    <input type="number" name="jml_lembaran" class="form-control" min="1">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label>Ukuran Layout Buku *</label>
                    <select name="ukuran" class="form-select" required>
                        <option value="">-- Pilih Ukuran --</option>
                        <option value="Besar">Besar (25.5 x 30.5 cm)</option>
                        <option value="Kecil">Kecil (1.5 x 25.5 cm)</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label>Kategori *</label>
                    <select name="kategori" class="form-select" required>
                        <option value="">-- Pilih Kategori --</option>
                        <option value="Al-Quran">Al-Qur'an</option>
                        <option value="Islam">Buku Islam</option>
                        <option value="Panduan">Buku Panduan</option>
                        <option value="Solat">Buku Tentang Solat</option>
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <label>Sinopsis / Deskripsi</label>
                <textarea name="sinopsis" class="form-control" rows="4"></textarea>
            </div>
            <div class="mb-3">
                <label>Gambar Sampul</label>
                <input type="file" name="gambar" class="form-control" accept="image/*">
            </div>
            <button type="submit" name="simpan" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Buku</button>
            <a href="index.php" class="btn btn-secondary">Batal</a>
        </form>
    </div>
</body>
</html>