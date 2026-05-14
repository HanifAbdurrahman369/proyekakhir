<p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider px-2 py-1 mt-1">Laporan Eksekutif</p>

<a href="/dashboard-pejabat" class="flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-xs font-medium transition {{ request()->is('dashboard-pejabat') ? 'bg-primary-100 text-primary-800' : 'text-gray-600 hover:bg-primary-50 hover:text-primary-700' }}">
    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
    Statistik Utama
</a>

<a href="/peta-lahan" class="flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-xs font-medium transition text-gray-600 hover:bg-primary-50 hover:text-primary-700">
    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
    Sebaran Lahan
</a>

<p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider px-2 py-1 mt-3">Analisis Data</p>

<a href="/laporan-produksi" class="flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-xs font-medium transition text-gray-600 hover:bg-primary-50 hover:text-primary-700">
    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M3.5 18.49l6-6.01 4 4L22 6.92l-1.41-1.41-7.09 7.97-4-4L2 16.99z"/></svg>
    Produksi Daerah
</a>

<a href="/ekspor" class="flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-xs font-medium transition text-gray-600 hover:bg-primary-50 hover:text-primary-700">
    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
    Ekspor PDF / Excel
</a>