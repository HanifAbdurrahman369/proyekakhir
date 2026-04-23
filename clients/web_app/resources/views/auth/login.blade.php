@extends('layouts.guest')

@section('content')

<div class="bg-white p-8 rounded-xl shadow-md w-96">

    <h1 class="text-2xl font-bold mb-6 text-center text-primary-600">
        Login
    </h1>

    <form action="/login" method="POST">
        @csrf

        <input type="email"
               name="email"
               placeholder="Email"
               class="w-full border p-2 mb-3 rounded">

        <input type="password"
               name="password"
               placeholder="Password"
               class="w-full border p-2 mb-4 rounded">

        <button class="w-full bg-primary-500 hover:bg-primary-600 text-white py-2 rounded">
            Login
        </button>

    </form>

    <div class="text-center mt-4 text-sm">
        <a href="/register" class="text-primary-600">Daftar</a> |
        <a href="/forgot-password" class="text-primary-600">Lupa Password?</a>
    </div>

</div>

@endsection