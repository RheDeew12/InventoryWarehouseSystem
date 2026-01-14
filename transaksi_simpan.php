<?php
include 'config/database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

if ($_SESSION['role'] !== 'admin') {
    // Jika bukan admin, arahkan kembali ke dashboard dengan pesan error
    header("Location: dashboard.php?error=akses_ditolak");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_barang  = mysqli_real_escape_string($conn, $_POST['id_barang']);
    $jenis      = mysqli_real_escape_string($conn, $_POST['jenis']);
    $jumlah     = intval($_POST['jumlah']);
    $tanggal    = mysqli_real_escape_string($conn, $_POST['tanggal']);
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);

    // 1. MULAI TRANSAKSI DATABASE (Agar data konsisten)
    mysqli_begin_transaction($conn);

    try {
        // 2. Simpan ke tabel transaksi_barang
        $query_transaksi = "INSERT INTO transaksi_barang (id_barang, jenis, jumlah, tanggal, keterangan) 
                            VALUES ('$id_barang', '$jenis', '$jumlah', '$tanggal', '$keterangan')";
        mysqli_query($conn, $query_transaksi);

        // 3. UPDATE STOK DI TABEL BARANG (Master Barang)
        if ($jenis == 'masuk') {
            $query_update = "UPDATE barang SET stok_awal = stok_awal + $jumlah WHERE id_barang = '$id_barang'";
        } else {
            // Cek dulu apakah stok cukup jika barang keluar
            $res_cek = mysqli_query($conn, "SELECT stok_awal FROM barang WHERE id_barang = '$id_barang'");
            $data_cek = mysqli_fetch_assoc($res_cek);
            
            if ($data_cek['stok_awal'] < $jumlah) {
                throw new Exception("Stok tidak mencukupi untuk transaksi keluar!");
            }
            
            $query_update = "UPDATE barang SET stok_awal = stok_awal - $jumlah WHERE id_barang = '$id_barang'";
        }
        
        mysqli_query($conn, $query_update);

        // 4. Jika semua berhasil, COMMIT
        mysqli_commit($conn);
        header("Location: transaksi.php?status=sukses");

    } catch (Exception $e) {
        // 5. Jika ada error, ROLLBACK (Batalkan semua perubahan)
        mysqli_rollback($conn);
        echo "<script>alert('Error: " . $e->getMessage() . "'); window.location.href='transaksi.php';</script>";
    }
}
?>