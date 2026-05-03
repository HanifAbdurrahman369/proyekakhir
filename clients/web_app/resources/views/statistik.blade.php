<!-- ========================================================================= -->
<!-- FILE: resources/views/statistik.blade.php -->
<!-- ========================================================================= -->

<div class="flex w-full py-16 px-4 flex-col items-center gap-12 bg-slate-50">
    <div class="text-center space-y-2">
        <p class="text-primary-600 font-bold tracking-[0.2em] text-xs uppercase">Informasi Publik</p>
        <p class="text-slate-800 text-4xl font-extrabold tracking-tight">DATA STATISTIK & VISUALISASI</p>
        <div class="h-1 w-20 bg-primary-500 mx-auto rounded-full mt-4"></div>
    </div>

    <!-- 1. KOTAK RINGKASAN (KPI CARDS) -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-6 w-full max-w-7xl">
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:shadow-md hover:-translate-y-1 transition-all duration-300 text-center flex flex-col justify-center">
            <p class="text-slate-400 text-[10px] sm:text-xs font-bold uppercase tracking-wider mb-2">Total Kecamatan</p>
            <p class="text-3xl sm:text-4xl font-black text-slate-800" id="stat-kecamatan">...</p>
        </div>
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:shadow-md hover:-translate-y-1 transition-all duration-300 text-center flex flex-col justify-center">
            <p class="text-slate-400 text-[10px] sm:text-xs font-bold uppercase tracking-wider mb-2">Total Kelurahan</p>
            <p class="text-3xl sm:text-4xl font-black text-slate-800" id="stat-kelurahan">...</p>
        </div>
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:shadow-md hover:-translate-y-1 transition-all duration-300 text-center flex flex-col justify-center">
            <p class="text-primary-500 text-[10px] sm:text-xs font-bold uppercase tracking-wider mb-2">Total Lahan Sawah</p>
            <p class="text-3xl sm:text-4xl font-black text-primary-600" id="stat-total-lahan">...</p>
        </div>
        <div class="bg-emerald-50 p-6 rounded-2xl shadow-sm border border-emerald-100 hover:shadow-md hover:-translate-y-1 transition-all duration-300 text-center flex flex-col justify-center">
            <p class="text-emerald-600 text-[10px] sm:text-xs font-bold uppercase tracking-wider mb-2">Total Luas Lahan</p>
            <p class="text-3xl sm:text-4xl font-black text-emerald-700" id="stat-total-luas">...</p>
        </div>
    </div>

    <!-- 2. AREA GRAFIK (MODERN DESIGN) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 w-full max-w-7xl">
        
        <!-- Grafik Batang (Bar Chart) -->
        <div class="bg-white p-6 sm:p-8 rounded-3xl shadow-sm border border-slate-100">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h3 class="text-slate-800 font-bold text-lg">Hasil Panen per Kecamatan</h3>
                    <p class="text-slate-400 text-xs mt-1">Dalam satuan Ton</p>
                </div>
                <div class="p-2 bg-green-50 rounded-lg text-green-500">📈</div>
            </div>
            <div class="relative w-full h-[280px]">
                <canvas id="barChart"></canvas>
            </div>
        </div>
        
        <!-- Grafik Bulat (Doughnut Chart) -->
        <div class="bg-white p-6 sm:p-8 rounded-3xl shadow-sm border border-slate-100 flex flex-col">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h3 class="text-slate-800 font-bold text-lg">Luas Lahan per Tipe Rawa</h3>
                    <p class="text-slate-400 text-xs mt-1">Distribusi proporsi lahan</p>
                </div>
                <div class="p-2 bg-blue-50 rounded-lg text-blue-500">🍩</div>
            </div>
            <div class="relative w-full h-[280px] flex items-center justify-center">
                <canvas id="pieChart"></canvas>
            </div>
        </div>

        <!-- Grafik Garis (Line Chart) -->
        <div class="bg-white p-6 sm:p-8 rounded-3xl shadow-sm border border-slate-100">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h3 class="text-slate-800 font-bold text-lg">Tren Produktivitas Lahan</h3>
                    <p class="text-slate-400 text-xs mt-1">Rasio Ton/Hektar per Blok</p>
                </div>
                <div class="p-2 bg-indigo-50 rounded-lg text-indigo-500">📉</div>
            </div>
            <div class="relative w-full h-[280px]">
                <canvas id="lineChart"></canvas>
            </div>
        </div>

        <!-- Grafik Area (Polar Area Chart) -->
        <div class="bg-white p-6 sm:p-8 rounded-3xl shadow-sm border border-slate-100 flex flex-col">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h3 class="text-slate-800 font-bold text-lg">Sebaran Luas Lahan</h3>
                    <p class="text-slate-400 text-xs mt-1">Berdasarkan wilayah Kecamatan (Ha)</p>
                </div>
                <div class="p-2 bg-amber-50 rounded-lg text-amber-500">🧭</div>
            </div>
            <div class="relative w-full h-[280px] flex items-center justify-center">
                <canvas id="polarChart"></canvas>
            </div>
        </div>

    </div>

   <!-- 3. TABEL DATA DINAMIS (HANYA MUNCUL JIKA DIIZINKAN) -->
    @if(isset($showTable) && $showTable)
    <div class="w-full max-w-7xl mt-8">
        <div class="flex items-center justify-between mb-4 px-2">
            <h3 class="text-slate-800 font-bold text-xl">Rekapitulasi Data Wilayah</h3>
        </div>
        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-slate-50 border-b border-slate-100 text-slate-500 font-semibold uppercase tracking-wider text-[11px]">
                        <tr>
                            <th class="py-5 px-6">Kecamatan</th>
                            <th class="py-5 px-6">Kelurahan / Desa</th>
                            <th class="py-5 px-6 text-center">Jumlah Lahan Sawah</th>
                            <th class="py-5 px-6 text-center">Total Luas Lahan (Ha)</th>
                            <th class="py-5 px-6 text-right">Total Hasil Panen (Ton)</th>
                            <th class="py-5 px-6 text-right">Rata-rata Produktivitas</th>
                        </tr>
                    </thead>
                    <tbody id="tabel-rekap-body" class="text-slate-700 divide-y divide-slate-50">
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400 italic">Memuat data tabel...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Tambahkan Pustaka Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Script Logika Fetch Statistik dan Render Grafik -->
<script>
    // Konfigurasi Global Font Chart.js
    Chart.defaults.font.family = "'Poppins', sans-serif";
    Chart.defaults.color = '#94a3b8';
    Chart.defaults.scale.grid.color = '#f1f5f9';

    document.addEventListener("DOMContentLoaded", function () {
        const apiUrl = "http://127.0.0.1:8000/api/statistik"; 
        
        fetch(apiUrl)
            .then(res => {
                if (!res.ok) throw new Error(`Server merespons dengan status: ${res.status}`);
                return res.json();
            })
            .then(res => {
                if (res.status === 'success') {
                    const data = res.data;
                    const summary = data.summary;
                    
                    // --- 1. UPDATE KPI ---
                    if(document.getElementById('stat-kecamatan')) document.getElementById('stat-kecamatan').innerText = summary.total_kecamatan || 0;
                    if(document.getElementById('stat-kelurahan')) document.getElementById('stat-kelurahan').innerText = summary.total_kelurahan || 0;
                    if(document.getElementById('stat-total-lahan')) document.getElementById('stat-total-lahan').innerText = summary.total_lahan_sawah || 0;
                    if(document.getElementById('stat-total-luas')) document.getElementById('stat-total-luas').innerText = (summary.total_luas_ha || 0) + " Ha";

                    const colors = {
                        green: { fill: 'rgba(34, 197, 94, 0.8)', border: '#16a34a' },
                        blue: { fill: 'rgba(56, 189, 248, 0.8)', border: '#0284c7' },
                        indigo: { fill: 'rgba(99, 102, 241, 0.2)', border: '#4f46e5' },
                        palette: ['#10b981', '#3b82f6', '#f59e0b', '#8b5cf6', '#ec4899', '#14b8a6', '#f43f5e']
                    };

                    // --- 2. RENDER BAR CHART ---
                    const ctxBar = document.getElementById('barChart');
                    if(ctxBar && data.chart_panen_kecamatan) {
                        new Chart(ctxBar.getContext('2d'), {
                            type: 'bar',
                            data: {
                                labels: data.chart_panen_kecamatan.map(i => i.nama_kecamatan),
                                datasets: [{
                                    label: 'Hasil Panen (Ton)',
                                    data: data.chart_panen_kecamatan.map(i => i.total_panen),
                                    backgroundColor: colors.green.fill, borderColor: colors.green.border,
                                    borderWidth: 0, borderRadius: 8, barPercentage: 0.6
                                }]
                            },
                            options: { 
                                responsive: true, maintainAspectRatio: false,
                                plugins: { legend: { display: false }, tooltip: { padding: 12, cornerRadius: 8 } },
                                scales: { x: { grid: { display: false } }, y: { border: { display: false } } }
                            }
                        });
                    }

                    // --- 3. RENDER DOUGHNUT CHART ---
                    const ctxPie = document.getElementById('pieChart');
                    if(ctxPie && data.chart_luas_tipe_rawa) {
                        new Chart(ctxPie.getContext('2d'), {
                            type: 'doughnut',
                            data: {
                                labels: data.chart_luas_tipe_rawa.map(i => i.tipe_rawa),
                                datasets: [{ 
                                    data: data.chart_luas_tipe_rawa.map(i => i.total_luas), 
                                    backgroundColor: colors.palette, borderWidth: 0, hoverOffset: 8 
                                }]
                            },
                            options: { 
                                responsive: true, maintainAspectRatio: false,
                                plugins: { legend: { position: 'right', labels: { usePointStyle: true, boxWidth: 8 } } }, 
                                cutout: '75%', layout: { padding: 10 }
                            }
                        });
                    }

                    // --- 4. RENDER LINE CHART ---
                    const ctxLine = document.getElementById('lineChart');
                    if(ctxLine && data.chart_produktivitas_lahan) {
                        let gradient = ctxLine.getContext('2d').createLinearGradient(0, 0, 0, 300);
                        gradient.addColorStop(0, 'rgba(99, 102, 241, 0.4)');
                        gradient.addColorStop(1, 'rgba(99, 102, 241, 0.0)');

                        new Chart(ctxLine.getContext('2d'), {
                            type: 'line',
                            data: {
                                labels: data.chart_produktivitas_lahan.map(i => i.nama_lahan),
                                datasets: [{
                                    label: 'Produktivitas (Ton/Ha)',
                                    data: data.chart_produktivitas_lahan.map(i => i.produktivitas_ton_ha),
                                    borderColor: colors.indigo.border, backgroundColor: gradient,
                                    borderWidth: 3, fill: true, tension: 0.4, 
                                    pointBackgroundColor: '#fff', pointBorderColor: colors.indigo.border, 
                                    pointBorderWidth: 2, pointRadius: 4, pointHoverRadius: 6
                                }]
                            },
                            options: { 
                                responsive: true, maintainAspectRatio: false,
                                plugins: { legend: { display: false } },
                                scales: { x: { display: false }, y: { border: { display: false } } }
                            }
                        });
                    }

                    // --- 5. RENDER POLAR AREA CHART ---
                    const ctxPolar = document.getElementById('polarChart');
                    if(ctxPolar && data.chart_luas_kecamatan) {
                        new Chart(ctxPolar.getContext('2d'), {
                            type: 'polarArea',
                            data: {
                                labels: data.chart_luas_kecamatan.map(i => i.nama_kecamatan),
                                datasets: [{
                                    data: data.chart_luas_kecamatan.map(i => i.total_luas),
                                    backgroundColor: ['rgba(16, 185, 129, 0.7)', 'rgba(59, 130, 246, 0.7)', 'rgba(245, 158, 11, 0.7)', 'rgba(139, 92, 246, 0.7)', 'rgba(236, 72, 153, 0.7)'],
                                    borderWidth: 2, borderColor: '#fff'
                                }]
                            },
                            options: { 
                                responsive: true, maintainAspectRatio: false,
                                plugins: { legend: { position: 'right', labels: { usePointStyle: true, boxWidth: 8 } } },
                                scales: { r: { ticks: { display: false } } }
                            }
                        });
                    }

                    // --- 6. RENDER TABEL DINAMIS ---
                    const tbody = document.getElementById('tabel-rekap-body');
                    if (tbody && data.tabel_rekap) {
                        tbody.innerHTML = '';
                        if (data.tabel_rekap.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="6" class="py-6 text-center text-slate-400">Tidak ada data lahan.</td></tr>';
                        } else {
                            data.tabel_rekap.forEach((item, index) => {
                                const totalLuas = parseFloat(item.total_luas || 0);
                                const totalPanen = parseFloat(item.total_panen || 0);
                                const rataProduktivitas = totalLuas > 0 ? (totalPanen / totalLuas) : 0;

                                const bgClass = index % 2 === 0 ? 'bg-white' : 'bg-slate-50/50';
                                
                                // Pastikan urutan <td> sesuai dengan urutan <th>
                                const row = `
                                    <tr class="${bgClass} hover:bg-slate-100 transition-colors">
                                        <td class="py-4 px-6 font-medium text-slate-800">${item.nama_kecamatan}</td>
                                        <td class="py-4 px-6 text-slate-600">${item.nama_kelurahan || '<span class="text-slate-400 italic">Tidak terdata</span>'}</td>
                                        <td class="py-4 px-6 text-center">
                                            <span class="inline-flex items-center justify-center px-3 py-1 text-xs font-bold rounded-full bg-slate-100 text-slate-600">
                                                ${item.jumlah_lahan} Lahan
                                            </span>
                                        </td>
                                        <td class="py-4 px-6 text-center font-semibold text-slate-700">${totalLuas.toFixed(2)} Ha</td>
                                        <td class="py-4 px-6 text-right font-semibold text-emerald-600">${totalPanen.toFixed(2)} Ton</td>
                                        <td class="py-4 px-6 text-right font-bold text-blue-600">${rataProduktivitas.toFixed(2)} Ton/Ha</td>
                                    </tr>
                                `;
                                tbody.insertAdjacentHTML('beforeend', row);
                            });
                        }
                    }
                }
            })
            .catch(err => {
                console.error("Gagal memuat API Statistik. Detail error:", err);
            });
    });
</script>