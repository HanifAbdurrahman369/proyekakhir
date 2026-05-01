<!-- resources/views/partials/navbar-petani.blade.php -->

<nav class="bg-green-600 text-white p-4 flex justify-between items-center">
    
    <div class="font-bold text-lg">
        SIG-PALA | Petani
    </div>

    <div class="flex items-center gap-6">

        <a href="/dashboard-petani" class="hover:underline">
            Dashboard
        </a>

        <a href="/profile" class="hover:underline">
            Profile
        </a>

        <!-- MENAMPILKAN NAMA USER LOGIN -->
        <div class="bg-white text-green-700 px-4 py-2 rounded-lg font-semibold shadow">
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