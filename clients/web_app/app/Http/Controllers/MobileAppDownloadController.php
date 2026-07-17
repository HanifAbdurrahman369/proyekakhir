<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MobileAppDownloadController extends Controller
{
    private const ALLOWED_ROLES = [1, 2, 5];
    private const APK_FILENAME = 'SiPetani.apk';

    public function download(Request $request)
    {
        if (!session('token') || !session('role_id')) {
            session(['pending_mobile_app_download' => true]);

            return redirect()
                ->route('login')
                ->with('success', 'Silakan login sebagai Kelompok Tani, Brigade Pangan, atau Petugas untuk mengunduh aplikasi mobile.');
        }

        if (!in_array((int) session('role_id'), self::ALLOWED_ROLES, true)) {
            abort(403, 'Unduhan aplikasi mobile hanya tersedia untuk Kelompok Tani, Brigade Pangan, dan Petugas.');
        }

        $apk = $this->apkInformation();

        return view('mobile-app.download', [
            'apkVersion' => $apk['version'],
            'apkFingerprint' => $apk['fingerprint'],
            'apkUpdatedAt' => $apk['updated_at'],
            'apkSizeMb' => $apk['size_mb'],
        ]);
    }

    public function downloadFile(Request $request)
    {
        if (!session('token') || !session('role_id')) {
            return redirect()->route('login');
        }

        if (!in_array((int) session('role_id'), self::ALLOWED_ROLES, true)) {
            abort(403, 'Unduhan aplikasi mobile hanya tersedia untuk Kelompok Tani, Brigade Pangan, dan Petugas.');
        }

        $apkPath = storage_path('app/' . self::APK_FILENAME);

        if (!is_file($apkPath)) {
            abort(404, 'File aplikasi mobile (SiPetani.apk) belum tersedia di server produksi. Silakan upload file apk ke folder storage/app/ di server.');
        }

        clearstatcache(true, $apkPath);
        $sha256 = hash_file('sha256', $apkPath);
        $metadata = $this->readMetadata();

        return response()->download($apkPath, self::APK_FILENAME, [
            'Content-Type' => 'application/vnd.android.package-archive',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'X-SiPetani-APK-SHA256' => $sha256,
            'X-SiPetani-APK-Version' => (string) ($metadata['version'] ?? 'unknown'),
        ])->setPrivate();
    }

    /**
     * Informasi paket dihitung dari file aktual agar URL berubah setiap APK
     * diganti dan browser tidak menggunakan paket lama dari cache.
     */
    private function apkInformation(): array
    {
        $path = storage_path('app/' . self::APK_FILENAME);
        if (!is_file($path)) {
            return [
                'version' => 'belum tersedia',
                'fingerprint' => 'missing',
                'updated_at' => '-',
                'size_mb' => '0,00',
            ];
        }

        clearstatcache(true, $path);
        $metadata = $this->readMetadata();
        $sha256 = hash_file('sha256', $path);

        return [
            'version' => (string) ($metadata['version'] ?? 'terbaru'),
            'fingerprint' => substr($sha256, 0, 16),
            'updated_at' => date('d-m-Y H:i', filemtime($path)),
            'size_mb' => number_format(filesize($path) / 1048576, 2, ',', '.'),
        ];
    }

    private function readMetadata(): array
    {
        $path = storage_path('app/SiPetani.json');
        if (!is_file($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : [];
    }
}
