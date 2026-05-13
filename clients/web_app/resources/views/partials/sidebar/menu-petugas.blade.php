<p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider px-2 py-1 mt-1">Operasional Lapangan</p>

<a href="/dashboard-petugas" class="flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-xs font-medium transition {{ request()->is('dashboard-petugas') ? 'bg-primary-100 text-primary-800' : 'text-gray-600 hover:bg-primary-50 hover:text-primary-700' }}">
    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
    Beranda Petugas
</a>

<a href="/peta-lahan" class="flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-xs font-medium transition text-gray-600 hover:bg-primary-50 hover:text-primary-700">
    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
    Mapping Wilayah
</a>

<p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider px-2 py-1 mt-3">Validasi Data</p>

<a href="/verifikasi-panen" class="flex items-center justify-between px-2.5 py-2 rounded-lg text-xs font-medium transition text-gray-600 hover:bg-primary-50 hover:text-primary-700">
    <span class="flex items-center gap-2.5">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
        Verifikasi Panen
    </span>
    <span class="bg-amber-400 text-amber-900 text-[9px] font-bold px-1.5 py-0.5 rounded-full">8</span>
</a>

<a href="/petani" class="flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-xs font-medium transition text-gray-600 hover:bg-primary-50 hover:text-primary-700">
    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-.23c0-.62.28-1.2.76-1.58C7.47 15.82 9.64 15 12 15s4.53.82 6.24 2.19c.48.38.76.97.76 1.58V19z"/></svg>
    Data Petani Binaan
</a>