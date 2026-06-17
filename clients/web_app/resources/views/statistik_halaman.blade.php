@extends('layouts.public')

@section('content')
    <div class="pt-24 pb-12 min-h-screen bg-slate-50">
        
        <!-- Panggil file komponen statistik dan tampilkan tabel -->
        @include('statistik', ['showTable' => true])

    </div>
@endsection