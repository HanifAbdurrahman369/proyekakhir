@extends('layouts.app')

@section('title', 'Laporan Produksi Daerah')

@section('content')
    <div class="pt-2 pb-12 min-h-screen">
        
        <!-- Panggil file komponen statistik dan tampilkan tabel beserta flag isPejabat untuk tombol cetak -->
        @include('statistik', ['showTable' => true, 'isPejabat' => true])

    </div>
@endsection
