<?php
include 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: barang.php");
    exit;
}

if ($_SESSION['role'] !== 'admin') {
    // Jika bukan admin, arahkan kembali ke dashboard dengan pesan error
    header("Location: dashboard.php?error=akses_ditolak");
    exit;
}

// 1. Proteksi Input
$kode_barang   = mysqli_real_escape_string($conn, $_POST['kode_barang']);
$nama_barang   = mysqli_real_escape_string($conn, $_POST['nama_barang']);
$kategori      = mysqli_real_escape_string($conn, $_POST['kategori']); 
$jumlah_input  = (int) $_POST['stok_awal']; 
$satuan        = mysqli_real_escape_string($conn, $_POST['satuan']);
$tanggal_masuk = $_POST['tanggal_masuk'];
$keterangan    = mysqli_real_escape_string($conn, $_POST['keterangan']);

// 2. Cek apakah kode barang sudah ada (mencegah Duplicate Entry)
$cek_kode = mysqli_query($conn, "SELECT kode_barang FROM barang WHERE kode_barang = '$kode_barang'");
if (mysqli_num_rows($cek_kode) > 0) {
    header("Location: barang.php?status=duplicate&kode=$kode_barang");
    exit;
}

// 3. Simpan ke tabel barang (Perbaikan: stok_awal diisi variabel $jumlah_input)
$query_barang = "INSERT INTO barang (kode_barang, nama_barang, kategori, stok_awal, satuan, tanggal_masuk, keterangan) 
                 VALUES ('$kode_barang', '$nama_barang', '$kategori', $jumlah_input, '$satuan', '$tanggal_masuk', '$keterangan')";

if (mysqli_query($conn, $query_barang)) {
    $id_barang = mysqli_insert_id($conn);

    // 4. AUTO transaksi: Masukkan ke history transaksi_barang
    if ($jumlah_input > 0) {
        $query_transaksi = "INSERT INTO transaksi_barang (id_barang, tanggal, jenis, jumlah, keterangan) 
                            VALUES ($id_barang, '$tanggal_masuk', 'masuk', $jumlah_input, 'Saldo Awal Barang')";
        
        if (!mysqli_query($conn, $query_transaksi)) {
            die("Gagal simpan transaksi: " . mysqli_error($conn));
        }
    }

    header("Location: barang.php?status=success");
    exit;
} else {
    die("Gagal simpan master barang: " . mysqli_error($conn));
}
?>