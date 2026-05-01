@extends('layouts.guest')

@section('content')

<div class="bg-white p-8 rounded-xl shadow-md w-96">

    <h1 class="text-2xl font-bold mb-6 text-center">
        Login
    </h1>

    <form action="/login" method="POST">
        @csrf

        <input
            type="email"
            name="email"
            placeholder="Email"
            class="w-full border p-2 mb-3 rounded"
            required
        >

        <input
            type="password"
            name="password"
            placeholder="Password"
            class="w-full border p-2 mb-4 rounded"
            required
        >

        <!-- CAPTCHA V2 -->
        <div class="mb-4">
            <div
                class="g-recaptcha"
                data-sitekey="{{ env('CAPTCHA_SITE_KEY') }}">
            </div>
        </div>

        <button
            type="submit"
            class="w-full bg-blue-500 text-white py-2 rounded"
        >
            Login
        </button>

    </form>
</div>

<!-- SCRIPT CAPTCHA -->
<script
    src="https://www.google.com/recaptcha/api.js"
    async
    defer>
</script>

@endsection