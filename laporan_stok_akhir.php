<?php
session_start();
include 'config/database.php';
include 'templates/header.php';
include 'templates/sidebar.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

// 1. Parameter Filter
$tanggal = $_GET['tanggal'] ?? date('Y-m-d');
$kategori_filter = $_GET['kategori'] ?? '';

// 2. Query List Kategori untuk Dropdown
$list_kategori = mysqli_query($conn, "SELECT DISTINCT kategori FROM barang WHERE kategori IS NOT NULL AND kategori != '' ORDER BY kategori ASC");

// 3. Query Stok Akhir (Logika: 0 + Total Masuk - Total Keluar)
$sql = "SELECT 
            b.id_barang,
            b.kode_barang, 
            b.nama_barang, 
            b.satuan, 
            b.kategori, 
            (
                0 + 
                COALESCE((SELECT SUM(jumlah) FROM transaksi_barang WHERE id_barang = b.id_barang AND jenis = 'masuk' AND tanggal <= '".mysqli_real_escape_string($conn, $tanggal)."'), 0) - 
                COALESCE((SELECT SUM(jumlah) FROM transaksi_barang WHERE id_barang = b.id_barang AND jenis = 'keluar' AND tanggal <= '".mysqli_real_escape_string($conn, $tanggal)."'), 0)
            ) AS sisa_stok
        FROM barang b";

if ($kategori_filter) {
    $sql .= " WHERE b.kategori = '" . mysqli_real_escape_string($conn, $kategori_filter) . "'";
}

$sql .= " ORDER BY b.nama_barang ASC";
$stok_query = mysqli_query($conn, $sql);
?>

<style>
    /* --- UI PREVIEW (Tampilan Layar) --- */
    .container-proportional { max-width: 1440px; margin: 0 auto; }
    .table-proportional { border-collapse: separate; border-spacing: 0; width: 100%; }
    
    /* Header Tabel Lebih Modern */
    .table-proportional th { 
        padding: 1.25rem 1rem !important; 
        font-size: 0.75rem; 
        font-weight: 800;
        background-color: #f8fafc;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        border-bottom: 2px solid #e2e8f0;
    }

    /* Baris Tabel */
    .table-proportional td { 
        padding: 1.25rem 1rem !important; 
        font-size: 0.9rem; 
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }

    /* Efek Hover baris */
    .table-row-hover:hover {
        background-color: rgba(241, 245, 249, 0.5);
        transition: background-color 0.2s ease;
    }

    /* Lebar Kolom */
    .col-no { width: 60px; }
    .col-kode { width: 140px; }
    .col-nama { min-width: 300px; } 
    .col-kat { width: 220px; }
    .col-stok { width: 150px; }
    .col-satuan { width: 120px; }

    /* Badge Kategori Custom */
    .category-pill {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        background-color: #f1f5f9;
        color: #64748b;
        border-radius: 9999px;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.025em;
    }

    /* --- KONFIGURASI KHUSUS CETAK (PRINT) --- */
    @media print {
        aside, nav, header, .no-print, form, button { display: none !important; }
        main { margin: 0 !important; padding: 0 !important; background: white !important; width: 100% !important; }
        
        .rounded-\[2rem\], .shadow-xl, .border { 
            border-radius: 0 !important; 
            box-shadow: none !important; 
            border: none !important;
        }

        #printableArea { visibility: visible; width: 100%; position: absolute; left: 0; top: 0; }

        .print-header { 
            display: block !important; 
            border-bottom: 2px solid #000; 
            margin-bottom: 15px; 
            text-align: center; 
            padding-bottom: 10px;
        }

        table.table-proportional { 
            width: 100% !important; 
            border-collapse: collapse !important; 
            border: 1px solid #000 !important;
        }

        table.table-proportional th, 
        table.table-proportional td { 
            border: 1px solid #000 !important; 
            padding: 6px 10px !important; 
            font-size: 10pt !important; 
            line-height: 1.2 !important; 
            color: black !important;
            word-wrap: break-word;
            white-space: normal !important;
        }

        .category-pill {
            background-color: transparent !important;
            padding: 0 !important;
            color: black !important;
            font-size: 9pt !important;
        }

        .text-blue-600 { color: black !important; }
        .bg-blue-50 { background: transparent !important; padding: 0 !important; }
        .stok-critical { color: red !important; font-weight: bold !important; }
        
        .mt-16 { margin-top: 30px !important; }
        .mb-24 { margin-bottom: 60px !important; }
    }
</style>

<main class="flex-1 bg-slate-50 min-h-screen p-4 md:p-8 w-full">
    <div class="container-proportional">
        
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 mb-8 no-print">
            <div>
                <h1 class="text-4xl font-black text-slate-900 tracking-tight">Status Stok Akhir</h1>
                <p class="text-sm text-slate-500 mt-2 flex items-center gap-2">
                    <span class="flex h-3 w-3 relative">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                    </span>
                    Monitoring mutasi barang real-time PT MMM
                </p>
            </div>
            
            <div class="flex flex-wrap items-center gap-4">
                <form method="GET" class="flex flex-wrap items-center gap-3 bg-white p-3 rounded-2xl border border-slate-200 shadow-sm">
                    <div class="flex items-center gap-2 px-3 border-r border-slate-100">
                        <span class="text-slate-400 text-[10px] font-black uppercase tracking-wider">Periode</span>
                        <input type="date" name="tanggal" value="<?= $tanggal; ?>" class="border-none text-sm font-bold text-slate-800 focus:ring-0">
                    </div>
                    <div class="flex items-center gap-2 px-3">
                        <span class="text-slate-400 text-[10px] font-black uppercase tracking-wider">Kategori</span>
                        <select name="kategori" class="border-none text-sm font-bold text-slate-800 focus:ring-0 min-w-[150px]">
                            <option value="">Semua Kategori</option>
                            <?php mysqli_data_seek($list_kategori, 0); while($kat = mysqli_fetch_assoc($list_kategori)): ?>
                                <option value="<?= $kat['kategori']; ?>" <?= ($kategori_filter == $kat['kategori']) ? 'selected' : ''; ?>><?= $kat['kategori']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-xl text-sm font-bold transition-all shadow-lg shadow-blue-100 italic">Filter</button>
                </form>

                <button onclick="window.print()" class="bg-slate-900 hover:bg-black text-white px-6 py-3 rounded-2xl shadow-xl transition-all font-bold text-sm flex items-center gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    Cetak Laporan
                </button>
            </div>
        </div>

        <div id="printableArea">
            <div class="hidden print-header">
                <h1 class="text-2xl font-black uppercase tracking-widest text-slate-900">Laporan Stok Akhir Barang</h1>
                <p class="text-lg font-bold text-slate-700">PT MUARA MITRA MANDIRI</p>
                <div class="text-sm mt-2 text-slate-500">Kondisi per tanggal: <strong><?= date('d F Y', strtotime($tanggal)); ?></strong></div>
            </div>

            <div class="bg-white rounded-[2rem] shadow-xl shadow-slate-200/60 border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="table-proportional">
                        <thead>
                            <tr>
                                <th class="text-center col-no">#</th>
                                <th class="text-left col-kode">Kode</th>
                                <th class="text-left col-nama">Nama Produk</th>
                                <th class="text-center col-kat">Kategori</th>
                                <th class="text-center col-stok">Sisa Stok</th>
                                <th class="text-center col-satuan">Satuan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php 
                            $no = 1;
                            if(mysqli_num_rows($stok_query) > 0):
                                while($row = mysqli_fetch_assoc($stok_query)): 
                                    $stok = (float)$row['sisa_stok'];
                            ?>
                            <tr class="table-row-hover">
                                <td class="text-center text-slate-400 font-bold"><?= $no++; ?></td>
                                <td class="font-mono font-bold">
                                    <span class="text-blue-600 bg-blue-50 px-2.5 py-1 rounded text-xs"><?= $row['kode_barang']; ?></span>
                                </td>
                                <td class="font-extrabold text-slate-800 uppercase leading-snug">
                                    <?= htmlspecialchars($row['nama_barang']); ?>
                                </td>
                                <td class="text-center">
                                    <span class="category-pill">
                                        <?= htmlspecialchars($row['kategori'] ?: 'Umum'); ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="text-xl font-black tracking-tight <?= $stok <= 5 ? 'text-red-600 stok-critical' : 'text-slate-900' ?>">
                                        <?= number_format($stok); ?>
                                    </span>
                                </td>
                                <td class="text-center text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                    <?= htmlspecialchars($row['satuan']); ?>
                                </td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr>
                                <td colspan="6" class="p-32 text-center">
                                    <div class="flex flex-col items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-slate-200 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 00-2 2H6a2 2 0 00-2 2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                        </svg>
                                        <p class="text-slate-400 font-bold italic">Belum ada data barang atau transaksi ditemukan.</p>
                                    </div>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-16 hidden print:grid grid-cols-2">
                <div class="text-[9pt] text-slate-400 italic">
                    * Dokumen ini dihasilkan secara otomatis melalui Sistem Manajemen Inventaris Workshop PT MMM.
                </div> 
                <div class="flex flex-col items-center ml-auto w-72 text-center">
                    <p class="text-[10pt] text-slate-700 mb-24">
                        Yogyakarta, <?= date('d F Y') ?>
                    </p>
                    <div class="w-full border-t-2 border-slate-900 mt-1"></div>
                    <p class="font-black text-slate-900 tracking-widest text-[9pt] mt-2">
                        Kord. Workshop PT MMM
                    </p>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'templates/footer.php'; ?>