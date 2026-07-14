<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (isset($_SERVER['HTTP_HOST'])) {
            $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || env('FORCE_HTTPS', true) ? 'https' : 'http';
            config(['app.url' => $scheme . '://' . $_SERVER['HTTP_HOST']]);
        }

        if (env('FORCE_HTTPS', true)) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        \Illuminate\Support\Facades\Http::globalOptions([
            'headers' => [
                'Connection' => 'close',
            ],
            'connect_timeout' => 5,
            'timeout' => 15,
        ]);
    }
}
