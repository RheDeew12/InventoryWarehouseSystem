<?php
session_start();
include 'config/database.php';

// 1. SET TIMEZONE INDONESIA (WIB)
date_default_timezone_set('Asia/Jakarta');

// Proteksi Halaman
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

// Menangani Filter Data dari URL (GET)
$tgl_mulai  = $_GET['tgl_mulai'] ?? date('Y-m-01');
$tgl_sampai = $_GET['tgl_sampai'] ?? date('Y-m-d');
$kategori   = $_GET['kategori'] ?? '';
$proyek     = $_GET['proyek'] ?? ''; // Filter Proyek diambil dari kolom 'keterangan'

include 'templates/header.php';
include 'templates/sidebar.php';

// --- A. QUERY UNTUK ISI DROPDOWN FILTER ---
// 1. Ambil daftar Kategori unik
$list_kategori = mysqli_query($conn, "SELECT DISTINCT kategori FROM barang WHERE kategori IS NOT NULL AND kategori != '' ORDER BY kategori ASC");

// 2. Ambil daftar Proyek/Tujuan unik (dari history transaksi keluar)
// Kita ambil kolom 'keterangan' sebagai penanda Project/Tujuan
$list_proyek = mysqli_query($conn, "SELECT DISTINCT keterangan FROM transaksi_barang WHERE jenis = 'keluar' AND keterangan IS NOT NULL AND keterangan != '' ORDER BY keterangan ASC");


// --- B. QUERY UTAMA DATA BARANG KELUAR ---
$query = "SELECT t.*, b.nama_barang, b.satuan, b.kode_barang, b.kategori 
          FROM transaksi_barang t
          JOIN barang b ON t.id_barang = b.id_barang
          WHERE LOWER(TRIM(t.jenis)) = 'keluar' 
          AND t.tanggal BETWEEN '$tgl_mulai' AND '$tgl_sampai'";

// Tambahan Logika Filter Kategori
if (!empty($kategori)) {
    $query .= " AND b.kategori = '" . mysqli_real_escape_string($conn, $kategori) . "'";
}

// Tambahan Logika Filter Proyek (Keterangan)
if (!empty($proyek)) {
    $query .= " AND t.keterangan = '" . mysqli_real_escape_string($conn, $proyek) . "'";
}

$query .= " ORDER BY t.tanggal DESC, t.id_transaksi DESC";
          
$result = mysqli_query($conn, $query);

// --- C. LOGIKA MEMBUAT REKAPITULASI & RINCIAN ---
$total_qty = 0;
$data_rincian = []; // Untuk tabel detail (bawah)
$data_rekap = [];   // Untuk tabel total per barang (atas)

while($row = mysqli_fetch_assoc($result)) {
    $total_qty += $row['jumlah'];
    
    // Simpan ke data rincian
    $data_rincian[] = $row;

    // Simpan ke data rekapitulasi (Group by ID Barang)
    $id = $row['id_barang'];
    if (!isset($data_rekap[$id])) {
        $data_rekap[$id] = [
            'kode' => $row['kode_barang'],
            'nama' => $row['nama_barang'],
            'kategori' => $row['kategori'],
            'satuan' => $row['satuan'],
            'total_keluar' => 0
        ];
    }
    $data_rekap[$id]['total_keluar'] += $row['jumlah'];
}
$total_transaksi = count($data_rincian);

// Urutkan rekap berdasarkan nama barang
usort($data_rekap, function($a, $b) {
    return strcmp($a['nama'], $b['nama']);
});
?>

<style>
    .container-proportional { max-width: 1440px; margin: 0 auto; }
    
    /* UI TAMPILAN LAYAR */
    .glass-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(226, 232, 240, 0.8);
    }
    .table-proportional th { 
        padding: 0.75rem !important; font-size: 0.7rem; background: #f8fafc;
        text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; font-weight: 800;
    }
    .table-proportional td { 
        padding: 0.75rem !important; font-size: 0.875rem; border-bottom: 1px solid #f1f5f9;
    }

    /* KHUSUS CETAK / PRINT */
    @media print {
        aside, nav, header, .no-print, form, button { display: none !important; }
        main { margin: 0 !important; padding: 0 !important; width: 100% !important; background: white !important; }
        #printableArea { visibility: visible; position: absolute; left: 0; top: 0; width: 100%; }
        
        .print-header { display: block !important; border-bottom: 2px solid #000; margin-bottom: 15px; text-align: center; }
        .section-title { font-size: 11pt !important; font-weight: bold !important; text-decoration: underline; text-transform: uppercase; margin-top: 15px; }
        
        table { width: 100%; border-collapse: collapse !important; border: 1px solid #000 !important; margin-bottom: 20px !important; }
        th, td { border: 1px solid #000 !important; padding: 4px 6px !important; font-size: 9pt !important; color: black !important; }
        th { background-color: #f0f0f0 !important; -webkit-print-color-adjust: exact; }
        
        .glass-card { border: none !important; box-shadow: none !important; }
        .bg-blue-50, .bg-rose-50, .text-blue-600, .text-rose-600 { background: none !important; color: black !important; font-weight: bold; }
        
        /* Layout Tanda Tangan */
        .mt-12 { margin-top: 2rem !important; }
    }
</style>

<main class="flex-1 bg-[#fcfcfd] min-h-screen p-4 md:p-8 w-full">
    <div class="container-proportional">
        
        <div class="no-print flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
            <div>
                <h1 class="text-4xl font-black text-slate-900 tracking-tight">Barang Keluar</h1>
                <p class="text-slate-500 mt-1 flex items-center gap-2">
                    <span class="inline-block w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                    Log distribusi dan rekapitulasi pengeluaran
                </p>
            </div>
            <button onclick="window.print()" class="flex items-center gap-2 px-6 py-3 bg-white border border-slate-200 text-slate-700 rounded-2xl font-bold text-sm hover:bg-slate-50 transition-all shadow-sm">
                <span>🖨️</span> Cetak Laporan
            </button>
        </div>

        <div class="no-print grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="glass-card p-6 rounded-[2rem] shadow-sm flex items-center gap-5">
                <div class="w-14 h-14 bg-rose-50 text-rose-600 rounded-2xl flex items-center justify-center text-2xl">📤</div>
                <div>
                    <p class="text-xs font-black text-slate-400 uppercase tracking-widest">Total Volume</p>
                    <p class="text-2xl font-black text-slate-800"><?= number_format($total_qty); ?> <span class="text-sm font-medium text-slate-400">Unit</span></p>
                </div>
            </div>
            <div class="glass-card p-6 rounded-[2rem] shadow-sm flex flex-col justify-center col-span-2">
                <p class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Filter Aktif Saat Ini</p>
                <div class="flex flex-wrap gap-4 text-sm font-bold text-slate-700">
                    <span class="flex items-center gap-2">📅 <?= date('d M Y', strtotime($tgl_mulai)) ?> s/d <?= date('d M Y', strtotime($tgl_sampai)) ?></span>
                    <?php if($kategori): ?>
                        <span class="flex items-center gap-2 text-blue-600 bg-blue-50 px-2 py-0.5 rounded">📂 <?= htmlspecialchars($kategori) ?></span>
                    <?php endif; ?>
                    <?php if($proyek): ?>
                        <span class="flex items-center gap-2 text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded">🏗️ <?= htmlspecialchars($proyek) ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="no-print glass-card p-5 rounded-[1.5rem] shadow-sm mb-6">
            <form method="GET" class="flex flex-col gap-4">
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Dari Tanggal</label>
                        <input type="date" name="tgl_mulai" value="<?= $tgl_mulai ?>" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm font-bold focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                    
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Sampai Tanggal</label>
                        <input type="date" name="tgl_sampai" value="<?= $tgl_sampai ?>" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm font-bold focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Kategori Barang</label>
                        <select name="kategori" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm font-bold focus:ring-2 focus:ring-blue-500 outline-none">
                            <option value="">-- Semua Kategori --</option>
                            <?php mysqli_data_seek($list_kategori, 0); while($kat = mysqli_fetch_assoc($list_kategori)): ?>
                                <option value="<?= $kat['kategori'] ?>" <?= ($kategori == $kat['kategori']) ? 'selected' : '' ?>>
                                    <?= $kat['kategori'] ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Proyek / Tujuan</label>
                        <select name="proyek" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm font-bold focus:ring-2 focus:ring-blue-500 outline-none">
                            <option value="">-- Semua Proyek --</option>
                            <?php mysqli_data_seek($list_proyek, 0); while($prj = mysqli_fetch_assoc($list_proyek)): ?>
                                <option value="<?= $prj['keterangan'] ?>" <?= ($proyek == $prj['keterangan']) ? 'selected' : '' ?>>
                                    <?= $prj['keterangan'] ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="flex flex-col md:flex-row gap-4 items-center">
                    <button type="submit" class="w-full md:w-auto bg-slate-800 text-white px-8 py-2.5 rounded-xl font-bold hover:bg-black transition-all shadow-lg">
                        Terapkan Filter
                    </button>
                    <a href="laporan_keluar.php" class="w-full md:w-auto text-center text-slate-500 font-bold text-sm hover:text-red-500 transition-colors">
                        Reset
                    </a>
                    
                    <div class="flex-1 w-full relative">
                        <span class="absolute inset-y-0 left-4 flex items-center text-slate-400">🔍</span>
                        <input type="text" id="searchInput" placeholder="Cari cepat di tabel..." class="w-full pl-12 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-blue-100 transition-all">
                    </div>
                </div>
            </form>
        </div>

        <div id="printableArea">
            <div class="hidden print-header pb-4 text-center">
                <h1 class="text-2xl font-bold uppercase">Laporan Pengeluaran Barang</h1>
                <p class="text-md font-bold">PT MUARA MITRA MANDIRI</p>
                <div class="text-xs mt-2 border-t border-b border-black py-1 inline-block">
                    Periode: <strong><?= date('d/m/Y', strtotime($tgl_mulai)) ?></strong> s/d <strong><?= date('d/m/Y', strtotime($tgl_sampai)) ?></strong>
                    <?php if($kategori): ?> | Kategori: <strong><?= htmlspecialchars($kategori) ?></strong><?php endif; ?>
                    <?php if($proyek): ?> | Proyek: <strong><?= htmlspecialchars($proyek) ?></strong><?php endif; ?>
                </div>
            </div>

            <div class="mb-8">
                <h3 class="no-print text-lg font-bold text-slate-800 mb-3 flex items-center gap-2">
                    📊 Rekapitulasi Total <span class="text-xs font-normal text-slate-500 bg-slate-100 px-2 py-0.5 rounded-md border border-slate-200">Total per Item sesuai Filter</span>
                </h3>
                <h3 class="hidden print:block section-title">I. Rekapitulasi Barang Keluar</h3>
                
                <div class="glass-card rounded-[1.5rem] shadow-sm overflow-hidden border border-slate-200">
                    <table class="w-full text-left table-proportional">
                        <thead>
                            <tr>
                                <th class="text-center w-16">No</th>
                                <th class="text-center w-32">Kode</th>
                                <th>Nama Barang</th>
                                <th class="text-center w-32">Kategori</th>
                                <th class="text-center w-32">Total Keluar</th>
                                <th class="text-center w-24">Satuan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <?php if (!empty($data_rekap)) : $no=1; ?>
                                <?php foreach($data_rekap as $rekap) : ?>
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="text-center text-slate-400 font-bold"><?= $no++; ?></td>
                                    <td class="text-center font-mono font-bold text-blue-600 text-xs"><?= $rekap['kode']; ?></td>
                                    <td class="font-bold text-slate-700 uppercase"><?= htmlspecialchars($rekap['nama']); ?></td>
                                    <td class="text-center text-[10px] font-black uppercase text-slate-400"><?= $rekap['kategori'] ?: '-'; ?></td>
                                    <td class="text-center font-black text-rose-600 bg-rose-50/50"><?= number_format($rekap['total_keluar']); ?></td>
                                    <td class="text-center text-xs font-bold text-slate-500 lowercase"><?= $rekap['satuan']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr><td colspan="6" class="p-4 text-center text-slate-400 italic">Tidak ada data rekapitulasi untuk filter ini.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div>
                <h3 class="no-print text-lg font-bold text-slate-800 mb-3 flex items-center gap-2">
                    📋 Rincian Kronologis <span class="text-xs font-normal text-slate-500 bg-slate-100 px-2 py-0.5 rounded-md border border-slate-200">Log Harian</span>
                </h3>
                <h3 class="hidden print:block section-title">II. Rincian Transaksi</h3>

                <div class="glass-card rounded-[1.5rem] shadow-sm overflow-hidden border border-slate-200">
                    <table class="w-full text-left table-proportional" id="reportTable">
                        <thead>
                            <tr>
                                <th class="text-center w-28">Tanggal</th>
                                <th class="text-center w-28">Kode</th>
                                <th>Nama Barang</th>
                                <th class="text-center w-24">Jumlah</th>
                                <th>Tujuan / Proyek</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <?php if (!empty($data_rincian)) : ?>
                                <?php foreach($data_rincian as $row) : ?>
                                <tr class="hover:bg-blue-50/30 transition-colors">
                                    <td class="font-bold text-slate-600 text-center text-xs"><?= date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                                    <td class="text-center">
                                        <span class="text-[10px] font-mono font-black text-slate-500 bg-slate-100 px-2 py-1 rounded">
                                            <?= $row['kode_barang']; ?>
                                        </span>
                                    </td>
                                    <td class="font-bold text-slate-800 uppercase text-xs"><?= htmlspecialchars($row['nama_barang']); ?></td>
                                    <td class="text-center font-bold text-rose-600">
                                        - <?= number_format($row['jumlah']); ?>
                                    </td>
                                    <td class="text-xs text-slate-500 italic uppercase"><?= $row['keterangan'] ?: '-'; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr><td colspan="5" class="p-12 text-center text-slate-400 font-bold uppercase tracking-widest">Tidak ada data transaksi ditemukan</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="hidden print:block mt-12 mb-8">
                <table class="w-full" style="border: none !important;">
                    <tr style="border: none !important;">
                        <td class="text-center w-1/2" style="border: none !important; vertical-align: bottom;">
                            <p class="text-[10pt] mb-16">Mengetahui,</p>
                            <div class="inline-block w-48 border-t border-black pt-1">
                                <p class="text-[10pt] font-black">Direksi PT MMM</p>
                            </div>
                        </td>
                        <td class="text-center w-1/2" style="border: none !important; vertical-align: bottom;">
                            <p class="text-[8pt] italic text-right mb-4 opacity-70">Dicetak: <?= date('d/m/Y H:i') ?> (WIB)</p>
                            <p class="text-[10pt] mb-16">Kord. Workshop,</p>
                            <div class="inline-block w-48 border-t border-black pt-1">
                                <p class="text-[10pt] font-black">Nopri Adrian</p>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</main>

<script>
    document.getElementById('searchInput').addEventListener('keyup', function() {
        const filter = this.value.toLowerCase();
        const rows = document.querySelectorAll('#reportTable tbody tr');
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? "" : "none";
        });
    });
</script>

<?php include 'templates/footer.php'; ?>