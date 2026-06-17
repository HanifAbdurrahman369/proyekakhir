<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\TipeLahan;

class TipeLahanController extends Controller
{
    /**
     * Menampilkan semua data tipe lahan
     */
    public function index()
    {
        $data = TipeLahan::all();

        return response()->json([
            'success' => true,
            'message' => 'Data tipe lahan berhasil diambil',
            'data' => $data
        ], 200);
    }

    /**
     * Menampilkan detail tipe lahan
     */
    public function show($id)
    {
        $data = TipeLahan::find($id);

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Data tipe lahan tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail tipe lahan',
            'data' => $data
        ], 200);
    }
}