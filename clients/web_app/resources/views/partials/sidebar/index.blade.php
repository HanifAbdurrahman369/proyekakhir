<aside class="w-52 flex-shrink-0 bg-white border-r border-primary-100 py-4 px-3 flex flex-col gap-1 overflow-y-auto">

    @if(session('role_id') == 4)
        @include('partials.sidebar.menu-admin')
        
    @elseif(session('role_id') == 3)
        @include('partials.sidebar.menu-pejabat')
        
    @elseif(session('role_id') == 2)
        @include('partials.sidebar.menu-petugas')
        
    @elseif(session('role_id') == 1)
        @include('partials.sidebar.menu-petani')
        
    @endif

</aside>