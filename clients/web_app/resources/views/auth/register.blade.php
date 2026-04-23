@extends('layouts.guest')

@section('content')

<div class="bg-white p-8 rounded-xl shadow-md w-96">

    <h1 class="text-2xl font-bold mb-6 text-center text-primary-600">
        Register
    </h1>

    <form action="/register" method="POST">
        @csrf

        <input type="text"
               name="nama_lengkap"
               placeholder="Nama Lengkap"
               class="w-full border p-2 mb-3 rounded">

        <input type="email"
               name="email"
               placeholder="Email"
               class="w-full border p-2 mb-3 rounded">

        <input type="password"
               name="password"
               placeholder="Password"
               class="w-full border p-2 mb-4 rounded">

        <input type="password"
               name="password_confirmation"
               placeholder="Konfirmasi Password"
               class="w-full border p-2 mb-4 rounded">
        
        <input type="text"
               name="nomor_handphone"
               placeholder="Nomor Handphone"
               class="w-full border p-2 mb-4 rounded">

        <input type="text"
               name="alamat"
               placeholder="Alamat"
               class="w-full border p-2 mb-4 rounded">

        <button class="w-full bg-primary-500 hover:bg-primary-600 text-white py-2 rounded">
            Register
        </button>

    </form>

</div>

@endsection