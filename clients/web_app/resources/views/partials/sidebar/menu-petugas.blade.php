<p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider px-2 py-1 mt-1">Menu Petugas</p>

<!-- BERANDA (Opsional untuk halaman awal setelah login) -->
<a href="/dashboard-petugas" class="flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-xs font-medium transition {{ request()->is('dashboard-petugas') ? 'bg-primary-100 text-primary-800' : 'text-gray-600 hover:bg-primary-50 hover:text-primary-700' }}">
    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
    Beranda Petugas
</a>

<!-- USE CASE 1 -->
<a href="/manajemen-data-spasial" class="flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-xs font-medium transition {{ request()->is('manajemen-data-spasial') ? 'bg-primary-100 text-primary-800' : 'text-gray-600 hover:bg-primary-50 hover:text-primary-700' }}">
    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
    Manajemen Data Spasial
</a>

<!-- USE CASE 2 -->
<a href="/input-parameter-lingkungan" class="flex items-center gap-2.5 px-2.5 py-2 mt-1 rounded-lg text-xs font-medium transition {{ request()->is('input-parameter-lingkungan') ? 'bg-primary-100 text-primary-800' : 'text-gray-600 hover:bg-primary-50 hover:text-primary-700' }}">
    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 3c-4.97 0-9 4.03-9 9s4.03 9 9 9 9-4.03 9-9-4.03-9-9-9zm0 16c-3.86 0-7-3.14-7-7s3.14-7 7-7 7 3.14 7 7-3.14 7-7 7zm1-11h-2v3H8v2h3v3h2v-3h3v-2h-3V8z"/></svg>
    Input Parameter Lingkungan
</a>

<!-- USE CASE 3 -->
<a href="/verifikasi-data-petani" class="flex items-center gap-2.5 px-2.5 py-2 mt-1 rounded-lg text-xs font-medium transition {{ request()->is('verifikasi-data-petani') ? 'bg-primary-100 text-primary-800' : 'text-gray-600 hover:bg-primary-50 hover:text-primary-700' }}">
    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg>
    Verifikasi Data Petani
</a>