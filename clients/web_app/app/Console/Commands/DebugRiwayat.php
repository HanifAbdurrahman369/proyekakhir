<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class DebugRiwayat extends Command
{
    protected $signature = 'debug:riwayat';
    protected $description = 'Debug riwayat panen';

    public function handle()
    {
        // Login as parjo to get a real token via gateway
        $login = Http::withoutVerifying()->post('http://127.0.0.1:8003/api/auth/login', [
            'email' => 'parjo@example.com',
            'password' => 'password123'
        ]);
        
        $token = $login->json('data.token');
        if (!$token) {
            $this->error('Failed to get token');
            return;
        }

        $res = Http::withToken($token)->withoutVerifying()->get('http://127.0.0.1:8003/api/riwayat-panen');
        $this->info(json_encode($res->json(), JSON_PRETTY_PRINT));
        
        $resPupuk = Http::withToken($token)->withoutVerifying()->get('http://127.0.0.1:8003/api/siklus-pupuk');
        $this->info("Pupuk: " . json_encode($resPupuk->json(), JSON_PRETTY_PRINT));
    }
}
