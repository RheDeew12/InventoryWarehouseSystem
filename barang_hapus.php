<?php
include 'config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SESSION['role'] !== 'admin') {
    // Jika bukan admin, arahkan kembali ke dashboard dengan pesan error
    header("Location: dashboard.php?error=akses_ditolak");
    exit;
}

// Cek login
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

if (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    
    // Proses hapus
    $query = "DELETE FROM barang WHERE id_barang = '$id'";
    
    if (mysqli_query($conn, $query)) {
        // Redirect dengan status sukses
        header("Location: barang.php?status=hapus_berhasil");
    } else {
        echo "Gagal menghapus: " . mysqli_error($conn);
    }
} else {
    header("Location: barang.php");
}
exit;