@extends('layouts.guest')

@section('content')

<div class="bg-white p-8 rounded-xl shadow-md w-96">

    <h1 class="text-2xl font-bold mb-6 text-center text-primary-600">
        Lupa Password
    </h1>

    <form action="/forgot-password" method="POST">
        @csrf

        <input type="email"
               name="email"
               placeholder="Masukkan Email"
               class="w-full border p-2 mb-4 rounded">

        <button class="w-full bg-primary-500 hover:bg-primary-600 text-white py-2 rounded">
            Kirim Link Reset
        </button>

    </form>

</div>

@endsection