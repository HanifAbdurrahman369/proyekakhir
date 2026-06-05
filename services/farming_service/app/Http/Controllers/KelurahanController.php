<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Kelurahan;

class KelurahanController extends Controller
{
    /**
     * Menampilkan semua data kelurahan
     */
    public function index()
    {
        $data = Kelurahan::all();

        return response()->json([
            'success' => true,
            'message' => 'Data kelurahan berhasil diambil',
            'data' => $data
        ], 200);
    }

    /**
     * Menampilkan detail kelurahan
     */
    public function show($id)
    {
        $data = Kelurahan::find($id);

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Data kelurahan tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail kelurahan',
            'data' => $data
        ], 200);
    }
}