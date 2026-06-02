<?php
session_start();
require_once '../includes/config.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$action  = isset($_GET['action']) ? $_GET['action'] : '';
$buku_id = isset($_GET['buku_id']) ? (int)$_GET['buku_id'] : 0;

if ($action == 'tambah' && $buku_id > 0) {
    // Tambah ke favorit
    $query = "INSERT IGNORE INTO favorit (user_id, buku_id) VALUES ($user_id, $buku_id)";
    mysqli_query($conn, $query);
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
} 
elseif ($action == 'hapus' && $buku_id > 0) {
    // Hapus dari favorit
    $query = "DELETE FROM favorit WHERE user_id = $user_id AND buku_id = $buku_id";
    mysqli_query($conn, $query);
    header("Location: favorit.php");
    exit;
}
else {
    header("Location: ../index.php");
    exit;
}
?>