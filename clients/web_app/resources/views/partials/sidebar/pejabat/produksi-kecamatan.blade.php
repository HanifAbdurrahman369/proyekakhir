@extends('layouts.app')

@section('title', 'Produksi per Kecamatan (Historis)')

@section('content')

<style>
    .custom-scrollbar::-webkit-scrollbar { height: 8px; width: 8px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-extrabold text-[#022c22]">
            Historis Produksi Kecamatan
        </h1>
        <p class="text-sm text-slate-500 mt-1">
            Lihat riwayat produksi komoditas padi per kecamatan (2010 - 2025).
        </p>
    </div>

    <div class="flex items-center gap-3">
        <a href="javascript:history.back()"
           class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl
                  bg-white border border-[#d1fae5] text-[#047857] font-bold text-sm
                  shadow-md hover:bg-[#ecfdf5] hover:scale-105 transition-all duration-300">
            ← Kembali
        </a>
    </div>
</div>

<div class="glass-card rounded-[28px] p-6 mb-6">
    <div class="flex flex-col md:flex-row md:items-end gap-5">
        <div class="w-full md:w-1/3">
            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Pilih Kecamatan</label>
            <select id="kecamatan-select" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium focus:ring-[#047857] focus:border-[#047857] transition">
                <option value="">-- Pilih Kecamatan --</option>
                @foreach($kecamatans as $kecamatan)
                    <option value="{{ $kecamatan['id'] }}">{{ $kecamatan['nama_kecamatan'] }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-full md:w-1/4">
            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Pilih Tahun</label>
            <select id="tahun-select" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium focus:ring-[#047857] focus:border-[#047857] transition" disabled>
                <option value="all">Semua Tahun</option>
            </select>
        </div>
        <div class="flex-grow flex justify-end gap-3 mt-4 md:mt-0">
            <a href="#" id="export-pdf-btn" target="_blank"
               class="hidden items-center gap-2 px-5 py-3 rounded-2xl
                      bg-gradient-to-r from-rose-600 to-rose-500 text-white font-bold text-sm
                      shadow-md hover:scale-105 transition-all duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                Export PDF
            </a>
            <a href="#" id="export-excel-btn" target="_blank"
               class="hidden items-center gap-2 px-5 py-3 rounded-2xl
                      bg-gradient-to-r from-[#047857] to-[#065f46] text-white font-bold text-sm
                      shadow-md hover:scale-105 transition-all duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                Export Excel
            </a>
        </div>
    </div>
</div>

<div class="glass-card rounded-[28px] p-6 hidden" id="data-container">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="rounded-2xl bg-slate-50 p-4 border border-slate-100">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Total Luas Tanam</p>
            <p class="text-xl font-extrabold text-emerald-700"><span id="summary-luas-tanam">0</span> <span class="text-sm text-slate-500 font-medium">Ha</span></p>
        </div>
        <div class="rounded-2xl bg-slate-50 p-4 border border-slate-100">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Total Produksi</p>
            <p class="text-xl font-extrabold text-amber-600"><span id="summary-produksi">0</span> <span class="text-sm text-slate-500 font-medium">Ton</span></p>
        </div>
        <div class="rounded-2xl bg-slate-50 p-4 border border-slate-100">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Rata-rata Produksi</p>
            <p class="text-xl font-extrabold text-blue-600"><span id="summary-avg">0</span> <span class="text-sm text-slate-500 font-medium">Ton/Ha</span></p>
        </div>
        <div class="rounded-2xl bg-slate-50 p-4 border border-slate-100">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Jumlah Data</p>
            <p class="text-xl font-extrabold text-purple-600"><span id="summary-count">0</span> <span class="text-sm text-slate-500 font-medium">Record</span></p>
        </div>
    </div>

    <div class="overflow-x-auto custom-scrollbar">
        <table class="w-full text-sm text-left">
            <thead class="text-xs text-slate-500 uppercase bg-slate-50 rounded-xl">
                <tr>
                    <th class="px-6 py-4 rounded-tl-2xl font-bold">No</th>
                    <th class="px-6 py-4 font-bold">Tahun</th>
                    <th class="px-6 py-4 text-right font-bold">Luas Tanam (Ha)</th>
                    <th class="px-6 py-4 text-right font-bold">Luas Panen (Ha)</th>
                    <th class="px-6 py-4 text-right font-bold">Produktivitas (Ton/Ha)</th>
                    <th class="px-6 py-4 text-right font-bold">Produksi (Ton)</th>
                    <th class="px-6 py-4 font-bold">Status Data</th>
                    <th class="px-6 py-4 rounded-tr-2xl font-bold">Sumber</th>
                </tr>
            </thead>
            <tbody id="detail-table-body" class="divide-y divide-slate-100">
                <!-- Data will be loaded here -->
            </tbody>
        </table>
    </div>
</div>

<div class="glass-card rounded-[28px] p-12 text-center" id="empty-state">
    <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10 text-slate-300">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
        </svg>
    </div>
    <h3 class="text-lg font-bold text-slate-700 mb-1">Pilih Kecamatan</h3>
    <p class="text-slate-500 text-sm">Silakan pilih kecamatan terlebih dahulu untuk melihat data historis.</p>
</div>

<script>
    const gatewayBase = window.GATEWAY_URL || "{{ env('GATEWAY_URL', 'http://127.0.0.1:8003') }}";
    
    document.addEventListener('DOMContentLoaded', () => {
        const kecamatanSelect = document.getElementById('kecamatan-select');
        const tahunSelect = document.getElementById('tahun-select');
        
        kecamatanSelect.addEventListener('change', (e) => {
            const id = e.target.value;
            if (id) {
                loadData(id, 'all');
            } else {
                hideData();
            }
        });

        tahunSelect.addEventListener('change', (e) => {
            const id = kecamatanSelect.value;
            if (id) {
                loadData(id, e.target.value);
            }
        });

        // Cek URL parameter untuk auto-select kecamatan
        const urlParams = new URLSearchParams(window.location.search);
        const kecamatanParam = urlParams.get('kecamatan');
        
        if (kecamatanParam) {
            let found = false;
            Array.from(kecamatanSelect.options).forEach(opt => {
                if (opt.value === kecamatanParam || opt.text.toLowerCase() === kecamatanParam.toLowerCase()) {
                    kecamatanSelect.value = opt.value;
                    found = true;
                }
            });
            if (found) {
                loadData(kecamatanSelect.value, 'all');
            }
        } else {
            // Auto select the first kecamatan if available
            if (kecamatanSelect.options.length > 1) {
                kecamatanSelect.value = kecamatanSelect.options[1].value;
                loadData(kecamatanSelect.value, 'all');
            }
        }
    });

    function loadData(kecamatanId, tahun) {
        const url = new URL(`${gatewayBase}/api/statistik/kecamatan/${encodeURIComponent(kecamatanId)}`);
        if (tahun && tahun !== 'all') url.searchParams.set('tahun', tahun);

        const tbody = document.getElementById('detail-table-body');
        tbody.innerHTML = '<tr><td colspan="8" class="py-10 text-center text-slate-400">Memuat data...</td></tr>';
        
        document.getElementById('empty-state').classList.add('hidden');
        document.getElementById('data-container').classList.remove('hidden');

        fetch(url.toString())
            .then(res => res.json())
            .then(res => {
                if (!res.success) throw new Error(res.message);
                renderData(res.data, kecamatanId, tahun);
            })
            .catch(err => {
                tbody.innerHTML = `<tr><td colspan="8" class="py-10 text-center text-red-500">Gagal memuat data: ${err.message}</td></tr>`;
            });
    }

    function renderData(data, kecamatanId, activeYear) {
        // Render summary
        const summary = data.summary || {};
        document.getElementById('summary-luas-tanam').textContent = Number(summary.total_luas_tanam_ha || 0).toLocaleString('id-ID', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('summary-produksi').textContent = Number(summary.total_produksi_ton || 0).toLocaleString('id-ID', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('summary-avg').textContent = Number(summary.rata_produktivitas_ton_ha || 0).toLocaleString('id-ID', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('summary-count').textContent = Number(summary.jumlah_tahun || 0).toLocaleString('id-ID');

        // Populate years if needed
        const tahunSelect = document.getElementById('tahun-select');
        if (activeYear === 'all' || tahunSelect.options.length <= 1) {
            tahunSelect.innerHTML = '<option value="all">Semua Tahun</option>';
            const years = data.tahun_options || [];
            years.forEach(year => {
                const option = document.createElement('option');
                option.value = year;
                option.textContent = `Tahun ${year}`;
                if (year == activeYear) option.selected = true;
                tahunSelect.appendChild(option);
            });
            tahunSelect.disabled = false;
        }

        // Render table
        const tbody = document.getElementById('detail-table-body');
        const rows = data.rows || [];
        
        if (rows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="py-10 text-center text-slate-400">Tidak ada data untuk filter yang dipilih.</td></tr>';
        } else {
            let html = '';
            rows.forEach((row, i) => {
                html += `<tr class="border-b border-slate-50 hover:bg-emerald-50/30 transition">
                    <td class="px-6 py-4 font-medium text-slate-500">${i + 1}</td>
                    <td class="px-6 py-4 font-bold text-slate-700">${row.tahun || '-'}</td>
                    <td class="px-6 py-4 text-right font-medium text-slate-700">${Number(row.luas_tanam_ha || 0).toLocaleString('id-ID', {minimumFractionDigits: 2})}</td>
                    <td class="px-6 py-4 text-right font-medium text-slate-700">${Number(row.luas_panen_ha || 0).toLocaleString('id-ID', {minimumFractionDigits: 2})}</td>
                    <td class="px-6 py-4 text-right font-medium text-slate-600">${Number(row.produktivitas_ton_ha || 0).toLocaleString('id-ID', {minimumFractionDigits: 2})}</td>
                    <td class="px-6 py-4 text-right font-bold text-emerald-600">${Number(row.produksi_ton || 0).toLocaleString('id-ID', {minimumFractionDigits: 2})}</td>
                    <td class="px-6 py-4 text-slate-600">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${row.is_sementara ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800'}">
                            ${row.status_data || '-'}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-slate-500 text-xs">${row.sumber_data || '-'}</td>
                </tr>`;
            });
            tbody.innerHTML = html;
        }

        // Update export buttons
        const pdfBtn = document.getElementById('export-pdf-btn');
        const excelBtn = document.getElementById('export-excel-btn');
        
        pdfBtn.href = `/pejabat/produksi-kecamatan/pdf?kecamatan=${kecamatanId}&tahun=${activeYear === 'all' ? '' : activeYear}`;
        excelBtn.href = `/pejabat/produksi-kecamatan/excel?kecamatan=${kecamatanId}&tahun=${activeYear === 'all' ? '' : activeYear}`;
        
        pdfBtn.classList.remove('hidden');
        pdfBtn.classList.add('inline-flex');
        excelBtn.classList.remove('hidden');
        excelBtn.classList.add('inline-flex');
    }

    function hideData() {
        document.getElementById('empty-state').classList.remove('hidden');
        document.getElementById('data-container').classList.add('hidden');
        const tahunSelect = document.getElementById('tahun-select');
        tahunSelect.innerHTML = '<option value="all">Semua Tahun</option>';
        tahunSelect.disabled = true;
        
        const pdfBtn = document.getElementById('export-pdf-btn');
        const excelBtn = document.getElementById('export-excel-btn');
        pdfBtn.classList.add('hidden');
        pdfBtn.classList.remove('inline-flex');
        excelBtn.classList.add('hidden');
        excelBtn.classList.remove('inline-flex');
    }
</script>

@endsection
