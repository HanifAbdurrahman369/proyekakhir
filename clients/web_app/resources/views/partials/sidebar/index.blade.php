<aside id="appSidebar"
       class="sipetani-sidebar fixed lg:sticky top-0 lg:top-[72px] left-0 z-50 lg:z-30 w-[250px] lg:w-60 h-screen lg:h-[calc(100vh-72px)] flex-shrink-0 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-out overflow-y-auto border-r"
       style="background:rgba(255,255,255,.88); border-color:#e7efd8; box-shadow:16px 0 50px rgba(32,60,16,.06); backdrop-filter:blur(16px);">
    <div class="p-3.5 lg:p-4">
        <div class="lg:hidden flex items-center justify-between mb-5">
            <div>
                <p class="text-xs font-bold" style="color:#047857;">SiPetani</p>
                <p class="text-[11px] text-slate-400">Menu navigasi</p>
            </div>
            <button type="button" onclick="closeSidebar()" class="w-9 h-9 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M18.3 5.71 12 12l6.3 6.29-1.41 1.41L10.59 13.41 4.29 19.71 2.88 18.3 9.17 12 2.88 5.71 4.29 4.29l6.3 6.3 6.3-6.3 1.41 1.42z"/></svg>
            </button>
        </div>

        <div class="rounded-[20px] p-3.5 mb-4"
             style="background:linear-gradient(135deg,#f7fced,#edf8dc); border:1px solid #e7efd8;">
            <p class="text-[11px] font-semibold text-slate-500">Role Dashboard</p>
            <p class="text-sm font-extrabold mt-1" style="color:#203c10;">
                @switch(session('role_id'))
                    @case(4) Administrator @break
                    @case(3) Pejabat @break
                    @case(2) Petugas Lapangan @break
                    @case(1) Kelompok Tani @break
                    @case(5) Brigade Pangan @break
                    @default Pengguna
                @endswitch
            </p>
        </div>

        <div class="space-y-1.5">
            @if(session('role_id') == 4)
                @include('partials.sidebar.admin.menu-admin')
            @elseif(session('role_id') == 3)
                @include('partials.sidebar.pejabat.menu-pejabat')
            @elseif(session('role_id') == 2)
                @include('partials.sidebar.petugas.menu-petugas')
            @elseif(in_array((int) session('role_id'), [1, 5], true))
                @include('partials.sidebar.petani.menu-petani')
            @endif
        </div>
    </div>
</aside>
