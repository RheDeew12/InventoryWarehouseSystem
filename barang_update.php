<?php
include 'config/database.php';
session_start();

// Proteksi: Hanya Admin yang bisa eksekusi update
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id_barang'];
    $kode = mysqli_real_escape_string($conn, $_POST['kode_barang']);
    $nama = mysqli_real_escape_string($conn, $_POST['nama_barang']);
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);
    $stok_baru = (int)$_POST['stok_awal']; 
    $satuan = mysqli_real_escape_string($conn, $_POST['satuan']);
    $tgl = $_POST['tanggal_masuk'];
    $ket = mysqli_real_escape_string($conn, $_POST['keterangan']);

    // 1. Ambil stok lama untuk menghitung selisih
    $query_lama = mysqli_query($conn, "SELECT stok_awal FROM barang WHERE id_barang = '$id'");
    $data_lama = mysqli_fetch_assoc($query_lama);
    $stok_lama = (int)$data_lama['stok_awal'];

    // 2. Mulai Transaksi Database untuk konsistensi data
    mysqli_begin_transaction($conn);

    try {
        // 3. Update data di tabel barang
        $query_update = "UPDATE barang SET 
                    kode_barang = '$kode', 
                    nama_barang = '$nama',
                    kategori = '$kategori', 
                    stok_awal = '$stok_baru', 
                    satuan = '$satuan', 
                    tanggal_masuk = '$tgl', 
                    keterangan = '$ket' 
                  WHERE id_barang = '$id'";
        mysqli_query($conn, $query_update);

        // 4. Catat selisih ke tabel transaksi agar Laporan & Arus Barang sinkron
        if ($stok_baru != $stok_lama) {
            $selisih = abs($stok_baru - $stok_lama);
            $jenis = ($stok_baru > $stok_lama) ? 'masuk' : 'keluar';
            $ket_adj = "Penyesuaian Stok (Edit Master)";

            $query_adj = "INSERT INTO transaksi_barang (id_barang, jenis, jumlah, tanggal, keterangan) 
                          VALUES ('$id', '$jenis', '$selisih', '" . date('Y-m-d') . "', '$ket_adj')";
            mysqli_query($conn, $query_adj);
        }

        mysqli_commit($conn);
        header("Location: barang.php?status=update_berhasil");
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo "Error: " . $e->getMessage();
    }
}