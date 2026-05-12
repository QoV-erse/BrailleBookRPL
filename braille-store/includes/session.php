<?php
// session.php - Cek status login
// File ini di-include di SETIAP halaman admin

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}
?>