<?php
session_start();
include 'config/database.php';
include 'templates/header.php';
include 'templates/sidebar.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

// Statistik
$totalBarang = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM barang"))['total'];
$totalMasuk = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(jumlah),0) AS total FROM transaksi_barang WHERE UPPER(jenis)='MASUK'"))['total'];
$totalKeluar = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(jumlah),0) AS total FROM transaksi_barang WHERE UPPER(jenis)='KELUAR'"))['total'];

// Transaksi terbaru
$transaksi = mysqli_query($conn, "
    SELECT t.*, b.nama_barang, b.satuan, b.kode_barang
    FROM transaksi_barang t
    JOIN barang b ON b.id_barang = t.id_barang
    ORDER BY t.tanggal DESC, t.id_transaksi DESC
    LIMIT 10
");
?>

<main class="flex-1 bg-slate-50 min-h-screen p-4 md:p-8 w-full">
    
    <div class="mb-8 border-b border-slate-200 pb-4">
        <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight flex items-center gap-3">
            <span class="text-4xl">📊</span> Dashboard Gudang Utama
        </h1>
        <p class="text-slate-500 mt-1 font-medium">Ringkasan operasional PT MUARA MITRA MANDIRI hari ini.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 flex items-center justify-between hover:shadow-md transition-shadow">
            <div class="flex items-center gap-5">
                <div class="bg-blue-100 p-4 rounded-xl text-blue-600 shadow-inner">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <div>
                    <p class="text-slate-500 text-xs font-bold uppercase tracking-wider">Jenis Barang</p>
                    <h2 class="text-3xl font-black text-slate-800"><?= number_format($totalBarang); ?></h2>
                </div>
            </div>
            <span class="text-blue-500 font-bold bg-blue-50 px-3 py-1 rounded-full text-xs">Aktif</span>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 flex items-center justify-between hover:shadow-md transition-shadow">
            <div class="flex items-center gap-5">
                <div class="bg-emerald-100 p-4 rounded-xl text-emerald-600 shadow-inner">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                    </svg>
                </div>
                <div>
                    <p class="text-slate-500 text-xs font-bold uppercase tracking-wider">Total Masuk</p>
                    <h2 class="text-3xl font-black text-emerald-600"><?= number_format($totalMasuk); ?></h2>
                </div>
            </div>
            <span class="text-emerald-500 font-bold bg-emerald-50 px-3 py-1 rounded-full text-xs">Inventory In</span>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 flex items-center justify-between hover:shadow-md transition-shadow">
            <div class="flex items-center gap-5">
                <div class="bg-rose-100 p-4 rounded-xl text-rose-600 shadow-inner">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-slate-500 text-xs font-bold uppercase tracking-wider">Total Keluar</p>
                    <h2 class="text-3xl font-black text-rose-600"><?= number_format($totalKeluar); ?></h2>
                </div>
            </div>
            <span class="text-rose-500 font-bold bg-rose-50 px-3 py-1 rounded-full text-xs">Inventory Out</span>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-5 border-b border-slate-100 bg-white flex justify-between items-center">
            <h3 class="font-bold text-slate-800 flex items-center gap-2">
                <span class="w-2 h-6 bg-blue-600 rounded-full"></span>
                Aktivitas Transaksi Terakhir
            </h3>
            <a href="transaksi.php" class="text-xs font-bold text-blue-600 hover:text-blue-800 uppercase tracking-widest transition-colors">Lihat Semua →</a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 text-slate-500 uppercase text-[11px] font-black tracking-widest border-b border-slate-100">
                        <th class="px-6 py-4">Waktu</th>
                        <th class="px-6 py-4">Kode</th>
                        <th class="px-6 py-4">Nama Barang</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-center">Volume</th>
                        <th class="px-6 py-4">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if(mysqli_num_rows($transaksi) > 0): ?>
                        <?php while ($t = mysqli_fetch_assoc($transaksi)): ?>
                        <tr class="hover:bg-slate-50 transition-colors group">
                            <td class="px-6 py-4 text-sm text-slate-600 font-medium">
                                <?= date('d M Y', strtotime($t['tanggal'])); ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-[10px] font-mono font-bold text-blue-600 bg-blue-50 px-2 py-1 rounded uppercase tracking-wider">
                                    <?= $t['kode_barang'] ?? 'N/A'; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm font-bold text-slate-800 group-hover:text-blue-600 transition-colors">
                                    <?= htmlspecialchars($t['nama_barang']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <?php if(strtoupper($t['jenis']) == 'MASUK'): ?>
                                    <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase bg-emerald-100 text-emerald-700">IN</span>
                                <?php else: ?>
                                    <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase bg-rose-100 text-rose-700">OUT</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-center font-black text-slate-800">
                                <?= number_format($t['jumlah']); ?> <span class="text-[9px] text-slate-400 font-normal uppercase ml-1"><?= htmlspecialchars($t['satuan']); ?></span>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-500 italic">
                                <span class="truncate block max-w-[150px]" title="<?= htmlspecialchars($t['keterangan']); ?>">
                                    <?= $t['keterangan'] ? htmlspecialchars($t['keterangan']) : '<span class="text-slate-300">-</span>'; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-6 py-20 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <span class="text-5xl opacity-20">📂</span>
                                    <p class="text-slate-400 font-medium italic">Belum ada rekaman transaksi hari ini.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php include 'templates/footer.php'; ?>