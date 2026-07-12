@extends('layouts.app')

@section('title', 'Laporan Produksi Daerah')

@section('content')

<div class="flex w-full py-10 px-4 flex-col items-center gap-8 bg-white font-['Poppins']">
    {{-- Header Section --}}
    <div class="text-center space-y-2">
        <p class="text-slate-500 font-semibold tracking-[0.3em] text-sm uppercase">Analisis Data Pertanian</p>
        <h2 class="text-slate-800 text-3xl md:text-4xl font-extrabold tracking-tight">PRODUKSI DAERAH</h2>
        <div class="h-1 w-20 bg-slate-300 mx-auto rounded-full mt-3 shadow-sm"></div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 w-full max-w-7xl">
        <div class="bg-slate-50 p-6 rounded-[1.5rem] shadow-sm border border-slate-200 hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 text-center flex flex-col justify-center group">
            <p class="text-slate-500 text-xs font-bold uppercase tracking-widest mb-3 group-hover:text-slate-700 transition-colors">Total Daerah</p>
            <p class="text-4xl font-black text-slate-800" id="stat-total-daerah">...</p>
        </div>
        <div class="bg-slate-50 p-6 rounded-[1.5rem] shadow-sm border border-slate-200 hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 text-center flex flex-col justify-center group">
            <p class="text-slate-500 text-xs font-bold uppercase tracking-widest mb-3 group-hover:text-slate-700 transition-colors">Total Komoditas</p>
            <p class="text-4xl font-black text-slate-800" id="stat-total-komoditas">...</p>
        </div>
        <div class="bg-slate-50 p-6 rounded-[1.5rem] shadow-sm border border-slate-200 hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 text-center flex flex-col justify-center group">
            <p class="text-slate-600 text-xs font-bold uppercase tracking-widest mb-3">Total Luas Panen (Ha)</p>
            <p class="text-4xl font-black text-slate-700" id="stat-total-luas-panen">...</p>
        </div>
        <div class="bg-slate-700 p-6 rounded-[1.5rem] shadow-lg shadow-slate-400/30 hover:shadow-slate-500/40 transition-all duration-300 text-center flex flex-col justify-center group">
            <p class="text-slate-100 text-xs font-bold uppercase tracking-widest mb-3">Total Produksi</p>
            <p class="text-4xl font-black text-white" id="stat-total-produksi">...</p>
        </div>
    </div>

    {{-- Charts Section --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 w-full max-w-7xl">
        
        <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-200 transition-all hover:border-slate-300">
            <h3 class="text-slate-800 font-bold text-lg mb-6 flex items-center gap-2">
                <span class="w-2 h-6 bg-slate-600 rounded-full"></span> Produksi per Komoditas (Ton)
            </h3>
            <div class="relative w-full h-[280px]"><canvas id="barChart"></canvas></div>
        </div>
        
        <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-200 transition-all hover:border-slate-300 flex flex-col">
            <h3 class="text-slate-800 font-bold text-lg mb-6 flex items-center gap-2">
                <span class="w-2 h-6 bg-slate-500 rounded-full"></span> Distribusi Produksi per Daerah
            </h3>
            <div class="relative w-full h-[280px] flex items-center justify-center"><canvas id="pieChart"></canvas></div>
        </div>

        <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-200 transition-all hover:border-slate-300">
            <h3 class="text-slate-800 font-bold text-lg mb-6 flex items-center gap-2">
                <span class="w-2 h-6 bg-slate-700 rounded-full"></span> Produktivitas per Daerah (Ton/Ha)
            </h3>
            <div class="relative w-full h-[280px]"><canvas id="lineChart"></canvas></div>
        </div>

        <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-200 transition-all hover:border-slate-300 flex flex-col">
            <h3 class="text-slate-800 font-bold text-lg mb-6 flex items-center gap-2">
                <span class="w-2 h-6 bg-slate-400 rounded-full"></span> Luas Panen per Daerah (Ha)
            </h3>
            <div class="relative w-full h-[280px] flex items-center justify-center"><canvas id="polarChart"></canvas></div>
        </div>
    </div>

    {{-- Filter & Table Section --}}
    <div class="w-full max-w-7xl mt-8">
        
        <div class="bg-slate-50/80 backdrop-blur-md p-6 rounded-[2rem] shadow-sm border border-slate-200 mb-6">
            <div class="flex flex-col xl:flex-row gap-6 items-end justify-between">
                
                <div class="w-full xl:w-1/3 space-y-1.5">
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Pencarian Data</label>
                    <input type="text" id="tableSearch" placeholder="Cari daerah atau komoditas..." 
                           class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-medium font-['Poppins'] focus:ring-2 focus:ring-slate-400 transition-all shadow-sm">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 w-full xl:w-2/3">
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Filter Daerah</label>
                        <select id="filterDaerah" class="custom-select w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-medium font-['Poppins'] text-slate-700 focus:ring-2 focus:ring-slate-400 cursor-pointer shadow-sm">
                            <option value="">Semua Daerah</option>
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Urutan Data</label>
                        <select id="sortData" class="custom-select w-full px-4 py-2.5 bg-slate-100 border border-slate-200 rounded-xl text-sm font-bold font-['Poppins'] text-slate-700 focus:ring-2 focus:ring-slate-400 cursor-pointer shadow-sm">
                            <option value="daerah">Daerah A-Z</option>
                            <option value="produksi">Produksi Terbanyak</option>
                            <option value="luas">Luas Panen Terluas</option>
                            <option value="produktivitas">Paling Produktif</option>
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">&nbsp;</label>
                        <button type="button" onclick="applyFilters()" class="w-full px-4 py-2.5 bg-slate-700 hover:bg-slate-800 text-white text-sm font-bold rounded-xl transition-all shadow-md hover:shadow-lg">
                            Filter
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200 overflow-hidden relative">
            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full text-left whitespace-nowrap min-w-[900px]">
                    <thead class="bg-slate-100 border-b border-slate-200 text-slate-600 font-bold uppercase tracking-[0.1em] text-[10px]">
                        <tr>
                            <th class="py-4 px-6">Daerah</th>
                            <th class="py-4 px-6">Komoditas</th>
                            <th class="py-4 px-4 text-center">Luas Tanam (Ha)</th>
                            <th class="py-4 px-4 text-center">Luas Panen (Ha)</th>
                            <th class="py-4 px-6 text-right bg-slate-200/50 text-slate-700">Produksi (Ton)</th>
                            <th class="py-4 px-6 text-right bg-slate-200/50 text-slate-700">Produktivitas</th>
                        </tr>
                    </thead>
                    <tbody id="tabel-produksi-body" class="text-slate-600 divide-y divide-slate-100 text-sm font-normal">
                        <tr><td colspan="6" class="py-12 text-center italic text-slate-400">Menyusun data produksi...</td></tr>
                    </tbody>
                </table>
            </div>

            <div id="pagination-controls" class="flex justify-between items-center px-6 py-4 bg-slate-50 border-t border-slate-200">
            </div>
        </div>
    </div>

</div>

<style>
    .custom-select {
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2394a3b8'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 1.25rem center;
        background-size: 1rem;
    }
    .custom-scrollbar::-webkit-scrollbar { height: 8px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; border: 2px solid #fff; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    let globalData = [];
    let filteredData = [];
    let currentPage = 1;
    const rowsPerPage = 7;

    document.addEventListener("DOMContentLoaded", function () {
        fetch('/api/produksi-daerah')
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') {
                    const data = res.data;
                    document.getElementById('stat-total-daerah').innerText = data.summary.total_daerah;
                    document.getElementById('stat-total-komoditas').innerText = data.summary.total_komoditas;
                    document.getElementById('stat-total-luas-panen').innerText = data.summary.total_luas_panen.toLocaleString('id-ID');
                    document.getElementById('stat-total-produksi').innerText = data.summary.total_produksi.toLocaleString('id-ID') + ' Ton';

                    renderCharts(data);
                    globalData = data.tabel_rekap;
                    
                    if(document.getElementById('filterDaerah')) {
                        // Populate daerah filter
                        const daerahList = [...new Set(globalData.map(item => item.daerah))].sort();
                        const filterDaerah = document.getElementById('filterDaerah');
                        daerahList.forEach(d => {
                            const option = document.createElement('option');
                            option.value = d;
                            option.text = d;
                            filterDaerah.appendChild(option);
                        });
                        applyFilters();
                    }
                }
            })
            .catch(err => console.error('Error fetching produksi-daerah data:', err));

        const filters = ['tableSearch', 'filterDaerah', 'sortData'];
        filters.forEach(id => {
            const el = document.getElementById(id);
            if(el) el.addEventListener(id === 'tableSearch' ? 'input' : 'change', applyFilters);
        });
    });

    function applyFilters() {
        if(!globalData || globalData.length === 0) return;
        
        const search = document.getElementById('tableSearch').value.toLowerCase();
        const daerah = document.getElementById('filterDaerah').value;
        const sort = document.getElementById('sortData').value;

        filteredData = globalData.filter(item => {
            const matchSearch = !search || 
                item.daerah.toLowerCase().includes(search) || 
                item.komoditas.toLowerCase().includes(search);
            const matchDaerah = !daerah || item.daerah === daerah;
            return matchSearch && matchDaerah;
        });

        filteredData.sort((a, b) => {
            if (sort === 'daerah') return a.daerah.localeCompare(b.daerah);
            if (sort === 'produksi') return b.produksi - a.produksi;
            if (sort === 'luas') return b.luas_panen - a.luas_panen;
            if (sort === 'produktivitas') return b.produktivitas - a.produktivitas;
            return 0;
        });

        currentPage = 1;
        renderTable();
        renderPagination();
    }

    function renderTable() {
        const tbody = document.getElementById('tabel-produksi-body');
        if(!tbody) return;
        tbody.innerHTML = '';

        if(filteredData.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="py-20 text-center text-slate-400 font-medium text-base">Data tidak ditemukan.</td></tr>';
            return;
        }

        const start = (currentPage - 1) * rowsPerPage;
        const paginated = filteredData.slice(start, start + rowsPerPage);

        paginated.forEach((item, index) => {
            const bg = index % 2 === 0 ? 'bg-white' : 'bg-slate-50';

            tbody.insertAdjacentHTML('beforeend', `
                <tr class="${bg} hover:bg-slate-100 transition-colors">
                    <td class="py-4 px-6 text-slate-800 text-sm font-medium">${item.daerah}</td>
                    <td class="py-4 px-6 text-slate-600 font-normal text-sm">${item.komoditas}</td>
                    <td class="py-4 px-4 text-center font-normal text-sm">${item.luas_tanam.toLocaleString('id-ID')}</td>
                    <td class="py-4 px-4 text-center font-normal text-sm">${item.luas_panen.toLocaleString('id-ID')}</td>
                    <td class="py-4 px-6 text-right font-medium text-slate-700 bg-slate-100/30 text-sm">${item.produksi.toLocaleString('id-ID')}</td>
                    <td class="py-4 px-6 text-right bg-slate-100/30">
                        <span class="text-slate-700 text-sm font-medium">${item.produktivitas.toFixed(2)}</span>
                        <small class="text-[9px] text-slate-500 uppercase font-bold">Ton/Ha</small>
                    </td>
                </tr>
            `);
        });
    }

    function renderPagination() {
        const ctrl = document.getElementById('pagination-controls');
        if(!ctrl) return;
        const total = Math.ceil(filteredData.length / rowsPerPage);
        ctrl.innerHTML = `<p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Halaman ${currentPage} dari ${total} (${filteredData.length} data)</p>`;
        const btnContainer = document.createElement('div');
        btnContainer.className = 'flex gap-1';
        for(let i=1; i<=total; i++) {
            const btn = document.createElement('button');
            btn.innerText = i;
            btn.className = `w-8 h-8 rounded-lg text-xs font-bold transition-all ${i === currentPage ? 'bg-slate-700 text-white shadow-md shadow-slate-400' : 'bg-white text-slate-500 border border-slate-200 hover:bg-slate-100'}`;
            btn.onclick = () => { currentPage = i; renderTable(); renderPagination(); };
            btnContainer.appendChild(btn);
        }
        ctrl.appendChild(btnContainer);
    }

    function renderCharts(data) {
        Chart.defaults.font.family = "'Poppins', sans-serif";
        Chart.defaults.color = '#94a3b8';
        const colors = {
            green: { fill: 'rgba(4, 120, 87, 0.9)', border: '#047857' },
            blue: { fill: 'rgba(59, 130, 246, 0.9)', border: '#2563eb' },
            indigo: { fill: 'rgba(99, 102, 241, 0.2)', border: '#4f46e5' },
            palette: ['#047857', '#3b82f6', '#f59e0b', '#8b5cf6', '#ec4899', '#14b8a6']
        };

        const config = { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } };
        
        // 1. Bar Chart - Produksi per Komoditas
        if(document.getElementById('barChart') && data.chart_produksi_komoditas) {
            new Chart(document.getElementById('barChart').getContext('2d'), {
                type: 'bar',
                data: {
                    labels: data.chart_produksi_komoditas.map(i => i.nama_komoditas),
                    datasets: [{ data: data.chart_produksi_komoditas.map(i => i.total_produksi), backgroundColor: colors.green.fill, borderRadius: 8 }]
                },
                options: { ...config, scales: { x: { grid: { display: false } } } }
            });
        }

        // 2. Pie Chart - Distribusi per Daerah
        if(document.getElementById('pieChart') && data.chart_produksi_daerah) {
            new Chart(document.getElementById('pieChart').getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: data.chart_produksi_daerah.map(i => i.nama_daerah),
                    datasets: [{ data: data.chart_produksi_daerah.map(i => i.total_produksi), backgroundColor: colors.palette, borderWidth: 0 }]
                },
                options: { ...config, plugins: { legend: { display: true, position: 'right', labels: { usePointStyle: true, font: { family: 'Poppins' } } } }, cutout: '75%' }
            });
        }

        // 3. Line Chart - Produktivitas per Daerah
        if(document.getElementById('lineChart') && data.chart_produktivitas_daerah) {
            let grad = document.getElementById('lineChart').getContext('2d').createLinearGradient(0, 0, 0, 300);
            grad.addColorStop(0, 'rgba(99, 102, 241, 0.3)'); 
            grad.addColorStop(1, 'rgba(99, 102, 241, 0)');
            new Chart(document.getElementById('lineChart').getContext('2d'), {
                type: 'line',
                data: {
                    labels: data.chart_produktivitas_daerah.map(i => i.nama_daerah),
                    datasets: [{ label: 'Produktivitas', data: data.chart_produktivitas_daerah.map(i => i.produktivitas), borderColor: colors.indigo.border, backgroundColor: grad, fill: true, tension: 0.4, pointRadius: 4 }]
                },
                options: { ...config, scales: { x: { display: false } } }
            });
        }

        // 4. Polar Chart - Luas Panen per Daerah
        if(document.getElementById('polarChart') && data.chart_luas_tanam_daerah) {
            new Chart(document.getElementById('polarChart').getContext('2d'), {
                type: 'polarArea',
                data: {
                    labels: data.chart_luas_tanam_daerah.map(i => i.nama_daerah),
                    datasets: [{ data: data.chart_luas_tanam_daerah.map(i => i.total_luas), backgroundColor: ['rgba(4, 120, 87, 0.7)', 'rgba(59, 130, 246, 0.7)', 'rgba(245, 158, 11, 0.7)', 'rgba(139, 92, 246, 0.7)'] }]
                },
                options: { ...config, plugins: { legend: { position: 'right' } }, scales: { r: { ticks: { display: false } } } }
            });
        }
    }
</script>

</div>

@endsection
