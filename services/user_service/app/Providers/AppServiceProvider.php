<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Http;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Http::globalOptions([
            'headers' => [
                'Connection' => 'close',
            ],
        ]);

        ResetPassword::createUrlUsing(function ($user, string $token) {

            return 'http://localhost:8080/reset-password/' .
                    $token .
                    '?email=' . urlencode($user->email);

        });
    }
}