<?php
include 'config/database.php';
session_start();

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: transaksi.php");
    exit;
}

if ($_SESSION['role'] !== 'admin') {
    // Jika bukan admin, arahkan kembali ke dashboard dengan pesan error
    header("Location: dashboard.php?error=akses_ditolak");
    exit;
}

$id = $_GET['id'];

// 1. Ambil detail transaksi sebelum dihapus
$query = mysqli_query($conn, "SELECT * FROM transaksi_barang WHERE id_transaksi = '$id'");
$t = mysqli_fetch_assoc($query);

if ($t) {
    $id_barang = $t['id_barang'];
    $jumlah = $t['jumlah'];
    $jenis = trim(strtolower($t['jenis']));

    // 2. Logika Kembalikan Stok:
    // Jika dulu barang MASUK dihapus -> maka stok harus DIKURANGI
    // Jika dulu barang KELUAR dihapus -> maka stok harus DITAMBAH
    if ($jenis == 'masuk') {
        mysqli_query($conn, "UPDATE barang SET stok_awal = stok_awal - $jumlah WHERE id_barang = '$id_barang'");
    } else {
        mysqli_query($conn, "UPDATE barang SET stok_awal = stok_awal + $jumlah WHERE id_barang = '$id_barang'");
    }

    // 3. Hapus transaksi
    mysqli_query($conn, "DELETE FROM transaksi_barang WHERE id_transaksi = '$id'");
}

header("Location: transaksi.php?pesan=hapus_berhasil");
exit;