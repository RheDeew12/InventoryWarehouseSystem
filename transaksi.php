<?php
// 1. Inisialisasi Session & Database
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SESSION['role'] !== 'admin') {
    // Jika bukan admin, arahkan kembali ke dashboard dengan pesan error
    header("Location: dashboard.php?error=akses_ditolak");
    exit;
}

include 'config/database.php';

// 2. Proteksi Halaman
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

// 3. Header & Sidebar
include 'templates/header.php';
include 'templates/sidebar.php';

// 4. Ambil data transaksi dengan Join ke tabel barang
$data = mysqli_query($conn, "
    SELECT t.*, b.nama_barang, b.satuan, b.kode_barang 
    FROM transaksi_barang t
    JOIN barang b ON t.id_barang = b.id_barang
    ORDER BY t.tanggal DESC, t.id_transaksi DESC
");

// 5. Hitung Ringkasan Statistik
$q_stats = mysqli_query($conn, "
    SELECT 
        SUM(CASE WHEN LOWER(TRIM(jenis)) = 'masuk' THEN jumlah ELSE 0 END) as masuk,
        SUM(CASE WHEN LOWER(TRIM(jenis)) = 'keluar' THEN jumlah ELSE 0 END) as keluar
    FROM transaksi_barang
");
$stats = mysqli_fetch_assoc($q_stats);
$total_masuk = $stats['masuk'] ?? 0;
$total_keluar = $stats['keluar'] ?? 0;
?>

<main class="flex-1 bg-slate-50 min-h-screen p-4 md:p-8 w-full">
    
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8 w-full">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Riwayat Transaksi</h1>
            <p class="text-sm text-slate-500 mt-1">Pantau arus masuk dan keluar barang gudang PT MMM</p>
        </div>
        
        <div class="flex items-center gap-3">
            <div class="relative hidden sm:block">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">🔍</span>
                <input type="text" id="searchTransaksi" placeholder="Cari kode, nama, atau ket..." 
                    class="pl-10 pr-4 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white w-64 md:w-80 transition-all shadow-sm">
            </div>

            <button onclick="document.getElementById('modal').classList.remove('hidden')"
                class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl shadow-lg shadow-blue-600/20 transition-all flex items-center gap-2 font-semibold text-sm">
                <span class="text-lg">+</span> Transaksi Baru
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8 w-full">
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-5">
            <div class="w-14 h-14 bg-slate-100 text-slate-600 rounded-2xl flex items-center justify-center text-2xl">🔄</div>
            <div>
                <p class="text-xs text-slate-400 font-bold uppercase tracking-widest">Total Record</p>
                <p class="text-2xl font-black text-slate-800"><?= mysqli_num_rows($data); ?></p>
            </div>
        </div>
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-5">
            <div class="w-14 h-14 bg-emerald-100 text-emerald-600 rounded-2xl flex items-center justify-center text-2xl">📥</div>
            <div>
                <p class="text-xs text-slate-400 font-bold uppercase tracking-widest">Barang Masuk</p>
                <p class="text-2xl font-black text-slate-800"><?= number_format($total_masuk); ?></p>
            </div>
        </div>
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-5">
            <div class="w-14 h-14 bg-rose-100 text-rose-600 rounded-2xl flex items-center justify-center text-2xl">📤</div>
            <div>
                <p class="text-xs text-slate-400 font-bold uppercase tracking-widest">Barang Keluar</p>
                <p class="text-2xl font-black text-slate-800"><?= number_format($total_keluar); ?></p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden w-full">
        <div class="overflow-x-auto">
            <table class="w-full text-left table-auto" id="tableTransaksi">
                <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-100">
                        <th class="px-6 py-5 text-xs font-bold text-slate-500 uppercase tracking-wider">Tanggal</th>
                        <th class="px-6 py-5 text-xs font-bold text-slate-500 uppercase tracking-wider">Kode</th>
                        <th class="px-6 py-5 text-xs font-bold text-slate-500 uppercase tracking-wider">Nama Barang</th>
                        <th class="px-6 py-5 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Jenis</th>
                        <th class="px-6 py-5 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Jumlah</th>
                        <th class="px-6 py-5 text-xs font-bold text-slate-500 uppercase tracking-wider">Keterangan</th>
                        <th class="px-6 py-5 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php while($t = mysqli_fetch_assoc($data)) { 
                        $jenis_bersih = trim(strtolower($t['jenis']));
                    ?>
                    <tr class="hover:bg-slate-50/50 transition-colors group">
                        <td class="px-6 py-4">
                            <span class="text-sm font-medium text-slate-600"><?= date('d M Y', strtotime($t['tanggal'])); ?></span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-xs font-mono font-bold text-blue-600 bg-blue-50 px-2 py-1 rounded uppercase tracking-wider">
                                <?= htmlspecialchars($t['kode_barang']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm font-bold text-slate-700 group-hover:text-blue-600 transition-colors">
                                <?= htmlspecialchars($t['nama_barang']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <?php if($jenis_bersih == 'masuk'): ?>
                                <span class="px-3 py-1 text-[10px] font-black uppercase rounded-full bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200">Masuk</span>
                            <?php else: ?>
                                <span class="px-3 py-1 text-[10px] font-black uppercase rounded-full bg-rose-100 text-rose-700 ring-1 ring-rose-200">Keluar</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex flex-col items-end">
                                <span class="font-black text-base <?= $jenis_bersih == 'masuk' ? 'text-emerald-600' : 'text-rose-600'; ?>">
                                    <?= $jenis_bersih == 'masuk' ? '+' : '-'; ?> <?= number_format($t['jumlah']); ?>
                                </span>
                                <span class="text-[9px] text-slate-400 font-bold uppercase tracking-tighter"><?= htmlspecialchars($t['satuan']); ?></span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-xs text-slate-500 italic truncate max-w-[150px]" title="<?= htmlspecialchars($t['keterangan'] ?? ''); ?>">
                                <?= !empty($t['keterangan']) ? htmlspecialchars($t['keterangan']) : '<span class="text-slate-300">Tanpa catatan</span>'; ?>
                            </p>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <button onclick='openEditModal(<?= json_encode($t); ?>)' 
                                    class="p-2 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors" title="Edit">
                                    ✏️
                                </button>
                                <a href="transaksi_hapus.php?id=<?= $t['id_transaksi']; ?>" 
                                   onclick="return confirm('Hapus transaksi ini? Stok barang akan otomatis dikembalikan ke kondisi sebelumnya.')"
                                   class="p-2 text-rose-600 hover:bg-rose-50 rounded-lg transition-colors" title="Hapus">
                                    🗑️
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<div id="modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center z-[100] p-4">
    <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="bg-slate-50 px-8 py-5 border-b border-slate-100 flex justify-between items-center">
            <h2 class="font-bold text-slate-700 text-lg uppercase tracking-tight">Input Transaksi Baru</h2>
            <button onclick="document.getElementById('modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-2xl">&times;</button>
        </div>
        
        <form action="transaksi_simpan.php" method="POST" class="p-8 space-y-5">
            <div class="space-y-2">
                <label class="text-xs font-black text-slate-500 uppercase tracking-widest">Pilih Produk</label>
                <select name="id_barang" required class="w-full bg-slate-50 border-2 border-slate-100 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500 transition-all">
                    <option value="">-- Cari Nama Barang --</option>
                    <?php
                    $barang = mysqli_query($conn, "SELECT id_barang, nama_barang, kode_barang, stok_awal FROM barang ORDER BY nama_barang");
                    while($b = mysqli_fetch_assoc($barang)) { ?>
                        <option value="<?= $b['id_barang']; ?>">
                            <?= htmlspecialchars($b['nama_barang']); ?> (Stok: <?= $b['stok_awal']; ?>)
                        </option>
                    <?php } ?>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-5">
                <div class="space-y-2">
                    <label class="text-xs font-black text-slate-500 uppercase tracking-widest">Jenis Arus</label>
                    <select name="jenis" required class="w-full bg-slate-50 border-2 border-slate-100 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500 transition-all">
                        <option value="masuk">📥 Barang Masuk</option>
                        <option value="keluar">📤 Barang Keluar</option>
                    </select>
                </div>
                <div class="space-y-2">
                    <label class="text-xs font-black text-slate-500 uppercase tracking-widest">Jumlah Unit</label>
                    <input type="number" name="jumlah" placeholder="0" min="1" required class="w-full bg-slate-50 border-2 border-slate-100 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500 transition-all font-bold">
                </div>
            </div>

            <div class="space-y-2">
                <label class="text-xs font-black text-slate-500 uppercase tracking-widest">Tanggal Transaksi</label>
                <input type="date" name="tanggal" value="<?= date('Y-m-d'); ?>" required class="w-full bg-slate-50 border-2 border-slate-100 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500 transition-all">
            </div>

            <div class="space-y-2">
                <label class="text-xs font-black text-slate-500 uppercase tracking-widest">Keterangan / Tujuan</label>
                <textarea name="keterangan" rows="3" placeholder="Restock atau Pengiriman..." class="w-full bg-slate-50 border-2 border-slate-100 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-500 transition-all resize-none"></textarea>
            </div>

            <div class="flex justify-end gap-3 pt-6">
                <button type="button" onclick="document.getElementById('modal').classList.add('hidden')" class="px-6 py-2.5 text-xs font-black text-slate-400">Batal</button>
                <button type="submit" class="px-8 py-2.5 text-xs font-black text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-lg transition-all uppercase tracking-widest">Simpan Data</button>
            </div>
        </form>
    </div>
</div>

<div id="modalEdit" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center z-[100] p-4">
    <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="bg-amber-50 px-8 py-5 border-b border-slate-100 flex justify-between items-center">
            <h2 class="font-bold text-slate-700 text-lg uppercase tracking-tight">Edit Riwayat Transaksi</h2>
            <button onclick="document.getElementById('modalEdit').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-2xl">&times;</button>
        </div>
        
        <form action="transaksi_edit.php" method="POST" class="p-8 space-y-5">
            <input type="hidden" name="id_transaksi" id="edit_id">
            
            <div class="space-y-2">
                <label class="text-xs font-black text-slate-500 uppercase tracking-widest">Nama Barang (Tetap)</label>
                <input type="text" id="edit_nama_barang" disabled class="w-full bg-slate-100 border-2 border-slate-100 rounded-xl px-4 py-3 text-sm text-slate-500 font-bold">
            </div>

            <div class="grid grid-cols-2 gap-5">
                <div class="space-y-2">
                    <label class="text-xs font-black text-slate-500 uppercase tracking-widest">Jenis Arus</label>
                    <select name="jenis" id="edit_jenis" required class="w-full bg-slate-50 border-2 border-slate-100 rounded-xl px-4 py-3 text-sm focus:border-blue-500 outline-none transition-all">
                        <option value="masuk">📥 Barang Masuk</option>
                        <option value="keluar">📤 Barang Keluar</option>
                    </select>
                </div>
                <div class="space-y-2">
                    <label class="text-xs font-black text-slate-500 uppercase tracking-widest">Jumlah Unit</label>
                    <input type="number" name="jumlah" id="edit_jumlah" required class="w-full bg-slate-50 border-2 border-slate-100 rounded-xl px-4 py-3 text-sm focus:border-blue-500 outline-none transition-all font-bold">
                </div>
            </div>

            <div class="space-y-2">
                <label class="text-xs font-black text-slate-500 uppercase tracking-widest">Tanggal</label>
                <input type="date" name="tanggal" id="edit_tanggal" required class="w-full bg-slate-50 border-2 border-slate-100 rounded-xl px-4 py-3 text-sm focus:border-blue-500 outline-none transition-all">
            </div>

            <div class="space-y-2">
                <label class="text-xs font-black text-slate-500 uppercase tracking-widest">Keterangan / Catatan</label>
                <textarea name="keterangan" id="edit_keterangan" rows="3" class="w-full bg-slate-50 border-2 border-slate-100 rounded-xl px-4 py-3 text-sm focus:border-blue-500 outline-none transition-all resize-none"></textarea>
            </div>

            <div class="flex justify-end gap-3 pt-6 border-t border-slate-50">
                <button type="button" onclick="document.getElementById('modalEdit').classList.add('hidden')" class="px-6 py-2.5 text-xs font-black text-slate-400">Batal</button>
                <button type="submit" class="px-8 py-2.5 text-xs font-black text-white bg-amber-500 hover:bg-amber-600 rounded-xl shadow-lg transition-all uppercase tracking-widest">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
    // FUNGSI MODAL EDIT: Mengisi data otomatis saat tombol pensil diklik
    function openEditModal(data) {
        document.getElementById('edit_id').value = data.id_transaksi;
        document.getElementById('edit_nama_barang').value = data.nama_barang;
        document.getElementById('edit_jenis').value = data.jenis.toLowerCase().trim();
        document.getElementById('edit_jumlah').value = data.jumlah;
        document.getElementById('edit_tanggal').value = data.tanggal;
        document.getElementById('edit_keterangan').value = data.keterangan;
        
        document.getElementById('modalEdit').classList.remove('hidden');
    }

    // Search Real-time
    document.getElementById('searchTransaksi').addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        let rows = document.querySelectorAll('#tableTransaksi tbody tr');

        rows.forEach(row => {
            let kode = row.cells[1].innerText.toLowerCase();
            let nama = row.cells[2].innerText.toLowerCase();
            let ket = row.cells[5].innerText.toLowerCase();
            
            if (kode.includes(filter) || nama.includes(filter) || ket.includes(filter)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    // Close Modals on Overlay Click
    window.onclick = function(event) {
        let modalTambah = document.getElementById('modal');
        let modalEdit = document.getElementById('modalEdit');
        if (event.target == modalTambah) modalTambah.classList.add('hidden');
        if (event.target == modalEdit) modalEdit.classList.add('hidden');
    }
</script>

<?php include 'templates/footer.php'; ?>