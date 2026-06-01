<p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider px-2 py-1 mt-1">Operasional Lapangan</p>

<a href="/dashboard-petugas" class="flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-xs font-medium transition {{ request()->is('dashboard-petugas') ? 'bg-primary-100 text-primary-800' : 'text-gray-600 hover:bg-primary-50 hover:text-primary-700' }}">
    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
    Beranda Petugas
</a>

<a href="/peta-lahan" class="flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-xs font-medium transition {{ request()->is('peta-lahan') ? 'bg-primary-100 text-primary-800' : 'text-gray-600 hover:bg-primary-50 hover:text-primary-700' }}">
    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
    Mapping Wilayah
</a>

<p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider px-2 py-1 mt-3">Validasi Data</p>

<a href="/verifikasi-panen" class="flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-xs font-medium transition {{ request()->is('verifikasi-panen') ? 'bg-primary-100 text-primary-800' : 'text-gray-600 hover:bg-primary-50 hover:text-primary-700' }}">
    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg>
    Verifikasi Siklus
</a>