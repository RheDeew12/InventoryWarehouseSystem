<?php
session_start();
include 'config/database.php';

// Proteksi Halaman
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

// Menangani Filter Tanggal
$tgl_mulai = $_GET['tgl_mulai'] ?? date('Y-m-01');
$tgl_sampai = $_GET['tgl_sampai'] ?? date('Y-m-d');

include 'templates/header.php';
include 'templates/sidebar.php';

// Query mengambil data barang MASUK dengan filter tanggal
$query = "SELECT t.*, b.nama_barang, b.satuan, b.kode_barang, b.kategori 
          FROM transaksi_barang t
          JOIN barang b ON t.id_barang = b.id_barang
          WHERE LOWER(TRIM(t.jenis)) = 'masuk' 
          AND t.tanggal BETWEEN '$tgl_mulai' AND '$tgl_sampai'
          ORDER BY t.tanggal DESC, t.id_transaksi DESC";
          
$result = mysqli_query($conn, $query);

// Menghitung Ringkasan (Stats)
$total_qty = 0;
$total_transaksi = mysqli_num_rows($result);
$data_array = [];
while($row = mysqli_fetch_assoc($result)) {
    $total_qty += $row['jumlah'];
    $data_array[] = $row;
}
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
        padding: 0.75rem 0.75rem !important;
        font-size: 0.7rem; 
        background: #f8fafc;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    
    .table-proportional td { 
        padding: 0.75rem 0.75rem !important; 
        font-size: 0.875rem;
    }

    /* KHUSUS HASIL CETAK / PRINT */
    @media print {
        aside, nav, header, .no-print, form, button { display: none !important; }
        main { margin: 0 !important; padding: 0 !important; width: 100% !important; background: white !important; }
        
        #printableArea { 
            visibility: visible; 
            width: 100%; 
            position: absolute; 
            left: 0; 
            top: 0; 
        }
        
        .print-header { 
            display: block !important; 
            border-bottom: 2px solid #000; 
            margin-bottom: 10px; 
            text-align: center; 
        }
        
        table { 
            width: 100%; 
            border-collapse: collapse !important; 
            border: 1px solid #000 !important;
        }
        
        th, td { 
            border: 1px solid #000 !important; 
            padding: 1px 4px !important; 
            font-size: 8.5pt !important; 
            line-height: 1 !important; 
            color: black !important;
        }

        .glass-card, .rounded-3xl, .shadow-xl { 
            border-radius: 0 !important; 
            box-shadow: none !important; 
            border: none !important; 
        }

        .mt-12 { margin-top: 1.5rem !important; }
        .mb-20 { margin-bottom: 2.5rem !important; }
    }
</style>

<main class="flex-1 bg-[#fcfcfd] min-h-screen p-4 md:p-8 w-full">
    <div class="container-proportional">
        
        <div class="no-print flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
            <div>
                <h1 class="text-4xl font-black text-slate-900 tracking-tight">Barang Masuk</h1>
                <p class="text-slate-500 mt-1 flex items-center gap-2">
                    <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    Log penerimaan dan penambahan inventaris gudang
                </p>
            </div>
            <button onclick="window.print()" class="flex items-center gap-2 px-6 py-3 bg-white border border-slate-200 text-slate-700 rounded-2xl font-bold text-sm hover:bg-slate-50 transition-all shadow-sm">
                <span>🖨️</span> Cetak Laporan
            </button>
        </div>

        <div class="no-print grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="glass-card p-6 rounded-[2rem] shadow-sm flex items-center gap-5">
                <div class="w-14 h-14 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center text-2xl">📦</div>
                <div>
                    <p class="text-xs font-black text-slate-400 uppercase tracking-widest">Total Transaksi</p>
                    <p class="text-2xl font-black text-slate-800"><?= number_format($total_transaksi); ?> <span class="text-sm font-medium text-slate-400">Record</span></p>
                </div>
            </div>
            <div class="glass-card p-6 rounded-[2rem] shadow-sm flex items-center gap-5">
                <div class="w-14 h-14 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center text-2xl">📥</div>
                <div>
                    <p class="text-xs font-black text-slate-400 uppercase tracking-widest">Total Volume Masuk</p>
                    <p class="text-2xl font-black text-slate-800"><?= number_format($total_qty); ?> <span class="text-sm font-medium text-slate-400">Unit</span></p>
                </div>
            </div>
            <div class="glass-card p-6 rounded-[2rem] shadow-sm flex items-center gap-5">
                <div class="w-14 h-14 bg-amber-50 text-amber-600 rounded-2xl flex items-center justify-center text-2xl">📅</div>
                <div>
                    <p class="text-xs font-black text-slate-400 uppercase tracking-widest">Periode</p>
                    <p class="text-sm font-bold text-slate-800"><?= date('d M', strtotime($tgl_mulai)) ?> — <?= date('d M Y', strtotime($tgl_sampai)) ?></p>
                </div>
            </div>
        </div>

        <div class="no-print glass-card p-4 rounded-[1.5rem] shadow-sm mb-6">
            <form method="GET" class="flex flex-wrap items-center gap-4">
                <div class="flex items-center gap-3 bg-slate-100/50 p-1.5 rounded-xl border border-slate-200">
                    <div class="px-3">
                        <label class="block text-[9px] font-black text-slate-400 uppercase">Dari</label>
                        <input type="date" name="tgl_mulai" value="<?= $tgl_mulai ?>" class="bg-transparent border-none p-0 text-sm font-bold focus:ring-0">
                    </div>
                    <div class="h-8 w-px bg-slate-200"></div>
                    <div class="px-3">
                        <label class="block text-[9px] font-black text-slate-400 uppercase">Sampai</label>
                        <input type="date" name="tgl_sampai" value="<?= $tgl_sampai ?>" class="bg-transparent border-none p-0 text-sm font-bold focus:ring-0">
                    </div>
                    <button type="submit" class="bg-slate-800 text-white p-2.5 rounded-lg hover:bg-black transition-all">Filter</button>
                </div>
                <div class="flex-1 relative">
                    <span class="absolute inset-y-0 left-4 flex items-center text-slate-400">🔍</span>
                    <input type="text" id="searchInput" placeholder="Cari data masuk..." class="w-full pl-12 pr-4 py-3.5 bg-white border border-slate-200 rounded-2xl text-sm outline-none focus:ring-2 focus:ring-blue-100 transition-all">
                </div>
            </form>
        </div>

        <div id="printableArea">
            <div class="hidden print-header pb-4 text-center">
                <h1 class="text-2xl font-bold uppercase">Laporan Penerimaan Barang</h1>
                <p class="text-md font-bold">PT MUARA MITRA MANDIRI</p>
                <p class="text-xs italic">Periode: <?= date('d/m/Y', strtotime($tgl_mulai)) ?> — <?= date('d/m/Y', strtotime($tgl_sampai)) ?></p>
            </div>

            <div class="glass-card rounded-[2rem] shadow-xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left table-proportional" id="reportTable">
                        <thead>
                            <tr class="border-b border-slate-100">
                                <th class="text-center w-24">Tanggal</th>
                                <th class="text-center w-24">Kode</th>
                                <th class="text-center">Nama Barang</th>
                                <th class="text-center">Kategori</th>
                                <th class="text-center">Jumlah</th>
                                <th>Keterangan / Sumber</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (!empty($data_array)) : ?>
                                <?php foreach($data_array as $row) : ?>
                                <tr class="hover:bg-emerald-50/30 transition-colors">
                                    <td class="whitespace-nowrap font-bold text-slate-600 text-center"><?= date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                                    <td class="text-center">
                                        <span class="text-[10px] font-mono font-black text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-md">
                                            <?= $row['kode_barang']; ?>
                                        </span>
                                    </td>
                                    <td class="text-center font-bold text-slate-800 uppercase"><?= htmlspecialchars($row['nama_barang']); ?></td>
                                    <td class="text-center">
                                        <span class="inline-block px-3 py-1 bg-slate-100 text-slate-500 rounded-full text-[9px] font-black uppercase">
                                            <?= htmlspecialchars($row['kategori'] ?: 'Umum'); ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="flex flex-col items-center">
                                            <span class="font-black text-emerald-600">+ <?= number_format($row['jumlah']); ?></span>
                                            <span class="text-[9px] text-slate-400"><?= $row['satuan']; ?></span>
                                        </div>
                                    </td>
                                    <td class="text-xs text-slate-500 leading-relaxed"><?= $row['keterangan'] ?: '-'; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr><td colspan="6" class="p-24 text-center text-slate-400 font-bold uppercase tracking-widest">Tidak ada data masuk</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="hidden print:block mt-12 mb-8">
                <table class="w-full" style="border: none !important;">
                    <tr style="border: none !important;">
                        <td class="text-center w-1/2" style="border: none !important; vertical-align: bottom;">
                            <p class="text-[10pt] mb-20">Mengetahui,</p>
                            <div class="inline-block w-56 border-t border-black pt-2">
                                <p class="text-[10pt] font-black">Direksi PT MMM</p>
                            </div>
                        </td>
                        <td class="text-center w-1/2" style="border: none !important; vertical-align: bottom;">
                            <p class="text-[8pt] italic text-right mb-4 opacity-70">Sistem Inventaris — <?= date('d/m/Y') ?></p>
                            <p class="text-[10pt] mb-20">Kord. Gudang,</p>
                            <div class="inline-block w-56 border-t border-black pt-2">
                                <p class="text-[10pt] font-black">Admin Gudang</p>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</main>

<script>
    // Fitur Search Client-side
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