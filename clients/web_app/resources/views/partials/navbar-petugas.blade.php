<!-- resources/views/partials/navbar-petugas.blade.php -->

<nav class="bg-blue-600 text-white p-4 flex justify-between items-center">
    
    <div class="font-bold text-lg">
        SIG-PALA | Petugas
    </div>

    <div class="flex items-center gap-6">

        <a href="/dashboard-petugas" class="hover:underline">
            Dashboard
        </a>

        <a href="/verifikasi" class="hover:underline">
            Verifikasi Data
        </a>

        <!-- MENAMPILKAN NAMA USER LOGIN -->
        <div class="bg-white text-blue-700 px-4 py-2 rounded-lg font-semibold shadow">
            {{ session('user.nama_lengkap') }}
        </div>

        <a 
            href="/logout"
            class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded text-white font-medium"
        >
            Logout
        </a>

    </div>

</nav>