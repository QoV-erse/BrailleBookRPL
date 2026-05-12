<?php
// config.php - Koneksi Database
// File ini akan di-include di semua halaman yang butuh database

$host = "localhost";
$user = "root";           // Default XAMPP/Laragon
$pass = "";               // Kosongkan jika default, isi jika ada password
$db   = "bd_braille_print"; // Sesuai nama database Anda

// Membuat koneksi
$conn = mysqli_connect($host, $user, $pass, $db);

// Cek koneksi
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Set timezone Indonesia
date_default_timezone_set('Asia/Jakarta');

// Optional: Fungsi query aman (anti SQL Injection dasar)
function query($query) {
    global $conn;
    $result = mysqli_query($conn, $query);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    return $rows;
}
?>