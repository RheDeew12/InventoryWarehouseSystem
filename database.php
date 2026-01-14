<?php
date_default_timezone_set('Asia/Jakarta');
$host = "sql310.infinityfree.com";
$user = "if0_40712843";
$pass = "gudangmmm";
$db   = "if0_40712843_db_gudang";

$conn = mysqli_connect($host, $user, $pass, $db, 3306);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Session aman (tidak double start)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
