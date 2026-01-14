<?php
session_start();
include 'config/database.php';

// 1. Proteksi Halaman
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php?error=akses_ditolak");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_transaksi = mysqli_real_escape_string($conn, $_POST['id_transaksi']);
    $jenis_baru   = mysqli_real_escape_string($conn, $_POST['jenis']);
    $jumlah_baru  = intval($_POST['jumlah']);
    $tanggal      = mysqli_real_escape_string($conn, $_POST['tanggal']);
    $keterangan   = mysqli_real_escape_string($conn, $_POST['keterangan']);

    // 2. MULAI TRANSAKSI DATABASE (Penting untuk konsistensi)
    mysqli_begin_transaction($conn);

    try {
        // 3. Ambil data lama transaksi sebelum diubah
        $query_lama = mysqli_query($conn, "SELECT id_barang, jenis, jumlah FROM transaksi_barang WHERE id_transaksi = '$id_transaksi'");
        $t_lama = mysqli_fetch_assoc($query_lama);
        
        if (!$t_lama) throw new Exception("Data transaksi tidak ditemukan.");
        
        $id_barang = $t_lama['id_barang'];
        $jenis_lama = trim(strtolower($t_lama['jenis']));
        $jumlah_lama = intval($t_lama['jumlah']);

        // 4. BALIKKAN (REVERSE) stok lama di tabel Master Barang
        if ($jenis_lama == 'masuk') {
            mysqli_query($conn, "UPDATE barang SET stok_awal = stok_awal - $jumlah_lama WHERE id_barang = '$id_barang'");
        } else {
            mysqli_query($conn, "UPDATE barang SET stok_awal = stok_awal + $jumlah_lama WHERE id_barang = '$id_barang'");
        }

        // 5. TERAPKAN (APPLY) stok baru hasil editan ke tabel Master Barang
        if ($jenis_baru == 'masuk') {
            mysqli_query($conn, "UPDATE barang SET stok_awal = stok_awal + $jumlah_baru WHERE id_barang = '$id_barang'");
        } else {
            // Cek apakah stok cukup setelah perubahan
            $res_cek = mysqli_query($conn, "SELECT stok_awal FROM barang WHERE id_barang = '$id_barang'");
            $data_cek = mysqli_fetch_assoc($res_cek);
            if ($data_cek['stok_awal'] < $jumlah_baru) {
                throw new Exception("Stok tidak cukup jika diubah menjadi keluar sebanyak $jumlah_baru!");
            }
            mysqli_query($conn, "UPDATE barang SET stok_awal = stok_awal - $jumlah_baru WHERE id_barang = '$id_barang'");
        }

        // 6. UPDATE record di tabel transaksi_barang
        $query_update = "UPDATE transaksi_barang SET 
                            jenis = '$jenis_baru', 
                            jumlah = '$jumlah_baru', 
                            tanggal = '$tanggal', 
                            keterangan = '$keterangan' 
                         WHERE id_transaksi = '$id_transaksi'";
        mysqli_query($conn, $query_update);

        // 7. Jika semua sukses, COMMIT
        mysqli_commit($conn);
        header("Location: transaksi.php?pesan=edit_berhasil");
        exit;

    } catch (Exception $e) {
        // 8. Jika ada error, ROLLBACK semua perubahan
        mysqli_rollback($conn);
        echo "<script>alert('Error: " . $e->getMessage() . "'); window.location.href='transaksi.php';</script>";
        exit;
    }
}