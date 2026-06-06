<?php

namespace App\Http\Controllers;

use App\Models\SiklusTanam;
use Illuminate\Http\Request;

class RiwayatPanenController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->attributes->get('auth');

$perPage = $request->get('per_page', 5);

$data = SiklusTanam::with([
        'lahan:id,nama_lahan,luas_lahan_hektar',
        'bibit:id,nama_bibit'
    ])
    ->where('created_by', $user->sub)
    ->latest()
    ->paginate($perPage, ['*'], 'riwayat_page');
            $data->getCollection()->transform(function ($item) {

    return [
        'id' => $item->id,
        'lahan_id' => $item->lahan_id,
        'bibit_id' => $item->bibit_id,
        'tanggal_tanam' => $item->tanggal_tanam,
        'tanggal_panen' => $item->tanggal_panen,
        'estimasi_panen' => $item->estimasi_panen,
        'hasil_panen' => $item->hasil_panen,
        'status_aktif' => $item->status_aktif,
        'status_verifikasi' => $item->status_verifikasi,
        'created_by' => $item->created_by,
        'lahan' => $item->lahan,
        'bibit' => $item->bibit,
    ];

});

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
     }
  }
