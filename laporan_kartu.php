<?php
session_start();
include 'config/database.php';
include 'templates/header.php';
include 'templates/sidebar.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

$barang_query = mysqli_query($conn, "SELECT * FROM barang ORDER BY nama_barang");

$id_barang = isset($_GET['id_barang']) ? intval($_GET['id_barang']) : 0;
$tgl_awal = $_GET['tgl_awal'] ?? '';
$tgl_akhir = $_GET['tgl_akhir'] ?? '';

// ... kode sebelumnya ...

$transaksi = [];
$nama_barang = $kode_barang = $satuan = '';
$saldo_awal_periode = 0; // Inisialisasi default
$total_masuk = $total_keluar = 0;

if ($id_barang) {
    // 1. Ambil data master barang
    $barang_res = mysqli_query($conn, "SELECT nama_barang, kode_barang, satuan FROM barang WHERE id_barang=$id_barang");
    $barang_row = mysqli_fetch_assoc($barang_res);
    $nama_barang = $barang_row['nama_barang'] ?? '';
    $kode_barang = $barang_row['kode_barang'] ?? '';
    $satuan = $barang_row['satuan'] ?? '';

    // 2. HITUNG SALDO AWAL (SALDO SEBELUM PERIODE FILTER)
    // Jika ada filter tgl_awal, hitung transaksi SEBELUM tanggal tersebut
    if ($tgl_awal) {
        $q_saldo = "SELECT 
                        SUM(CASE WHEN jenis='masuk' THEN jumlah ELSE 0 END) - 
                        SUM(CASE WHEN jenis='keluar' THEN jumlah ELSE 0 END) as saldo_lalu
                    FROM transaksi_barang 
                    WHERE id_barang=$id_barang AND tanggal < '" . mysqli_real_escape_string($conn, $tgl_awal) . "'";

        $res_saldo = mysqli_query($conn, $q_saldo);
        $row_saldo = mysqli_fetch_assoc($res_saldo);
        $saldo_awal_periode = $row_saldo['saldo_lalu'] ?? 0;
    }
    // Jika tidak ada filter tgl_awal, saldo awal tetap 0 (karena kita menampilkan dari awal waktu)
    else {
        $saldo_awal_periode = 0;
    }

    // 3. Ambil transaksi untuk ditampilkan (DALAM rentang periode)
    $sql = "SELECT * FROM transaksi_barang WHERE id_barang=$id_barang";
    if ($tgl_awal) $sql .= " AND tanggal >= '" . mysqli_real_escape_string($conn, $tgl_awal) . "'";
    if ($tgl_akhir) $sql .= " AND tanggal <= '" . mysqli_real_escape_string($conn, $tgl_akhir) . "'";

    // Penting: Urutkan berdasarkan ID juga agar urutan stok konsisten jika tanggal sama
    $sql .= " ORDER BY tanggal ASC, id_transaksi ASC";

    $transaksi_result = mysqli_query($conn, $sql);
    while ($t = mysqli_fetch_assoc($transaksi_result)) {
        $transaksi[] = $t;
        if (strtolower($t['jenis']) == 'masuk') $total_masuk += $t['jumlah'];
        else $total_keluar += $t['jumlah'];
    }
}
// ... kode selanjutnya ...
?>

<style>
    /* Tabel standar layar */
    .table-custom {
        width: 100%;
        border-collapse: collapse;
    }

    .table-custom th {
        @apply bg-slate-50 text-slate-500 text-[10px] font-black uppercase tracking-widest p-4 border-b border-slate-100;
    }

    .table-custom td {
        @apply p-4 border-b border-slate-50 text-sm text-slate-600;
    }

    @media print {

        .no-print,
        aside,
        nav,
        header,
        form {
            display: none !important;
        }

        body {
            background: white !important;
        }

        main {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }

        #printable {
            visibility: visible !important;
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            border: none !important;
            padding: 0 !important;
        }

        .table-custom th,
        .table-custom td {
            padding: 4px 8px !important;
            font-size: 9pt !important;
            border: 1px solid #000 !important;
            line-height: 1.1 !important;
        }

        .print-header {
            display: flex !important;
            justify-content: space-between;
            border-bottom: 2px solid #000;
            margin-bottom: 10px;
            padding-bottom: 10px;
        }
    }
</style>

<main class="flex-1 bg-slate-50 min-h-screen p-4 md:p-8 w-full">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8 no-print">
        <div>
            <h1 class="text-3xl font-black text-slate-800 tracking-tight">Kartu Gudang</h1>
            <p class="text-sm text-slate-500 mt-1">Mutasi stok dimulai dari saldo nol.</p>
        </div>
        <?php if ($id_barang): ?>
            <button onclick="window.print()" class="bg-blue-600 text-white px-6 py-2.5 rounded-xl shadow-lg hover:bg-blue-700 transition-all flex items-center gap-2 font-bold text-sm">
                🖨️ Cetak Laporan
            </button>
        <?php endif; ?>
    </div>

    <div class="bg-white rounded-3xl shadow-sm border border-slate-200/60 p-6 mb-8 no-print">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div class="md:col-span-1">
                <label class="text-[10px] font-black text-slate-400 uppercase mb-2 block tracking-widest">Pilih Barang</label>
                <select name="id_barang" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-bold focus:border-blue-500 outline-none" required>
                    <option value="">-- Pilih Barang --</option>
                    <?php mysqli_data_seek($barang_query, 0); ?>
                    <?php while ($b = mysqli_fetch_assoc($barang_query)) { ?>
                        <option value="<?= $b['id_barang']; ?>" <?= ($id_barang == $b['id_barang']) ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($b['nama_barang']); ?> (<?= htmlspecialchars($b['kode_barang']); ?>)
                        </option>
                    <?php } ?>
                </select>
            </div>
            <div>
                <label class="text-[10px] font-black text-slate-400 uppercase mb-2 block tracking-widest">Mulai</label>
                <input type="date" name="tgl_awal" value="<?= htmlspecialchars($tgl_awal); ?>" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-bold">
            </div>
            <div>
                <label class="text-[10px] font-black text-slate-400 uppercase mb-2 block tracking-widest">Hingga</label>
                <input type="date" name="tgl_akhir" value="<?= htmlspecialchars($tgl_akhir); ?>" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-bold">
            </div>
            <button type="submit" class="bg-slate-800 hover:bg-black text-white px-6 py-2.5 rounded-xl font-black text-sm uppercase tracking-widest transition-all">Tampilkan</button>
        </form>
    </div>

    <?php if ($id_barang): ?>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 no-print">
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 flex items-center gap-5">
                <div class="w-14 h-14 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center text-2xl shadow-inner">📥</div>
                <div>
                    <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest">Barang Masuk</p>
                    <p class="text-2xl font-black text-slate-800"><?= number_format($total_masuk); ?> <span class="text-xs font-medium"><?= $satuan ?></span></p>
                </div>
            </div>
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 flex items-center gap-5">
                <div class="w-14 h-14 bg-rose-50 text-rose-600 rounded-2xl flex items-center justify-center text-2xl shadow-inner">📤</div>
                <div>
                    <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest">Barang Keluar</p>
                    <p class="text-2xl font-black text-slate-800"><?= number_format($total_keluar); ?> <span class="text-xs font-medium"><?= $satuan ?></span></p>
                </div>
            </div>
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 flex items-center gap-5">
                <div class="w-14 h-14 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center text-2xl shadow-inner">📦</div>
                <div>
                    <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest">Saldo Mutasi</p>
                    <p class="text-2xl font-black text-slate-800"><?= number_format($saldo_awal_periode + $total_masuk - $total_keluar); ?> <span class="text-xs font-medium"><?= $satuan ?></span></p>
                </div>
            </div>
        </div>

        <div id="printable" class="bg-white rounded-[2rem] shadow-sm border border-slate-200/60 p-8">
            <div class="print-header flex justify-between items-end border-b-2 border-slate-800 pb-6 mb-8">
                <div>
                    <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tighter leading-none">Kartu Gudang</h2>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-2">PT MMM - Laporan Mutasi</p>
                </div>
                <div class="text-right">
                    <p class="text-lg font-black text-slate-800 uppercase leading-none"><?= htmlspecialchars($nama_barang); ?></p>
                    <p class="text-[10px] font-mono font-bold text-blue-600 uppercase mt-1"><?= htmlspecialchars($kode_barang); ?></p>
                    <p class="text-[10px] text-slate-400 mt-2 font-bold uppercase">Periode: <?= $tgl_awal ? date('d/m/Y', strtotime($tgl_awal)) : 'AWAL' ?> — <?= $tgl_akhir ? date('d/m/Y', strtotime($tgl_akhir)) : date('d/m/Y') ?></p>
                </div>
            </div>

            <table class="table-custom">
                <thead>
                    <tr>
                        <th class="text-left w-32">Tanggal</th>
                        <th class="text-left">Keterangan / Tujuan</th>
                        <th class="text-center w-28">Masuk</th>
                        <th class="text-center w-28">Keluar</th>
                        <th class="text-center w-28 bg-slate-50">Saldo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">

                    <?php if ($tgl_awal && $id_barang): ?>
                        <tr class="bg-slate-50">
                            <td class="font-bold text-slate-500 text-xs text-center">-</td>
                            <td class="text-slate-500 font-bold uppercase text-xs italic">
                                Saldo Awal (s/d <?= date('d/m/Y', strtotime($tgl_awal . ' -1 day')) ?>)
                            </td>
                            <td class="text-center text-slate-400">-</td>
                            <td class="text-center text-slate-400">-</td>
                            <td class="text-center font-black text-slate-800 bg-slate-100">
                                <?= number_format($saldo_awal_periode) ?>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php
                    // Inisialisasi running saldo dimulai dari saldo awal periode
                    $running_saldo = $saldo_awal_periode;

                    if (count($transaksi) > 0):
                        foreach ($transaksi as $t):
                            // Hitung masuk/keluar
                            $masuk = (strtolower($t['jenis']) == 'masuk') ? $t['jumlah'] : 0;
                            $keluar = (strtolower($t['jenis']) == 'keluar') ? $t['jumlah'] : 0;

                            // Update saldo berjalan
                            $running_saldo += ($masuk - $keluar);
                    ?>
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="font-bold text-slate-600 text-xs">
                                    <?= date('d/m/Y', strtotime($t['tanggal'])); ?>
                                </td>
                                <td class="text-slate-700 uppercase text-xs">
                                    <?= htmlspecialchars($t['keterangan']); ?>
                                </td>
                                <td class="text-center text-emerald-600 font-black italic">
                                    <?= $masuk ? '+' . number_format($masuk) : '-' ?>
                                </td>
                                <td class="text-center text-rose-600 font-black italic">
                                    <?= $keluar ? '-' . number_format($keluar) : '-' ?>
                                </td>
                                <td class="text-center font-black text-slate-800 bg-slate-50/30">
                                    <?= number_format($running_saldo) ?>
                                </td>
                            </tr>
                        <?php endforeach;
                    else: ?>

                        <tr>
                            <td colspan="5" class="text-center py-20 text-slate-400 italic">
                                Tidak ada mutasi barang pada periode ini.
                            </td>
                        </tr>
                    <?php endif; ?>

                </tbody>
            </table>

            <div class="mt-16 hidden print:grid grid-cols-2 text-center">
                <div></div>
                <div class="flex flex-col items-center ml-auto w-64">
                    <p class="text-[10px] font-bold text-slate-400 mb-20">Yogyakarta, <?= date('d F Y') ?></p>
                    <div class="w-full border-t-2 border-slate-800"></div>
                    <p class="font-bold text-slate-800 tracking-widest text-[10px] mt-1">Kord. Workshop PT MMM</p>
                </div>
            </div>
        </div>
    <?php endif; ?>
</main>

<?php include 'templates/footer.php'; ?>