<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\JenisBibit;

class JenisBibitController extends Controller
{
    /**
     * Menampilkan semua data jenis bibit
     */
    public function index()
    {
        $data = JenisBibit::all();

        return response()->json([
            'success' => true,
            'message' => 'Data jenis bibit berhasil diambil',
            'data' => $data
        ], 200);
    }

    /**
     * Menampilkan detail jenis bibit
     */
    public function show($id)
    {
        $data = JenisBibit::find($id);

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Data jenis bibit tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail jenis bibit',
            'data' => $data
        ], 200);
    }
}