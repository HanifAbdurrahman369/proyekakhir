<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Camat;

class KecamatanController extends Controller
{
    /**
     * Menampilkan semua data kecamatan
     */
    public function index()
    {
        $data = Camat::all();

        return response()->json([
            'success' => true,
            'message' => 'Data kecamatan berhasil diambil',
            'data' => $data
        ], 200);
    }

    /**
     * Menampilkan detail kecamatan
     */
    public function show($id)
    {
        $data = Camat::find($id);

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Data kecamatan tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail kecamatan',
            'data' => $data
        ], 200);
    }
}