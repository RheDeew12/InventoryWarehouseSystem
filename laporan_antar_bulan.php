<?php
include 'config/database.php'; 
include 'templates/header.php';
include 'templates/sidebar.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

// 1. Tangkap filter
$id_barang   = isset($_GET['id_barang']) ? intval($_GET['id_barang']) : '';
$bulan_awal  = isset($_GET['bulan_awal']) ? intval($_GET['bulan_awal']) : '';
$bulan_akhir = isset($_GET['bulan_akhir']) ? intval($_GET['bulan_akhir']) : '';
$tahun       = isset($_GET['tahun']) ? intval($_GET['tahun']) : date('Y');

// 2. Query Daftar Barang
$barang_query = mysqli_query($conn, "SELECT * FROM barang ORDER BY nama_barang");
?>

<style>
    .container-proportional { max-width: 1440px; margin: 0 auto; }
    .table-compact th, .table-compact td { padding: 0.75rem 1rem !important; font-size: 0.875rem; }

    @media print {
        .no-print, aside, nav, header, form, button { display: none !important; }
        body { background: white !important; }
        main { margin: 0 !important; padding: 0 !important; width: 100% !important; }
        .table-compact th, .table-compact td {
            padding: 3px 6px !important; 
            font-size: 9pt !important;
            border: 1px solid #000 !important;
            line-height: 1.2 !important;
        }
        .print-header { display: block !important; text-align: center; border-bottom: 2px solid #000; margin-bottom: 10px; }
    }
</style>

<main class="flex-1 bg-slate-50 min-h-screen p-4 md:p-8 w-full">
  <div class="container-proportional">
      
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8 no-print">
        <div>
          <h1 class="text-3xl font-black text-slate-800 tracking-tight">Mutasi Antar Bulan</h1>
          <p class="text-sm text-slate-500 mt-1">Analisis pergerakan stok barang gudang PT MMM</p>
        </div>
        <button type="button" onclick="window.print()" class="flex items-center gap-2 px-6 py-2.5 bg-blue-600 text-white rounded-xl text-sm font-bold shadow-lg hover:bg-blue-700 transition-all">
          🖨️ Cetak Laporan
        </button>
      </div>

      <div class="bg-white rounded-3xl shadow-sm border border-slate-200/60 p-6 mb-8 no-print">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-6">
          <div class="space-y-2">
            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Bulan Awal</label>
            <select name="bulan_awal" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-bold outline-none focus:border-blue-500 transition-all">
              <option value="">Pilih Bulan</option>
              <?php for ($i=1;$i<=12;$i++): ?>
                <option value="<?= $i ?>" <?= ($bulan_awal==$i)?'selected':''; ?>><?= date('F', mktime(0,0,0,$i,1)) ?></option>
              <?php endfor; ?>
            </select>
          </div>

          <div class="space-y-2">
            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Bulan Akhir</label>
            <select name="bulan_akhir" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-bold outline-none focus:border-blue-500 transition-all">
              <option value="">Pilih Bulan</option>
              <?php for ($i=1;$i<=12;$i++): ?>
                <option value="<?= $i ?>" <?= ($bulan_akhir==$i)?'selected':''; ?>><?= date('F', mktime(0,0,0,$i,1)) ?></option>
              <?php endfor; ?>
            </select>
          </div>

          <div class="space-y-2">
            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Tahun</label>
            <select name="tahun" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-bold outline-none focus:border-blue-500 transition-all">
              <?php for ($t=date('Y'); $t>=2020; $t--): ?>
                <option value="<?= $t ?>" <?= ($tahun==$t)?'selected':''; ?>><?= $t ?></option>
              <?php endfor; ?>
            </select>
          </div>

          <div class="space-y-2">
            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Pilih Produk</label>
            <select name="id_barang" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-bold outline-none focus:border-blue-500 transition-all">
              <option value="">Semua Produk</option>
              <?php mysqli_data_seek($barang_query, 0); while($b = mysqli_fetch_assoc($barang_query)): ?>
                <option value="<?= $b['id_barang'] ?>" <?= ($id_barang==$b['id_barang'])?'selected':''; ?>>
                  <?= htmlspecialchars($b['nama_barang']) ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>

          <div class="md:col-span-4 flex justify-end">
            <button type="submit" name="preview" value="1" class="px-10 py-3 bg-slate-800 text-white rounded-xl text-sm font-black shadow-lg hover:bg-black transition-all uppercase tracking-widest">
              Tampilkan Laporan
            </button>
          </div>
        </form>
      </div>

      <?php if(isset($_GET['preview'])): ?>
        <?php
            // Query Transaksi sesuai range bulan
            $sql = "SELECT t.*, b.kode_barang, b.nama_barang, b.satuan
                    FROM transaksi_barang t
                    JOIN barang b ON t.id_barang = b.id_barang
                    WHERE YEAR(t.tanggal) = '$tahun' 
                    AND MONTH(t.tanggal) BETWEEN '$bulan_awal' AND '$bulan_akhir'";
            
            if($id_barang) $sql .= " AND t.id_barang = '$id_barang'";
            $sql .= " ORDER BY t.tanggal ASC, t.id_transaksi ASC";
            $res = mysqli_query($conn, $sql);

            $stok_per_barang = []; 
        ?>

        <div id="preview-table">
            <div class="bg-white rounded-[2rem] shadow-sm border border-slate-200/60 overflow-hidden">
                <div class="hidden print:block p-6 text-center border-b-2 border-slate-800 mb-6">
                    <h2 class="text-xl font-bold uppercase">Laporan Mutasi Stok Antar Bulan</h2>
                    <p class="text-sm font-bold">PT MUARA MITRA MANDIRI</p>
                    <p class="text-xs italic">Periode: <?= date('F', mktime(0,0,0,$bulan_awal,1)) ?> - <?= date('F', mktime(0,0,0,$bulan_akhir,1)) ?> <?= $tahun ?></p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse table-compact">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100">
                                <th class="font-bold text-slate-500 uppercase tracking-widest">Tanggal</th>
                                <th class="font-bold text-slate-500 uppercase tracking-widest text-center">Kode</th>
                                <th class="font-bold text-slate-500 uppercase tracking-widest">Nama Produk</th>
                                <th class="font-bold text-slate-500 uppercase tracking-widest text-center">Masuk</th>
                                <th class="font-bold text-slate-500 uppercase tracking-widest text-center">Keluar</th>
                                <th class="font-bold text-slate-500 uppercase tracking-widest text-center bg-slate-50">Sisa Stok</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php
                            if(mysqli_num_rows($res) > 0){
                              while($row = mysqli_fetch_assoc($res)){
                                $id = $row['id_barang'];
                                
                                // LOGIKA BARU: Hitung saldo awal barang s/d sebelum bulan awal filter
                                if(!isset($stok_per_barang[$id])) {
                                    $tgl_limit = "$tahun-$bulan_awal-01";
                                    $q_awal = mysqli_query($conn, "SELECT 
                                        (SUM(CASE WHEN jenis='masuk' THEN jumlah ELSE 0 END) - 
                                         SUM(CASE WHEN jenis='keluar' THEN jumlah ELSE 0 END)) as saldo_lalu
                                        FROM transaksi_barang 
                                        WHERE id_barang = '$id' AND tanggal < '$tgl_limit'");
                                    $d_awal = mysqli_fetch_assoc($q_awal);
                                    
                                    // Mulai dari 0 (asumsi stok awal master barang sudah masuk ke tabel transaksi sebagai 'masuk')
                                    $stok_per_barang[$id] = $d_awal['saldo_lalu'] ?? 0;
                                }

                                $masuk = strtolower($row['jenis'])=='masuk' ? $row['jumlah'] : 0;
                                $keluar = strtolower($row['jenis'])=='keluar' ? $row['jumlah'] : 0;
                                
                                // Saldo Berjalan
                                $stok_per_barang[$id] += ($masuk - $keluar);
                            ?>
                            <tr class="hover:bg-slate-50/50 transition-colors">
                              <td class="font-bold text-slate-600">
                                  <?= date('d/m/Y', strtotime($row['tanggal'])) ?>
                              </td>
                              <td class="text-center">
                                  <span class="text-[11px] font-mono font-bold text-blue-600 uppercase tracking-tighter">
                                      <?= $row['kode_barang'] ?>
                                  </span>
                              </td>
                              <td class="font-bold text-slate-800 uppercase">
                                  <?= htmlspecialchars($row['nama_barang']) ?>
                              </td>
                              <td class="text-center font-black text-emerald-600">
                                  <?= $masuk ? '+'.number_format($masuk) : '-' ?>
                              </td>
                              <td class="text-center font-black text-rose-600">
                                  <?= $keluar ? '-'.number_format($keluar) : '-' ?>
                              </td>
                              <td class="text-center font-black text-slate-900 bg-slate-50/30">
                                  <?= number_format($stok_per_barang[$id]) ?>
                              </td>
                            </tr>
                            <?php } ?>
                            <?php } else { ?>
                            <tr>
                                <td colspan="6" class="p-20 text-center">
                                    <p class="text-slate-400 font-black italic uppercase tracking-widest">Data tidak ditemukan</p>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="hidden print:block mt-10">
                    <div class="flex justify-between items-start px-10">
                        <div class="text-center w-56">
                            <p class="text-[10px] mb-16">Mengetahui,</p>
                            <p class="text-[10px] font-bold border-t border-black pt-1">Direksi PT MMM</p>
                        </div>
                        <div class="text-center w-56">
                            <p class="text-[10px] mb-2 text-right italic">Dicetak pada: <?= date('d/m/Y') ?></p>
                            <p class="text-[10px] mb-16">Kord. Workshop,</p>
                            <p class="text-[10px] font-bold border-t border-black pt-1">Nopri Adrian</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
      <?php endif; ?>
  </div>
</main>

<?php include 'templates/footer.php'; ?>