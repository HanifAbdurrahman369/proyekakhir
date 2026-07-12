@extends('layouts.app')

@section('title', 'Dashboard Admin')

@section('content')

@push('styles')
<style>
    .admin-section { animation: fadeUp .22s ease-out; }
    @keyframes fadeUp { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:translateY(0); } }
</style>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush

<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-7 admin-section">
    <div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-[#022c22] tracking-tight">Dashboard Administrator</h1>
        <p class="text-sm text-slate-500 mt-1 leading-relaxed">Ringkasan aktivitas dan metrik utama dari seluruh sistem SiPetani.</p>
    </div>
    <div class="flex flex-col sm:flex-row gap-2">
        <a href="/admin/users" class="flex items-center gap-2 bg-[#047857] hover:bg-[#065f46] text-white text-xs font-semibold px-4 py-2 rounded-[26px] transition shadow-[0_14px_38px_rgba(4,120,87,.16)] shadow-primary-200">
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
            Kelola Pengguna
        </a>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8 admin-section" style="animation-delay: 0.1s;">
    <!-- Card 1 -->
    <div class="bg-white rounded-[24px] p-6 border border-slate-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)] relative overflow-hidden flex flex-col justify-between">
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-emerald-500/10 rounded-full blur-2xl"></div>
        <div>
            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Pengguna</p>
            <h3 class="text-3xl font-extrabold text-[#022c22] mt-2">{{ number_format($stats['total_users']) }}</h3>
        </div>
        <div class="mt-4 flex items-center justify-between">
            <span class="text-xs font-semibold text-emerald-600 bg-emerald-50 px-2 py-1 rounded-lg">{{ $stats['total_petani'] }} Petani</span>
            <div class="w-8 h-8 rounded-full bg-slate-50 flex items-center justify-center text-slate-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
            </div>
        </div>
    </div>
    <!-- Card 2 -->
    <div class="bg-white rounded-[24px] p-6 border border-slate-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)] relative overflow-hidden flex flex-col justify-between">
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-blue-500/10 rounded-full blur-2xl"></div>
        <div>
            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Komunitas</p>
            <h3 class="text-3xl font-extrabold text-[#022c22] mt-2">{{ number_format($stats['total_komunitas']) }}</h3>
        </div>
        <div class="mt-4 flex items-center justify-between">
            <span class="text-xs font-semibold text-blue-600 bg-blue-50 px-2 py-1 rounded-lg">BPP & Gapoktan</span>
            <div class="w-8 h-8 rounded-full bg-slate-50 flex items-center justify-center text-slate-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            </div>
        </div>
    </div>
    <!-- Card 3 -->
    <div class="bg-white rounded-[24px] p-6 border border-slate-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)] relative overflow-hidden flex flex-col justify-between">
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-amber-500/10 rounded-full blur-2xl"></div>
        <div>
            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Lahan</p>
            <h3 class="text-3xl font-extrabold text-[#022c22] mt-2">{{ number_format($stats['total_lahan']) }}</h3>
        </div>
        <div class="mt-4 flex items-center justify-between">
            <span class="text-xs font-semibold text-amber-600 bg-amber-50 px-2 py-1 rounded-lg">Bidang Sawah</span>
            <div class="w-8 h-8 rounded-full bg-slate-50 flex items-center justify-center text-slate-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path></svg>
            </div>
        </div>
    </div>
    <!-- Card 4 -->
    <div class="bg-white rounded-[24px] p-6 border border-slate-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)] relative overflow-hidden flex flex-col justify-between">
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-emerald-500/10 rounded-full blur-2xl"></div>
        <div>
            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Siklus Panen</p>
            <h3 class="text-3xl font-extrabold text-[#022c22] mt-2">{{ number_format($stats['total_panen']) }}</h3>
        </div>
        <div class="mt-4 flex items-center justify-between">
            <span class="text-xs font-semibold text-emerald-600 bg-emerald-50 px-2 py-1 rounded-lg">Laporan Tanam</span>
            <div class="w-8 h-8 rounded-full bg-slate-50 flex items-center justify-center text-slate-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-8 admin-section" style="animation-delay: 0.2s;">
    <div class="bg-white rounded-[24px] p-6 border border-slate-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)]">
        <h3 class="text-sm font-bold text-[#022c22] mb-4">Distribusi Pengguna Berdasarkan Peran (Role)</h3>
        <div class="h-64 relative w-full flex items-center justify-center">
            <canvas id="roleChart"></canvas>
        </div>
    </div>
    <div class="bg-white rounded-[24px] p-6 border border-slate-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)]">
        <h3 class="text-sm font-bold text-[#022c22] mb-4">Komposisi Jenis Komunitas</h3>
        <div class="h-64 relative w-full flex items-center justify-center">
            <canvas id="komunitasChart"></canvas>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Data for Role Chart
        const roleData = {
            labels: ['Petani/Brigade', 'Petugas BPP', 'Pejabat/Admin'],
            datasets: [{
                data: [{{ $stats['total_petani'] }}, {{ $stats['total_petugas'] }}, {{ $stats['total_pejabat_admin'] }}],
                backgroundColor: ['#047857', '#3b82f6', '#f59e0b'],
                borderWidth: 0,
                hoverOffset: 4
            }]
        };

        new Chart(document.getElementById('roleChart'), {
            type: 'doughnut',
            data: roleData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: { font: { family: "'Poppins', sans-serif", size: 12 }, usePointStyle: true, boxWidth: 8 }
                    }
                },
                cutout: '70%'
            }
        });

        // Data for Komunitas Chart
        @php
            $jenisCounts = collect($komunitas)->countBy('jenis_komunitas');
            $kt = $jenisCounts->get('komunitas_tani', 0);
            $bp = $jenisCounts->get('brigade_pangan', 0);
        @endphp

        const komData = {
            labels: ['Komunitas Tani', 'Brigade Pangan'],
            datasets: [{
                data: [{{ $kt }}, {{ $bp }}],
                backgroundColor: ['#047857', '#f97316'],
                borderWidth: 0,
                hoverOffset: 4
            }]
        };

        new Chart(document.getElementById('komunitasChart'), {
            type: 'pie',
            data: komData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: { font: { family: "'Poppins', sans-serif", size: 12 }, usePointStyle: true, boxWidth: 8 }
                    }
                }
            }
        });
    });
</script>
@endpush
@endsection
