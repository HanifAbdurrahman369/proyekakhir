<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MobileAppDownloadController extends Controller
{
    private const ALLOWED_ROLES = [1, 2, 5];

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

        $apkPath = storage_path('app/sipetani-mobile.apk');

        if (!is_file($apkPath)) {
            return redirect()
                ->back()
                ->with('error', 'File aplikasi mobile belum tersedia. Silakan hubungi admin.');
        }

        return response()->download($apkPath, 'sipetani-mobile.apk', [
            'Content-Type' => 'application/vnd.android.package-archive',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }
}
