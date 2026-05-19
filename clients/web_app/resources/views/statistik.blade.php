<div class="flex w-full py-16 px-4 flex-col items-center gap-12 bg-slate-50 font-['Poppins']">
    <div class="text-center space-y-3">
        <p class="text-emerald-600 font-semibold tracking-[0.3em] text-base uppercase">Pusat Data Analisis</p>
        <h2 class="text-slate-900 text-4xl md:text-5xl font-extrabold tracking-tight">STATISTIK & VISUALISASI</h2>
        <div class="h-1.5 w-24 bg-emerald-500 mx-auto rounded-full mt-4 shadow-sm"></div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 w-full max-w-7xl">
        <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-500 text-center flex flex-col justify-center group">
            <p class="text-slate-400 text-sm font-bold uppercase tracking-widest mb-4 group-hover:text-emerald-500 transition-colors">Total Kecamatan</p>
            <p class="text-5xl font-black text-slate-800" id="stat-kecamatan">...</p>
        </div>
        <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-500 text-center flex flex-col justify-center group">
            <p class="text-slate-400 text-sm font-bold uppercase tracking-widest mb-4 group-hover:text-emerald-500 transition-colors">Total Kelurahan</p>
            <p class="text-5xl font-black text-slate-800" id="stat-kelurahan">...</p>
        </div>
        <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-500 text-center flex flex-col justify-center group">
            <p class="text-emerald-500 text-sm font-bold uppercase tracking-widest mb-4">Total Lahan Sawah</p>
            <p class="text-5xl font-black text-emerald-500" id="stat-total-lahan">...</p>
        </div>
        <div class="bg-emerald-500 p-8 rounded-[2rem] shadow-lg shadow-emerald-200/50 hover:shadow-emerald-400 transition-all duration-500 text-center flex flex-col justify-center group">
            <p class="text-emerald-50 text-sm font-bold uppercase tracking-widest mb-4">Total Luas Lahan</p>
            <p class="text-5xl font-black text-white" id="stat-total-luas">...</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 w-full max-w-7xl mt-4">
        
        <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100 transition-all hover:border-emerald-100">
            <h3 class="text-slate-800 font-bold text-xl mb-8 flex items-center gap-3">
                <span class="w-2.5 h-8 bg-emerald-500 rounded-full"></span> Hasil Panen (Ton)
            </h3>
            <div class="relative w-full h-[300px]"><canvas id="barChart"></canvas></div>
        </div>
        
        <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100 transition-all hover:border-blue-100 flex flex-col">
            <h3 class="text-slate-800 font-bold text-xl mb-8 flex items-center gap-3">
                <span class="w-2.5 h-8 bg-blue-500 rounded-full"></span> Distribusi Tipe Rawa
            </h3>
            <div class="relative w-full h-[300px] flex items-center justify-center"><canvas id="pieChart"></canvas></div>
        </div>

        <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100 transition-all hover:border-indigo-100">
            <h3 class="text-slate-800 font-bold text-xl mb-8 flex items-center gap-3">
                <span class="w-2.5 h-8 bg-indigo-500 rounded-full"></span> Tren Produktivitas Lahan
            </h3>
            <div class="relative w-full h-[300px]"><canvas id="lineChart"></canvas></div>
        </div>

        <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100 transition-all hover:border-amber-100 flex flex-col">
            <h3 class="text-slate-800 font-bold text-xl mb-8 flex items-center gap-3">
                <span class="w-2.5 h-8 bg-amber-500 rounded-full"></span> Sebaran Luas Lahan (Ha)
            </h3>
            <div class="relative w-full h-[300px] flex items-center justify-center"><canvas id="polarChart"></canvas></div>
        </div>
    </div>

    @if(isset($showTable) && $showTable)
    <div class="w-full max-w-7xl mt-10">
        
        <div class="bg-white/80 backdrop-blur-md p-8 rounded-[2.5rem] shadow-sm border border-slate-100 mb-8">
            <div class="flex flex-col xl:flex-row gap-6 items-end justify-between">
                
                <div class="w-full xl:w-1/3 space-y-2">
                    <label class="text-xs font-bold text-slate-400 uppercase tracking-widest ml-1">Pencarian Wilayah</label>
                    <input type="text" id="tableSearch" placeholder="Cari nama kecamatan..." 
                           class="w-full px-6 py-3.5 bg-slate-50 border-none rounded-2xl text-base font-medium font-['Poppins'] focus:ring-2 focus:ring-emerald-500 transition-all shadow-inner">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 w-full xl:w-2/3">
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest ml-1">Tahun LBS</label>
                        <select id="filterTahun" class="custom-select w-full px-4 py-3.5 bg-slate-50 border-none rounded-2xl text-base font-medium font-['Poppins'] text-slate-600 focus:ring-2 focus:ring-emerald-500 cursor-pointer shadow-inner">
                            <option value="all">Semua Tahun</option>
                            <option value="2017">2017</option>
                            <option value="2024">2024</option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest ml-1">Kategori Tipe</label>
                        <select id="filterTipe" class="custom-select w-full px-4 py-3.5 bg-slate-50 border-none rounded-2xl text-base font-medium font-['Poppins'] text-slate-600 focus:ring-2 focus:ring-emerald-500 cursor-pointer shadow-inner">
                            <option value="all">Semua Tipe</option>
                            <option value="a">Tipe A</option>
                            <option value="b">Tipe B</option>
                            <option value="c">Tipe C</option>
                            <option value="d">Tipe D</option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest ml-1">Ambang Panen</label>
                        <select id="filterProduktivitas" class="custom-select w-full px-4 py-3.5 bg-slate-50 border-none rounded-2xl text-base font-medium font-['Poppins'] text-slate-600 focus:ring-2 focus:ring-emerald-500 cursor-pointer shadow-inner">
                            <option value="all">Semua Hasil</option>
                            <option value="4">> 4 Ton/Ha</option>
                            <option value="5">> 5 Ton/Ha</option>
                            <option value="6">> 6 Ton/Ha</option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest ml-1">Urutan Data</label>
                        <select id="sortData" class="custom-select w-full px-4 py-3.5 bg-emerald-50 border-none rounded-2xl text-base font-bold font-['Poppins'] text-emerald-700 focus:ring-2 focus:ring-emerald-500 cursor-pointer shadow-inner">
                            <option value="nama_kecamatan">Nama A-Z</option>
                            <option value="total_luas">Luas Terluas</option>
                            <option value="total_panen">Panen Terbanyak</option>
                            <option value="rataProduktivitas">Paling Produktif</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-[3rem] shadow-sm border border-slate-100 overflow-hidden relative">
            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full text-left whitespace-nowrap min-w-[1250px]">
                    <thead class="bg-slate-50 border-b border-slate-100 text-slate-400 font-bold uppercase tracking-[0.15em] text-[11px]">
                        <tr>
                            <th class="py-6 px-8">Kecamatan</th>
                            <th class="py-6 px-8">Kelurahan / Desa</th>
                            <th class="py-6 px-6 text-center">Tahun LBS</th>
                            <th class="py-6 px-6 text-center">Jumlah Lahan</th>
                            <th class="py-6 px-6 text-center">Total Luas (Ha)</th>
                            <th class="py-6 px-6 text-center bg-slate-100/50">Rincian Per Tipe (Ha)</th> 
                            <th class="py-6 px-8 text-right bg-emerald-50/50 text-emerald-700">Hasil Panen (Ton)</th>
                            <th class="py-6 px-8 text-right bg-blue-50/50 text-blue-700">Produktivitas</th>
                        </tr>
                    </thead>
                    <tbody id="tabel-rekap-body" class="text-slate-600 divide-y divide-slate-50 text-base font-normal">
                        <tr><td colspan="8" class="py-20 text-center italic text-slate-300">Menyusun data statistik...</td></tr>
                    </tbody>
                </table>
            </div>

            <div id="pagination-controls" class="flex justify-between items-center px-8 py-6 bg-slate-50/50 border-t border-slate-100">
                </div>
        </div>
    </div>
    @endif
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
        const gatewayBase = window.GATEWAY_URL || "{{ env('GATEWAY_URL', 'http://127.0.0.1:8000') }}";

        fetch(`${gatewayBase}/api/statistik`)
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') {
                    const data = res.data;
                    document.getElementById('stat-kecamatan').innerText = data.summary.total_kecamatan;
                    document.getElementById('stat-kelurahan').innerText = data.summary.total_kelurahan;
                    document.getElementById('stat-total-lahan').innerText = data.summary.total_lahan_sawah;
                    document.getElementById('stat-total-luas').innerText = data.summary.total_luas_ha + " Ha";

                    renderCharts(data);
                    globalData = data.tabel_rekap;
                    
                    if(document.getElementById('filterTahun')) {
                        applyFilters();
                    }
                }
            });

        const filters = ['tableSearch', 'filterTahun', 'filterTipe', 'filterProduktivitas', 'sortData'];
        filters.forEach(id => {
            const el = document.getElementById(id);
            if(el) el.addEventListener(id === 'tableSearch' ? 'input' : 'change', applyFilters);
        });
    });

    function applyFilters() {
        if(!globalData || globalData.length === 0) return;
        
        const search = document.getElementById('tableSearch').value.toLowerCase();
        const tahun = document.getElementById('filterTahun').value;
        const tipe = document.getElementById('filterTipe').value;
        const minProd = document.getElementById('filterProduktivitas').value;
        const sort = document.getElementById('sortData').value;

        filteredData = globalData.filter(item => {
            const totalLuas = parseFloat(item.total_luas || 0);
            const totalPanen = parseFloat(item.total_panen || 0);
            const prod = totalLuas > 0 ? (totalPanen / totalLuas) : 0;

            const matchSearch = !search || item.nama_kecamatan.toLowerCase().includes(search);
            const matchTahun = tahun === 'all' || item.tahun_lbs === tahun;
            const matchTipe = tipe === 'all' || parseFloat(item['luas_' + tipe]) > 0;
            const matchProd = minProd === 'all' || prod >= parseFloat(minProd);

            return matchSearch && matchTahun && matchTipe && matchProd;
        });

        filteredData.sort((a, b) => {
            if (sort === 'nama_kecamatan') return a.nama_kecamatan.localeCompare(b.nama_kecamatan);
            if (sort === 'total_luas') return b.total_luas - a.total_luas;
            if (sort === 'total_panen') return b.total_panen - a.total_panen;
            if (sort === 'rataProduktivitas') {
                const prodA = a.total_luas > 0 ? a.total_panen / a.total_luas : 0;
                const prodB = b.total_luas > 0 ? b.total_panen / b.total_luas : 0;
                return prodB - prodA;
            }
            return 0;
        });

        currentPage = 1;
        renderTable();
        renderPagination();
    }

    function renderTable() {
        const tbody = document.getElementById('tabel-rekap-body');
        if(!tbody) return;
        tbody.innerHTML = '';

        if(filteredData.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="py-20 text-center text-slate-400 font-medium text-base">Data tidak ditemukan.</td></tr>';
            return;
        }

        const start = (currentPage - 1) * rowsPerPage;
        const paginated = filteredData.slice(start, start + rowsPerPage);

        paginated.forEach((item, index) => {
            const totalLuas = parseFloat(item.total_luas || 0);
            const totalPanen = parseFloat(item.total_panen || 0);
            const prod = totalLuas > 0 ? (totalPanen / totalLuas) : 0;
            const bg = index % 2 === 0 ? 'bg-white' : 'bg-slate-50/30';

            let tipeBadges = `<div class="flex flex-wrap gap-2 justify-center">`;
            const colors = { 'a': 'blue', 'b': 'emerald', 'c': 'amber', 'd': 'purple' };
            ['a','b','c','d'].forEach(t => {
                const val = parseFloat(item['luas_'+t] || 0);
                if(val > 0) {
                    tipeBadges += `<span class="px-2.5 py-1.5 bg-${colors[t]}-50 text-${colors[t]}-700 text-sm font-medium rounded-xl border border-${colors[t]}-100">
                        ${t.toUpperCase()}: ${val.toFixed(2)} Ha
                    </span>`;
                }
            });
            tipeBadges += `</div>`;
            if(tipeBadges === `<div class="flex flex-wrap gap-2 justify-center"></div>`) tipeBadges = `<span class="text-slate-300 italic">-</span>`;

            tbody.insertAdjacentHTML('beforeend', `
                <tr class="${bg} hover:bg-slate-50 transition-colors">
                    <td class="py-5 px-8 text-slate-800 text-base font-normal">${item.nama_kecamatan}</td>
                    <td class="py-5 px-8 text-slate-500 font-normal text-base">${item.nama_kelurahan || '-'}</td>
                    <td class="py-5 px-6 text-center font-normal text-slate-400 text-base">${item.tahun_lbs || '-'}</td>
                    <td class="py-5 px-6 text-center font-normal text-base">${item.jumlah_lahan} Lahan</td>
                    <td class="py-5 px-6 text-center font-normal text-base">${totalLuas.toFixed(2)} Ha</td>
                    <td class="py-5 px-6 text-center bg-slate-50/50">${tipeBadges}</td>
                    <td class="py-5 px-8 text-right font-normal text-emerald-600 bg-emerald-50/20 text-base">${totalPanen.toFixed(2)} Ton</td>
                    <td class="py-5 px-8 text-right bg-blue-50/20">
                        <span class="text-blue-700 text-base font-normal">${prod.toFixed(2)}</span>
                        <small class="text-[10px] text-blue-400 uppercase font-bold">Ton/Ha</small>
                    </td>
                </tr>
            `);
        });
    }

    function renderPagination() {
        const ctrl = document.getElementById('pagination-controls');
        if(!ctrl) return;
        const total = Math.ceil(filteredData.length / rowsPerPage);
        ctrl.innerHTML = `<p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Halaman ${currentPage} dari ${total}</p>`;
        const btnContainer = document.createElement('div');
        btnContainer.className = 'flex gap-2';
        for(let i=1; i<=total; i++) {
            const btn = document.createElement('button');
            btn.innerText = i;
            btn.className = `w-10 h-10 rounded-xl text-sm font-bold transition-all ${i === currentPage ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-200' : 'bg-white text-slate-400 border border-slate-100 hover:bg-slate-50'}`;
            btn.onclick = () => { currentPage = i; renderTable(); renderPagination(); };
            btnContainer.appendChild(btn);
        }
        ctrl.appendChild(btnContainer);
    }

    function renderCharts(data) {
        Chart.defaults.font.family = "'Poppins', sans-serif";
        Chart.defaults.color = '#94a3b8';
        const colors = {
            green: { fill: 'rgba(16, 185, 129, 0.9)', border: '#059669' },
            blue: { fill: 'rgba(59, 130, 246, 0.9)', border: '#2563eb' },
            indigo: { fill: 'rgba(99, 102, 241, 0.2)', border: '#4f46e5' },
            palette: ['#10b981', '#3b82f6', '#f59e0b', '#8b5cf6', '#ec4899', '#14b8a6']
        };

        const config = { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } };
        
        // 1. Render Bar Chart
        if(document.getElementById('barChart') && data.chart_panen_kecamatan) {
            new Chart(document.getElementById('barChart').getContext('2d'), {
                type: 'bar',
                data: {
                    labels: data.chart_panen_kecamatan.map(i => i.nama_kecamatan),
                    datasets: [{ data: data.chart_panen_kecamatan.map(i => i.total_panen), backgroundColor: colors.green.fill, borderRadius: 8 }]
                },
                options: { ...config, scales: { x: { grid: { display: false } } } }
            });
        }

        // 2. Render Doughnut Chart
        if(document.getElementById('pieChart') && data.chart_luas_tipe_rawa) {
            new Chart(document.getElementById('pieChart').getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: data.chart_luas_tipe_rawa.map(i => i.tipe_rawa),
                    datasets: [{ data: data.chart_luas_tipe_rawa.map(i => i.total_luas), backgroundColor: colors.palette, borderWidth: 0 }]
                },
                options: { ...config, plugins: { legend: { display: true, position: 'right', labels: { usePointStyle: true, font: { family: 'Poppins' } } } }, cutout: '75%' }
            });
        }

        // 3. Render Line Chart (DIKEMBALIKAN)
        if(document.getElementById('lineChart') && data.chart_produktivitas_lahan) {
            let grad = document.getElementById('lineChart').getContext('2d').createLinearGradient(0, 0, 0, 300);
            grad.addColorStop(0, 'rgba(99, 102, 241, 0.3)'); grad.addColorStop(1, 'rgba(99, 102, 241, 0)');
            new Chart(document.getElementById('lineChart').getContext('2d'), {
                type: 'line',
                data: {
                    labels: data.chart_produktivitas_lahan.map(i => i.nama_lahan),
                    datasets: [{ label: 'Produktivitas', data: data.chart_produktivitas_lahan.map(i => i.produktivitas_ton_ha), borderColor: colors.indigo.border, backgroundColor: grad, fill: true, tension: 0.4, pointRadius: 4 }]
                },
                options: { ...config, scales: { x: { display: false } } }
            });
        }

        // 4. Render Polar Area Chart (DIKEMBALIKAN)
        if(document.getElementById('polarChart') && data.chart_luas_kecamatan) {
            new Chart(document.getElementById('polarChart').getContext('2d'), {
                type: 'polarArea',
                data: {
                    labels: data.chart_luas_kecamatan.map(i => i.nama_kecamatan),
                    datasets: [{ data: data.chart_luas_kecamatan.map(i => i.total_luas), backgroundColor: ['rgba(16, 185, 129, 0.7)', 'rgba(59, 130, 246, 0.7)', 'rgba(245, 158, 11, 0.7)', 'rgba(139, 92, 246, 0.7)'] }]
                },
                options: { ...config, plugins: { legend: { position: 'right' } }, scales: { r: { ticks: { display: false } } } }
            });
        }
    }
</script>