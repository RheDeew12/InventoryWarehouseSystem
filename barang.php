<?php
// Pastikan database di-include terlebih dahulu
include 'config/database.php';

// Cek jika session belum berjalan, baru jalankan session_start()
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteksi halaman: cek login
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

if ($_SESSION['role'] !== 'admin') {
    // Jika bukan admin, arahkan kembali ke dashboard dengan pesan error
    header("Location: dashboard.php?error=akses_ditolak");
    exit;
}

// 1. Ambil semua data barang
$data = mysqli_query($conn, "SELECT * FROM barang ORDER BY nama_barang ASC");

// 2. Logika Dinamis: Hitung Stok Rendah (Ambang batas: 5)
$ambang_batas = 5;
$query_rendah = mysqli_query($conn, "SELECT COUNT(*) as total FROM barang WHERE stok_awal < $ambang_batas");
$res_rendah = mysqli_fetch_assoc($query_rendah);
$total_stok_rendah = $res_rendah['total'];

// Include template
include 'templates/header.php';
include 'templates/sidebar.php';
?>

<style>
    .input-field { 
        @apply w-full bg-white border-2 border-slate-200 rounded-lg px-4 py-2.5 text-slate-900 font-medium focus:outline-none focus:border-cyan-500 transition-all outline-none; 
    }
    .modal-overlay { 
        display: none; 
        @apply fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center z-[100] p-4; 
    }
    .modal-active { display: flex !important; }
    .fade-in-down { animation: fadeInDown 0.2s ease-out; }
    @keyframes fadeInDown {
        from { opacity: 0; transform: translateY(-5px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<main class="flex-1 bg-slate-50 min-h-screen p-4 md:p-8">
    
    <?php if (isset($_GET['status'])): ?>
        <div class="mb-6 animate-in fade-in slide-in-from-top-4 duration-300">
            <?php if ($_GET['status'] == 'success'): ?>
                <div class="bg-emerald-50 border-l-4 border-emerald-500 p-4 rounded-xl flex items-center gap-3">
                    <span class="text-emerald-600 text-xl">✅</span>
                    <p class="text-emerald-800 font-bold text-sm">Data inventaris berhasil diperbarui!</p>
                </div>
            <?php elseif ($_GET['status'] == 'duplicate'): ?>
                <div class="bg-rose-50 border-l-4 border-rose-500 p-4 rounded-xl flex items-center gap-3">
                    <span class="text-rose-600 text-xl">❌</span>
                    <p class="text-rose-800 font-bold text-sm">Gagal! Kode Barang <strong><?= htmlspecialchars($_GET['kode'] ?? '') ?></strong> sudah terdaftar.</p>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Manajemen Inventaris</h1>
            <p class="text-sm text-slate-500 mt-1">Kelola daftar produk dan stok gudang PT MMM</p>
        </div>
        
        <div class="flex items-center gap-3">
            <div class="relative hidden sm:block">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">🔍</span>
                <input type="text" id="searchInput" placeholder="Cari nama atau kode barang..." 
                       class="pl-10 pr-4 py-2 border-2 border-slate-200 rounded-xl text-sm focus:outline-none focus:border-cyan-500 bg-white w-64 transition-all">
            </div>

            <button onclick="toggleModal('modal', true)"
                class="bg-cyan-600 hover:bg-cyan-700 text-white px-5 py-2.5 rounded-xl shadow-lg shadow-cyan-600/20 transition-all flex items-center gap-2 font-bold text-sm">
                <span class="text-lg">+</span> Tambah Barang
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-4">
            <div class="w-12 h-12 bg-cyan-100 text-cyan-600 rounded-xl flex items-center justify-center text-xl">📦</div>
            <div>
                <p class="text-xs text-slate-500 font-black uppercase tracking-widest">Total Item</p>
                <p class="text-2xl font-bold text-slate-900"><?= mysqli_num_rows($data); ?></p>
            </div>
        </div>
        
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-4">
            <div class="w-12 h-12 bg-amber-100 text-amber-600 rounded-xl flex items-center justify-center text-xl">⚠️</div>
            <div>
                <p class="text-xs text-slate-500 font-black uppercase tracking-widest">Stok Rendah</p>
                <p class="text-2xl font-bold <?= ($total_stok_rendah > 0) ? 'text-red-600' : 'text-slate-900'; ?>">
                    <?= $total_stok_rendah; ?> Item
                </p> 
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-4">
            <div class="w-12 h-12 bg-green-100 text-green-600 rounded-xl flex items-center justify-center text-xl">📅</div>
            <div>
                <p class="text-xs text-slate-500 font-black uppercase tracking-widest">Hari Ini</p>
                <p class="text-sm font-bold text-slate-900"><?= date('d M Y'); ?></p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left" id="inventoryTable">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100">
                        <th class="px-6 py-4 text-xs font-black text-slate-500 uppercase tracking-widest">Kode</th>
                        <th class="px-6 py-4 text-xs font-black text-slate-500 uppercase tracking-widest">Nama Barang</th>
                        <th class="px-6 py-4 text-xs font-black text-slate-500 uppercase tracking-widest">Kategori</th>
                        <th class="px-6 py-4 text-xs font-black text-slate-500 uppercase tracking-widest">Stok</th>
                        <th class="px-6 py-4 text-xs font-black text-slate-500 uppercase tracking-widest">Tgl Masuk</th>
                        <th class="px-6 py-4 text-xs font-black text-slate-500 uppercase tracking-widest text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php 
                    mysqli_data_seek($data, 0); 
                    while ($b = mysqli_fetch_assoc($data)) { 
                        $isLow = ($b['stok_awal'] < $ambang_batas);
                        $kat = isset($b['kategori']) && $b['kategori'] ? $b['kategori'] : 'Umum';
                    ?>
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4">
                            <span class="text-xs text-cyan-700 font-mono font-bold bg-cyan-50 px-2 py-1 rounded"><?= htmlspecialchars($b['kode_barang']); ?></span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="font-bold text-slate-900 text-sm"><?= htmlspecialchars($b['nama_barang']); ?></span>
                        </td>
                        <td class="px-6 py-4 text-xs uppercase font-bold text-slate-500">
                            <?= htmlspecialchars($kat); ?>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <span class="text-base font-bold <?= $isLow ? 'text-red-600' : 'text-slate-900'; ?>">
                                    <?= number_format($b['stok_awal']); ?>
                                </span>
                                <span class="text-[9px] font-black text-slate-400 uppercase"><?= htmlspecialchars($b['satuan']); ?></span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-700">
                            <?= date('d/m/y', strtotime($b['tanggal_masuk']));?>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex justify-center items-center gap-2">
                                <button onclick="openEditModal('<?= $b['id_barang']; ?>','<?= addslashes($b['kode_barang']); ?>','<?= addslashes($b['nama_barang']); ?>','<?= $b['stok_awal']; ?>','<?= addslashes($b['satuan']); ?>','<?= addslashes($kat); ?>','<?= $b['tanggal_masuk']; ?>','<?= addslashes($b['keterangan'] ?? ''); ?>')"
                                    class="p-2 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors">✏️</button>
                                <a href="barang_hapus.php?id=<?= $b['id_barang']; ?>" 
                                    onclick="return confirm('Hapus barang ini?')"
                                    class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors">🗑️</a>
                            </div>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<div id="modal" class="modal-overlay">
    <div class="bg-white w-full max-w-2xl rounded-2xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="bg-slate-800 px-6 py-4 flex justify-between items-center">
            <h2 class="font-bold text-white tracking-widest uppercase text-sm">Tambah Inventaris Baru</h2>
            <button onclick="toggleModal('modal', false)" class="text-slate-300 hover:text-white text-2xl">&times;</button>
        </div>

        <form action="barang_simpan.php" method="POST" class="p-8 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-widest">Kode Barang</label>
                    <input type="text" name="kode_barang" placeholder="BRG-001" required class="input-field">
                </div>
                <div class="space-y-2">
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-widest">Nama Barang</label>
                    <input type="text" name="nama_barang" placeholder="Masukkan nama produk" required class="input-field">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-widest">Kategori</label>
                    <select id="selectKategoriTambah" name="kategori" onchange="handleHybrid(this, 'manualKategoriTambah')" required class="input-field appearance-none">
                        <option value="" disabled selected>Pilih Kategori</option>
                        <option value="Listrik">PERALATAN PERSIAPAN</option>
                        <option value="Finishing">PERALATAN ELETRONIK</option>
                        <option value="Plumbing">PERALATAN K3</option>
                        <option value="Ricikan">PERALATAN SCAFFOLDING</option>
                        <option value="Besi">MESIN KONTRUKSI</option>
                        <option value="K3 Proyek">HOLLOW BAGESTING</option>
                        <option value="Kendaraan Konstruksi">PERLATAN FORMWORK</option>
                        <option value="Mesin Konstruksi">PERALATAN PERTUKANGAN</option>
                        <option value="Peralatan Pertukangan">PERALATAN PLUMBING</option>
                        <option value="Lainnya">-- Lainnya (Input Manual) --</option>
                    </select>
                    <div id="manualKategoriTambah" class="hidden fade-in-down mt-2">
                        <input type="text" placeholder="Ketik kategori manual..." class="w-full bg-cyan-50 border-2 border-cyan-200 rounded-lg px-4 py-2 text-slate-900 font-bold focus:border-cyan-500 outline-none">
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-widest">Satuan</label>
                    <select id="selectSatuanTambah" name="satuan" onchange="handleHybrid(this, 'manualSatuanTambah')" required class="input-field appearance-none">
                        <option value="" disabled selected>Pilih Satuan</option>
                        <option value="Pcs">Pcs</option>
                        <option value="Box">Box</option>
                        <option value="Unit">Unit</option>
                        <option value="Set">Set</option>
                        <option value="Batang">Batang</option>
                        <option value="KG">KG</option>
                        <option value="Buah">Buah</option>
                        <option value="Lainnya">-- Lainnya (Input Manual) --</option>
                    </select>
                    <div id="manualSatuanTambah" class="hidden fade-in-down mt-2">
                        <input type="text" placeholder="Ketik satuan manual..." class="w-full bg-cyan-50 border-2 border-cyan-200 rounded-lg px-4 py-2 text-slate-900 font-bold focus:border-cyan-500 outline-none">
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-widest">Kuantitas Awal</label>
                    <input type="number" name="stok_awal" value="0" min="0" required class="input-field font-bold">
                </div>
                <div class="space-y-2">
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-widest">Tanggal Masuk</label>
                    <input type="date" name="tanggal_masuk" value="<?= date('Y-m-d') ?>" required class="input-field">
                </div>
            </div>

            <div class="space-y-2">
                <label class="block text-xs font-black text-slate-700 uppercase tracking-widest">Catatan Tambahan</label>
                <textarea name="keterangan" rows="1" placeholder="Info detail..." class="input-field resize-none"></textarea>
            </div>

            <div class="flex justify-end gap-4 pt-6 border-t border-slate-100">
                <button type="button" onclick="toggleModal('modal', false)" class="px-6 py-2.5 text-sm font-bold text-slate-400 hover:text-slate-600">BATAL</button>
                <button type="submit" class="px-8 py-2.5 bg-cyan-600 hover:bg-cyan-700 text-white text-sm font-black rounded-lg shadow-lg shadow-cyan-200 transition-all">SIMPAN BARANG</button>
            </div>
        </form>
    </div>
</div>

<div id="modalEdit" class="modal-overlay">
    <div class="bg-white w-full max-w-2xl rounded-2xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="bg-amber-500 px-6 py-4 flex justify-between items-center">
            <h2 class="font-bold text-white tracking-widest uppercase text-sm">Perbarui Data Barang</h2>
            <button onclick="toggleModal('modalEdit', false)" class="text-white text-2xl">&times;</button>
        </div>

        <form action="barang_update.php" method="POST" class="p-8 space-y-6">
            <input type="hidden" name="id_barang" id="edit_id">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-widest">Kode Barang</label>
                    <input type="text" name="kode_barang" id="edit_kode" required class="input-field font-bold bg-slate-50 border-amber-100">
                </div>
                <div class="space-y-2">
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-widest">Nama Barang</label>
                    <input type="text" name="nama_barang" id="edit_nama" required class="input-field">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-widest">Kategori</label>
                    <select id="edit_kategori" name="kategori" onchange="handleHybrid(this, 'manualKategoriEdit')" required class="input-field appearance-none">
                        <option value="Listrik">Material Listrik</option>
                        <option value="Finishing">Material Finishing</option>
                        <option value="Plumbing">Material Plumbing</option>
                        <option value="Ricikan">Material Ricikan</option>
                        <option value="Besi">Material Besi</option>
                        <option value="K3 Proyek">K3 Proyek</option>
                        <option value="Kendaraan Konstruksi">Kendaraan Konstruksi</option>
                        <option value="Mesin Konstruksi">Mesin Konstruksi</option>
                        <option value="Peralatan Pertukangan">Peralatan Pertukangan</option>
                        <option value="Lainnya">-- Lainnya (Input Manual) --</option>
                    </select>
                    <div id="manualKategoriEdit" class="hidden fade-in-down mt-2">
                        <input type="text" id="edit_kategori_manual" placeholder="Ketik kategori baru..." class="w-full bg-amber-50 border-2 border-amber-200 rounded-lg px-4 py-2 text-slate-900 font-bold focus:border-amber-500 outline-none">
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-widest">Satuan</label>
                    <select id="edit_satuan" name="satuan" onchange="handleHybrid(this, 'manualSatuanEdit')" required class="input-field appearance-none">
                        <option value="Pcs">Pcs</option>
                        <option value="Box">Box</option>
                        <option value="Unit">Unit</option>
                        <option value="Set">Set</option>
                        <option value="Batang">Batang</option>
                        <option value="KG">KG</option>
                        <option value="Buah">Buah</option>
                        <option value="Lainnya">-- Lainnya (Input Manual) --</option>
                    </select>
                    <div id="manualSatuanEdit" class="hidden fade-in-down mt-2">
                        <input type="text" id="edit_satuan_manual" placeholder="Ketik satuan baru..." class="w-full bg-amber-50 border-2 border-amber-200 rounded-lg px-4 py-2 text-slate-900 font-bold focus:border-amber-500 outline-none">
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-widest">Stok Saat Ini</label>
                    <input type="number" name="stok_awal" id="edit_stok" required class="input-field font-bold">
                </div>
                <div class="space-y-2">
                    <label class="block text-xs font-black text-slate-700 uppercase tracking-widest">Tanggal Update</label>
                    <input type="date" name="tanggal_masuk" id="edit_tanggal" required class="input-field">
                </div>
            </div>

            <div class="space-y-2">
                <label class="block text-xs font-black text-slate-700 uppercase tracking-widest">Keterangan</label>
                <textarea name="keterangan" id="edit_keterangan" rows="1" class="input-field resize-none"></textarea>
            </div>

            <div class="flex justify-end gap-4 pt-6 border-t border-slate-100">
                <button type="button" onclick="toggleModal('modalEdit', false)" class="px-6 py-2.5 text-sm font-bold text-slate-400 hover:text-slate-600">BATAL</button>
                <button type="submit" class="px-8 py-2.5 bg-amber-500 hover:bg-amber-600 text-white text-sm font-black rounded-lg shadow-lg shadow-amber-200 transition-all">SIMPAN PERUBAHAN</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleModal(modalId, show) {
    const modal = document.getElementById(modalId);
    if (show) {
        modal.classList.add('modal-active');
    } else {
        modal.classList.remove('modal-active');
        // Reset manual fields if closing add modal
        if(modalId === 'modal') {
            resetManual('selectSatuanTambah', 'manualSatuanTambah', 'satuan');
            resetManual('selectKategoriTambah', 'manualKategoriTambah', 'kategori');
        }
    }
}

// FUNGSI HYBRID TERBARU: Mencegah nama input ganda
function handleHybrid(selectElem, containerId) {
    const container = document.getElementById(containerId);
    const manualInput = container.querySelector('input');
    const isSatuan = containerId.toLowerCase().includes('satuan');
    const fieldName = isSatuan ? 'satuan' : 'kategori';

    if (selectElem.value === 'Lainnya') {
        container.classList.remove('hidden');
        manualInput.setAttribute('required', 'true');
        manualInput.setAttribute('name', fieldName); // Input manual ambil alih nama variabel
        selectElem.removeAttribute('name');           // Select melepaskan nama variabel agar tidak konflik
        manualInput.focus();
    } else {
        container.classList.add('hidden');
        manualInput.removeAttribute('required');
        manualInput.removeAttribute('name');          // Input manual melepaskan nama
        selectElem.setAttribute('name', fieldName);   // Select mengambil alih nama variabel
    }
}

function resetManual(selectId, containerId, originalName) {
    const sel = document.getElementById(selectId);
    const con = document.getElementById(containerId);
    con.classList.add('hidden');
    sel.setAttribute('name', originalName);
    const inp = con.querySelector('input');
    inp.removeAttribute('name');
    inp.value = "";
}

function openEditModal(id, kode, nama, stok, satuan, kategori, tanggal, ket) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_kode').value = kode;
    document.getElementById('edit_nama').value = nama;
    document.getElementById('edit_stok').value = stok;
    document.getElementById('edit_tanggal').value = tanggal;
    document.getElementById('edit_keterangan').value = ket;

    // Sinkronisasi Field Hybrid untuk Satuan & Kategori
    syncField('edit_satuan', 'edit_satuan_manual', 'manualSatuanEdit', satuan, 'satuan');
    syncField('edit_kategori', 'edit_kategori_manual', 'manualKategoriEdit', kategori, 'kategori');
    
    toggleModal('modalEdit', true);
}

function syncField(selectId, manualId, containerId, value, fieldName) {
    const sel = document.getElementById(selectId);
    const man = document.getElementById(manualId);
    const con = document.getElementById(containerId);
    
    let exists = false;
    for(let i=0; i<sel.options.length; i++) {
        if(sel.options[i].value === value) { exists = true; break; }
    }

    if(exists) {
        sel.value = value;
        sel.setAttribute('name', fieldName);
        con.classList.add('hidden');
        man.removeAttribute('name');
        man.value = "";
    } else {
        sel.value = 'Lainnya';
        sel.removeAttribute('name');
        con.classList.remove('hidden');
        man.value = value;
        man.setAttribute('name', fieldName);
    }
}

// Pencarian Tabel
document.getElementById('searchInput').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('#inventoryTable tbody tr');
    
    rows.forEach(row => {
        let kode = row.cells[0].innerText.toLowerCase();
        let nama = row.cells[1].innerText.toLowerCase();
        let kategori = row.cells[2].innerText.toLowerCase();
        
        if (kode.includes(filter) || nama.includes(filter) || kategori.includes(filter)) {
            row.style.display = "";
        } else {
            row.style.display = "none";
        }
    });
});

window.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-overlay')) toggleModal(e.target.id, false);
});
</script>

<?php include 'templates/footer.php'; ?>