<button id="sidebarToggle" class="fixed top-4 left-4 z-[110] md:hidden bg-[#111827] text-white p-2 rounded-lg shadow-lg no-print">
    ☰
</button>

<div id="sidebarOverlay" class="fixed inset-0 bg-black/50 z-[100] hidden md:hidden no-print"></div>

<aside id="mainSidebar" class="fixed md:sticky top-0 left-0 w-72 bg-[#111827] text-slate-300 flex flex-col h-screen shadow-2xl border-r border-slate-800 transition-all duration-300 z-[105] -translate-x-full md:translate-x-0 no-print">
  
  <div class="px-6 py-7 border-b border-slate-800/50 bg-[#1f2937]/30 flex-shrink-0">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 bg-white p-1.5 rounded-lg shadow-md flex-shrink-0">
            <img src="../img/logoMMM.png" alt="Logo PT MMM" class="w-full h-full object-contain">
        </div>
        <div class="overflow-hidden">
            <h1 class="text-white font-bold text-xs leading-tight tracking-tight truncate">PT MUARA MITRA MANDIRI</h1>
            <p class="text-[8px] text-cyan-500 font-extrabold uppercase tracking-widest mt-0.5">WAREHOUSE SYSTEM</p>
        </div>
    </div>
  </div>

  <div class="px-6 py-4 bg-slate-800/10 flex items-center gap-3 border-b border-slate-800/50 flex-shrink-0">
    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-cyan-600 to-blue-700 flex items-center justify-center text-white text-xs font-bold shadow-lg flex-shrink-0">
        <?php echo isset($_SESSION['username']) ? strtoupper(substr($_SESSION['username'], 0, 1)) : 'A'; ?>
    </div>
    <div class="overflow-hidden">
        <p class="text-xs font-semibold text-white truncate max-w-[150px]">
            <?php echo $_SESSION['nama'] ?? 'Administrator'; ?>
        </p>
        <div class="flex items-center gap-1.5 mt-0.5">
            <span class="relative flex h-1.5 w-1.5">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-green-500"></span>
            </span>
            <span class="text-[9px] text-slate-500 font-medium">Sistem Online</span>
        </div>
    </div>
  </div>

  <nav class="flex-1 px-3 py-6 overflow-y-auto scrollbar-thin scrollbar-thumb-slate-800 space-y-8">
    
    <div class="flex flex-col">
        <p class="px-4 text-[9px] font-bold text-slate-600 uppercase tracking-[0.2em] mb-3">Utama</p>
        <div class="flex flex-col gap-1">
            <a href="dashboard.php" class="sidebar-link group <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <span class="icon">🏠</span>
                <div class="flex flex-col">
                <span class="text-sm font-medium">Dashboard</span>
                <span class="text-[9px] text-slate-500 group-hover:text-cyan-400 italic">Tampilan Utama</span>
            </div>
            </a>
      <a href="barang.php" class="sidebar-link group <?php echo basename($_SERVER['PHP_SELF']) == 'barang.php' ? 'active' : ''; ?>">
    <span class="icon">📋</span>
    <div class="flex flex-col">
        <span class="text-sm font-medium">Master Barang</span>
        <span class="text-[9px] text-slate-500 group-hover:text-cyan-400 italic">Update Barang Terbaru</span>
    </div>
    </a>
        </div>
    </div>

    <div class="flex flex-col">
        <p class="px-4 text-[9px] font-bold text-slate-600 uppercase tracking-[0.2em] mb-3">Operasional</p>
        <div class="flex flex-col gap-1">
            <a href="transaksi.php" class="sidebar-link group <?php echo basename($_SERVER['PHP_SELF']) == 'transaksi.php' ? 'active' : ''; ?>">
                <span class="icon">🔄</span>
                <div class="flex flex-col">
                    <span class="text-sm font-medium">Arus Barang</span>
                    <span class="text-[9px] text-slate-500 group-hover:text-cyan-400 italic">Masuk & Keluar</span>
                </div>
            </a>
        </div>
    </div>

<div class="flex flex-col">
    <p class="px-4 text-[9px] font-bold text-slate-600 uppercase tracking-[0.2em] mb-3">Laporan</p>
    <div class="flex flex-col gap-1">
        <a href="laporan_keluar.php" class="sidebar-link group <?php echo basename($_SERVER['PHP_SELF']) == 'laporan_keluar.php' ? 'active' : ''; ?>">
            <span class="icon">📦</span>
            <div class="flex flex-col">
                <span class="text-sm font-medium">Laporan Keluar</span>
                <span class="text-[9px] text-slate-500 group-hover:text-cyan-400 italic">Riwayat Distribusi</span>
            </div>
        </a>

        <a href="laporan_masuk.php" class="sidebar-link group <?php echo basename($_SERVER['PHP_SELF']) == 'laporan_masuk.php' ? 'active' : ''; ?>">
            <span class="icon">📥</span>
            <div class="flex flex-col">
                <span class="text-sm font-medium">Laporan Masuk</span>
                <span class="text-[9px] text-slate-500 group-hover:text-cyan-400 italic">Riwayat Penerimaan</span>
            </div>
        </a>

        <a href="laporan_kartu.php" class="sidebar-link group <?php echo basename($_SERVER['PHP_SELF']) == 'laporan_kartu.php' ? 'active' : ''; ?>">
            <span class="icon">📑</span>
            <span class="text-sm font-medium">Kartu Gudang</span>
        </a>
        <a href="laporan_stok_akhir.php" class="sidebar-link group <?php echo basename($_SERVER['PHP_SELF']) == 'laporan_stok_akhir.php' ? 'active' : ''; ?>">
            <span class="icon">📊</span>
            <span class="text-sm font-medium">Stok Akhir</span>
        </a>
        <a href="laporan_antar_bulan.php" class="sidebar-link group <?php echo basename($_SERVER['PHP_SELF']) == 'laporan_antar_bulan.php' ? 'active' : ''; ?>">
            <span class="icon">📈</span>
            <span class="text-sm font-medium">Analisis Periode</span>
        </a>
    </div>
</div>
  </nav>

  <div class="p-4 border-t border-slate-800 bg-[#0f172a] flex-shrink-0">
    <a href="auth/logout.php"
        onclick="return confirm('Yakin ingin keluar?')"
        class="flex items-center justify-center gap-2 w-full bg-red-600/10 hover:bg-red-600 text-red-500 hover:text-white py-2.5 rounded-lg text-[11px] font-bold transition-all border border-red-500/20 shadow-sm">
      🚪 LOGOUT SISTEM
    </a>
  </div>
</aside>

<style>
    /* Mengatur agar menu tersusun vertikal secara paksa */
    .sidebar-link {
        display: flex !important;
        align-items: center;
        gap: 12px;
        padding: 10px 16px;
        border-radius: 8px;
        transition: all 0.2s ease;
        color: #94a3b8; /* slate-400 */
    }
    
    .sidebar-link:hover {
        background-color: rgba(6, 182, 212, 0.1); /* cyan-600/10 */
        color: #ffffff;
    }

    .sidebar-link.active {
        background-color: #0891b2; /* cyan-600 */
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(8, 145, 178, 0.3);
    }

    .sidebar-link .icon {
        font-size: 1.1rem;
        flex-shrink: 0;
    }

    /* Penanganan Layout Utama */
    @media (min-width: 768px) {
        body { display: flex; flex-direction: row; }
        main { flex: 1; min-width: 0; }
    }

    /* Hilangkan sidebar total saat Print agar laporan bersih */
    @media print {
        aside, #sidebarToggle, #sidebarOverlay, .no-print {
            display: none !important;
        }
        body { display: block; }
    }
</style>

<script>
    // Logika buka-tutup untuk tampilan Mobile
    const toggleBtn = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('mainSidebar');
    const overlay = document.getElementById('sidebarOverlay');

    function toggleSidebar() {
        sidebar.classList.toggle('-translate-x-full');
        overlay.classList.toggle('hidden');
    }

    if(toggleBtn) toggleBtn.addEventListener('click', toggleSidebar);
    if(overlay) overlay.addEventListener('click', toggleSidebar);
</script>