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

// Jika tidak ada ID, kembali ke list
if ($id == 0) {
    header("Location: index.php?status=error&msg=id_tidak_valid");
    exit;
}

// Ambil data buku untuk hapus gambar
$query = "SELECT gambar FROM buku WHERE id = $id";
$result = mysqli_query($conn, $query);
$buku = mysqli_fetch_assoc($result);

// Jika buku tidak ditemukan
if (!$buku) {
    header("Location: index.php?status=error&msg=buku_tidak_ditemukan");
    exit;
}

// Hapus file gambar jika ada (kecuali default)
if ($buku['gambar'] && $buku['gambar'] != 'default.jpg') {
    $path_gambar = '../../assets/img/' . $buku['gambar'];
    if (file_exists($path_gambar)) {
        unlink($path_gambar);
    }
}

// Hapus data buku dari database
$query_delete = "DELETE FROM buku WHERE id = $id";

if (mysqli_query($conn, $query_delete)) {
    // Berhasil dihapus
    header("Location: index.php?status=hapus&msg=sukses");
    exit;
} else {
    // Gagal dihapus
    header("Location: index.php?status=error&msg=gagal_hapus");
    exit;
}
?>