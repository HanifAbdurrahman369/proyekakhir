<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ProduksiDaerahController extends Controller
{
    // Mengarah ke API Gateway (Port 8003)
    private $apiUrl = 'http://127.0.0.1:8003/api';

    private function api()
    {
        return Http::withToken(session('token'))->acceptJson()->withoutVerifying();
    }

    /**
     * Menampilkan halaman laporan produksi daerah
     * (Data di-fetch dari browser via /api/produksi-daerah)
     */
    public function index(Request $request)
    {
        return view('produksi-daerah');
    }

    /**
     * Fetch data produksi dari API
     */
    
}

