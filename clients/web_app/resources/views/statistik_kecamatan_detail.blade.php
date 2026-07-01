@extends('layouts.public')

@section('content')
<div class="min-h-screen bg-slate-50 pt-28 pb-16 px-4 font-['Poppins']">
    <div class="mx-auto flex w-full max-w-7xl flex-col gap-8">
        <div class="flex flex-col gap-5 rounded-[2rem] border border-slate-100 bg-white p-6 shadow-sm md:p-8">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div class="space-y-3">
                    <p class="text-xs font-bold uppercase tracking-[0.25em] text-emerald-600">Detail Statistik Kecamatan</p>
                    <h1 id="detail-title" class="text-3xl font-extrabold tracking-tight text-slate-900 md:text-5xl">Memuat data...</h1>
                    <p id="detail-subtitle" class="max-w-3xl text-sm font-medium leading-relaxed text-slate-500 md:text-base">
                        Data historis padi per kecamatan Kabupaten Barito Kuala.
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <a href="{{ url('/') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-600 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700">
                        Kembali ke Dashboard
                    </a>
                    <a href="{{ route('statistik.publik') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 px-5 py-3 text-sm font-bold text-slate-600 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700">
                        Data Statistik
                    </a>
                    <a id="download-excel" href="#" class="inline-flex items-center justify-center rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-emerald-200 transition hover:bg-emerald-700">
                        Download Excel
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-2xl bg-slate-50 p-5">
                    <p class="text-[11px] font-bold uppercase tracking-widest text-slate-400">Periode</p>
                    <p id="summary-periode" class="mt-2 text-2xl font-black text-slate-800">-</p>
                </div>
                <div class="rounded-2xl bg-emerald-50 p-5">
                    <p class="text-[11px] font-bold uppercase tracking-widest text-emerald-600">Total Produksi</p>
                    <p id="summary-produksi" class="mt-2 text-2xl font-black text-emerald-700">-</p>
                </div>
                <div class="rounded-2xl bg-blue-50 p-5">
                    <p class="text-[11px] font-bold uppercase tracking-widest text-blue-600">Luas Panen</p>
                    <p id="summary-panen" class="mt-2 text-2xl font-black text-blue-700">-</p>
                </div>
                <div class="rounded-2xl bg-amber-50 p-5">
                    <p class="text-[11px] font-bold uppercase tracking-widest text-amber-600">Produktivitas</p>
                    <p id="summary-produktivitas" class="mt-2 text-2xl font-black text-amber-700">-</p>
                </div>
            </div>
        </div>

        <div class="rounded-[2rem] border border-slate-100 bg-white/80 p-6 shadow-sm backdrop-blur md:p-8">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-3xl space-y-2">
                    <h2 class="text-xl font-extrabold text-slate-900">Rincian Luas Tanam, Panen, Produktivitas, dan Produksi</h2>
                    <p id="detail-narasi" class="text-sm font-medium leading-relaxed text-slate-500">
                        Menyiapkan ringkasan data kecamatan.
                    </p>
                </div>

                <div class="w-full max-w-xs space-y-2">
                    <label for="filter-tahun-detail" class="ml-1 text-xs font-bold uppercase tracking-widest text-slate-400">Filter Tahun</label>
                    <select id="filter-tahun-detail" class="custom-select w-full rounded-2xl border-0 bg-slate-50 px-5 py-3.5 text-sm font-bold text-slate-600 shadow-inner focus:ring-2 focus:ring-emerald-500">
                        <option value="all">Semua Tahun</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-[2rem] border border-slate-100 bg-white shadow-sm">
            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full min-w-[1050px] whitespace-nowrap text-left">
                    <thead class="border-b border-slate-100 bg-slate-50 text-[11px] font-bold uppercase tracking-[0.15em] text-slate-400">
                        <tr>
                            <th class="px-7 py-5">Tahun</th>
                            <th class="px-7 py-5 text-right">Luas Tanam (Ha)</th>
                            <th class="px-7 py-5 text-right">Luas Panen (Ha)</th>
                            <th class="px-7 py-5 text-right">Produktivitas (Kw/Ha)</th>
                            <th class="px-7 py-5 text-right">Produktivitas (Ton/Ha)</th>
                            <th class="px-7 py-5 text-right">Produksi (Ton)</th>
                            <th class="px-7 py-5 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody id="detail-table-body" class="divide-y divide-slate-50 text-base text-slate-600">
                        <tr><td colspan="7" class="py-16 text-center italic text-slate-300">Memuat data statistik...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    .custom-select {
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2394a3b8'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
        background-position: right 1.25rem center;
        background-repeat: no-repeat;
        background-size: 1rem;
    }
    .custom-scrollbar::-webkit-scrollbar { height: 8px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border: 2px solid #fff; border-radius: 10px; }
</style>

<script>
    const kecamatanIdentifier = @json($kecamatanIdentifier);
    const gatewayBase = window.GATEWAY_URL || "{{ env('GATEWAY_URL', 'http://127.0.0.1:8003') }}";
    let resolvedKecamatanId = kecamatanIdentifier;

    document.addEventListener('DOMContentLoaded', () => {
        loadDetail('all');

        document.getElementById('filter-tahun-detail')?.addEventListener('change', (event) => {
            loadDetail(event.target.value);
        });
    });

    function detailApiUrl(tahun = 'all') {
        const url = new URL(`${gatewayBase}/api/statistik/kecamatan/${encodeURIComponent(resolvedKecamatanId || kecamatanIdentifier)}`);
        if (tahun && tahun !== 'all') url.searchParams.set('tahun', tahun);
        return url.toString();
    }

    function detailDownloadUrl(tahun = 'all') {
        const url = new URL(`${gatewayBase}/api/statistik/kecamatan/${encodeURIComponent(resolvedKecamatanId || kecamatanIdentifier)}/excel`);
        if (tahun && tahun !== 'all') url.searchParams.set('tahun', tahun);
        return url.toString();
    }

    function loadDetail(tahun = 'all') {
        const tbody = document.getElementById('detail-table-body');
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="7" class="py-16 text-center italic text-slate-300">Memuat data statistik...</td></tr>';
        }

        fetch(detailApiUrl(tahun))
            .then(response => response.json())
            .then(response => {
                if (!response.success) throw new Error(response.message || 'Data tidak tersedia');
                renderDetail(response.data, tahun);
            })
            .catch(error => {
                document.getElementById('detail-title').textContent = 'Data tidak tersedia';
                document.getElementById('detail-subtitle').textContent = error.message || 'Gagal memuat detail kecamatan.';
                if (tbody) {
                    tbody.innerHTML = '<tr><td colspan="7" class="py-16 text-center font-medium text-slate-400">Data tidak ditemukan.</td></tr>';
                }
            });
    }

    function renderDetail(data, activeYear) {
        const kecamatan = data.kecamatan || {};
        resolvedKecamatanId = kecamatan.id || resolvedKecamatanId;

        document.getElementById('detail-title').textContent = `Kecamatan ${kecamatan.nama_kecamatan || '-'}`;
        document.getElementById('detail-subtitle').textContent = 'Detail data padi historis Kabupaten Barito Kuala tahun 2010 sampai 2025.';
        document.getElementById('detail-narasi').textContent = data.narasi || '-';

        populateYearFilter(data.tahun_options || [], activeYear);
        renderSummary(data.summary || {});
        renderRows(data.rows || []);

        const download = document.getElementById('download-excel');
        if (download) download.href = detailDownloadUrl(activeYear);
    }

    function populateYearFilter(years, activeYear) {
        const select = document.getElementById('filter-tahun-detail');
        if (!select) return;

        const nextValue = activeYear || select.value || 'all';
        select.innerHTML = '<option value="all">Semua Tahun</option>';
        years.forEach(year => {
            const option = document.createElement('option');
            option.value = year;
            option.textContent = year;
            select.appendChild(option);
        });
        select.value = [...select.options].some(option => option.value === String(nextValue)) ? String(nextValue) : 'all';
    }

    function renderSummary(summary) {
        setText('summary-periode', summary.periode_label || '-');
        setText('summary-produksi', `${formatNumber(summary.total_produksi_ton)} Ton`);
        setText('summary-panen', `${formatNumber(summary.total_luas_panen_ha)} Ha`);
        setText('summary-produktivitas', `${formatNumber(summary.rata_produktivitas_ton_ha, 3)} Ton/Ha`);
    }

    function renderRows(rows) {
        const tbody = document.getElementById('detail-table-body');
        if (!tbody) return;

        if (!rows.length) {
            tbody.innerHTML = '<tr><td colspan="7" class="py-16 text-center font-medium text-slate-400">Data tidak ditemukan.</td></tr>';
            return;
        }

        tbody.innerHTML = rows.map((row, index) => {
            const bg = index % 2 === 0 ? 'bg-white' : 'bg-slate-50/40';
            const statusClass = row.is_sementara
                ? 'bg-amber-50 text-amber-700 border-amber-100'
                : 'bg-emerald-50 text-emerald-700 border-emerald-100';

            return `
                <tr class="${bg} hover:bg-emerald-50/40 transition-colors">
                    <td class="px-7 py-5 font-bold text-slate-800">${escapeHtml(row.tahun)}</td>
                    <td class="px-7 py-5 text-right">${formatNumber(row.luas_tanam_ha)} Ha</td>
                    <td class="px-7 py-5 text-right">${formatNumber(row.luas_panen_ha)} Ha</td>
                    <td class="px-7 py-5 text-right">${formatNumber(row.produktivitas_kw_ha, 2)}</td>
                    <td class="px-7 py-5 text-right font-bold text-blue-700">${formatNumber(row.produktivitas_ton_ha, 3)}</td>
                    <td class="px-7 py-5 text-right font-bold text-emerald-700">${formatNumber(row.produksi_ton)} Ton</td>
                    <td class="px-7 py-5 text-center">
                        <span class="inline-flex rounded-full border px-3 py-1 text-xs font-bold ${statusClass}">${escapeHtml(row.status_data)}</span>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function setText(id, value) {
        const el = document.getElementById(id);
        if (el) el.textContent = value;
    }

    function formatNumber(value, fractionDigits = 2) {
        const number = Number(value || 0);
        return number.toLocaleString('id-ID', {
            minimumFractionDigits: 0,
            maximumFractionDigits: fractionDigits
        });
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
</script>
@endsection
