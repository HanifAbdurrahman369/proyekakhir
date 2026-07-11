<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\HumaIntegrationService;
use Illuminate\Support\Facades\Log;

class SyncHumaData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'huma:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Melakukan sinkronisasi data lahan dan sensor dari perangkat Huma secara otomatis';

    /**
     * Execute the console command.
     */
    public function handle(HumaIntegrationService $humaService)
    {
        $this->info('Memulai sinkronisasi data Huma...');
        Log::info('[HUMA SYNC] Sinkronisasi otomatis berjalan');

        try {
            $result = $humaService->syncData();
            
            if ($result['success']) {
                $this->info('Sinkronisasi berhasil: ' . $result['message']);
                Log::info('[HUMA SYNC] Berhasil', $result);
            } else {
                $this->error('Sinkronisasi gagal: ' . ($result['message'] ?? 'Unknown error'));
                Log::error('[HUMA SYNC] Gagal', $result);
            }
        } catch (\Exception $e) {
            $this->error('Terjadi kesalahan sistem: ' . $e->getMessage());
            Log::error('[HUMA SYNC] Exception: ' . $e->getMessage());
        }

        $this->info('Selesai.');
    }
}
