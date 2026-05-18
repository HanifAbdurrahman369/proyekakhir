<p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider px-2 py-1 mt-1">Sistem Manajemen Utama</p>

<a href="/admin/users" class="flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-xs font-medium transition {{ request()->is('admin/users*') ? 'bg-primary-100 text-primary-800' : 'text-gray-600 hover:bg-primary-50 hover:text-primary-700' }}">
    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
    Manajemen Pengguna
</a>

<a href="/admin/master" class="flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-xs font-medium transition {{ request()->is('admin/master*') ? 'bg-primary-100 text-primary-800' : 'text-gray-600 hover:bg-primary-50 hover:text-primary-700' }}">
    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M4 14h6v-4H4v4zm0 5h6v-4H4v4zM4 9h6V5H4v4zm7 5h6v-4h-6v4zm0 5h6v-4h-6v4zm0-14v4h6V5h-6z"/></svg>
    Manajemen Data Master
</a>