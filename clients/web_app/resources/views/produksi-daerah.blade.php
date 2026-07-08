@extends('layouts.app')

@section('title', 'Laporan Produksi Daerah')

@section('content')
    <div class="pt-16 pb-12 min-h-screen bg-slate-50">
        
        <!-- Panggil file komponen statistik dan tampilkan tabel beserta flag isPejabat untuk tombol cetak -->
        @include('statistik', ['showTable' => true, 'isPejabat' => true])

    </div>
@endsection
