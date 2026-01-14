<?php
include 'config/database.php';
include 'templates/header.php';
include 'templates/sidebar.php';

// Ambil daftar barang
$barang = mysqli_query($conn, "SELECT * FROM barang ORDER BY nama_barang ASC");

// Barang terpilih
$id_barang = isset($_GET['id_barang']) ? intval($_GET['id_barang']) : null;
?>

<main class="flex-1 p-6 bg-gray-100 min-h-screen">

<h1 class="text-xl font-semibold mb-4">📊 Kartu Gudang</h1>

<!-- PILIH BARANG -->
<form method="GET" class="mb-4 flex gap-2">
  <select name="id_barang" required class="border px-3 py-2 rounded w-64">
    <option value="">-- Pilih Barang --</option>
    <?php while ($b = mysqli_fetch_assoc($barang)) { ?>
      <option value="<?= $b['id_barang']; ?>" <?= ($id_barang == $b['id_barang']) ? 'selected' : '' ?>>
        <?= htmlspecialchars($b['kode_barang'].' - '.$b['nama_barang']); ?>
      </option>
    <?php } ?>
  </select>

  <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">
    Tampilkan
  </button>
</form>

<?php if ($id_barang) : ?>

<?php
// Ambil data barang
$barang_detail = mysqli_fetch_assoc(
  mysqli_query($conn, "SELECT * FROM barang WHERE id_barang=$id_barang")
);

// Hitung saldo awal dari semua transaksi sebelumnya
$saldo_awal_res = mysqli_query($conn, "
    SELECT SUM(CASE WHEN LOWER(jenis)='masuk' THEN jumlah ELSE -jumlah END) AS saldo
    FROM transaksi_barang
    WHERE id_barang=$id_barang
");
$saldo_awal_row = mysqli_fetch_assoc($saldo_awal_res);
$saldo = $saldo_awal_row['saldo'] ?? 0;

// Ambil transaksi barang
$transaksi = mysqli_query($conn, "
  SELECT * FROM transaksi_barang
  WHERE id_barang=$id_barang
  ORDER BY tanggal ASC, id_transaksi ASC
");
?>

<!-- INFO BARANG -->
<div class="bg-white p-4 rounded shadow mb-4">
  <strong><?= htmlspecialchars($barang_detail['nama_barang']); ?></strong><br>
  Kode: <?= htmlspecialchars($barang_detail['kode_barang']); ?><br>
  Saldo Awal: <?= $saldo; ?>
</div>

<!-- TABEL KARTU GUDANG -->
<div class="bg-white rounded shadow overflow-x-auto">
<table class="w-full text-sm">
<thead class="bg-gray-200">
<tr>
  <th class="px-3 py-2">Tanggal</th>
  <th class="px-3 py-2">Nama Barang</th>
  <th class="px-3 py-2">Keterangan</th>
  <th class="px-3 py-2 text-center">Masuk</th>
  <th class="px-3 py-2 text-center">Keluar</th>
  <th class="px-3 py-2 text-center">Sisa</th>
</tr>
</thead>
<tbody>

<tr class="border-t font-semibold bg-gray-50">
  <td colspan="5" class="px-3 py-2">Saldo Awal</td>
  <td class="px-3 py-2 text-center"><?= $saldo; ?></td>
</tr>

<?php 
if(mysqli_num_rows($transaksi) > 0){
  while ($t = mysqli_fetch_assoc($transaksi)) { 
      $jenis = strtolower($t['jenis']);
      $masuk = ($jenis == 'masuk') ? $t['jumlah'] : 0;
      $keluar = ($jenis == 'keluar') ? $t['jumlah'] : 0;
      $saldo += $masuk - $keluar;
?>
<tr class="border-t">
  <td class="px-3 py-2"><?= date('d-m-Y', strtotime($t['tanggal'])); ?></td>
  <td class="px-3 py-2"><?= htmlspecialchars($barang_detail['nama_barang']); ?></td>
  <td class="px-3 py-2"><?= htmlspecialchars($t['keterangan']); ?></td>
  <td class="px-3 py-2 text-center"><?= $masuk ?: '-'; ?></td>
  <td class="px-3 py-2 text-center"><?= $keluar ?: '-'; ?></td>
  <td class="px-3 py-2 text-center font-semibold"><?= $saldo; ?></td>
</tr>
<?php 
  }
} else {
  echo '<tr class="border-t"><td colspan="6" class="px-3 py-2 text-center">Tidak ada data transaksi</td></tr>';
}
?>

</tbody>
</table>
</div>

<?php endif; ?>

</main>

<?php include 'templates/footer.php'; ?>
