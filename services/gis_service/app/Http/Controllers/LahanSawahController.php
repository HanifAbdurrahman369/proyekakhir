<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\LahanSawah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LahanSawahController extends Controller
{
    // =========================================================================
    // FUNGSI BARU (DATA REFERENSI DROPDOWN)
    // =========================================================================
public function getReferensiData()
{
    return response()->json([
        'success' => true,
        'data' => [
            'petani' => DB::table('users')
                ->where('role_id', 1)
                ->select('id', 'nama_lengkap as name', 'nama_lengkap', 'email')
                ->orderBy('nama_lengkap')
                ->get(),

            'kecamatan' => DB::table('kecamatan')
                ->select('id', 'nama_kecamatan')
                ->orderBy('nama_kecamatan')
                ->get(),

            'kelurahan' => DB::table('kelurahan')
                ->select('id', 'kecamatan_id', 'nama_kelurahan')
                ->orderBy('nama_kelurahan')
                ->get(),

            'tipe_lahan' => DB::table('tipe_lahan')
                ->select('id', 'nama_tipe', 'deskripsi')
                ->orderBy('id')
                ->get(),
        ]
    ]);
}

    public function store(Request $request)
    {
        // Validasi ringan untuk memastikan geojson tidak memecah query SQL
        $geojson = json_encode(json_decode($request->geojson)); 

        try {
            DB::table('lahan_sawah')->insert([
                'user_id' => $request->user_id,
                'kecamatan_id' => $request->kecamatan_id,
                'kelurahan_id' => $request->kelurahan_id,
                'tipe_lahan_id' => $request->tipe_lahan_id,
                'nama_lahan' => $request->nama_lahan,
                'pemilik_lahan' => $request->pemilik_lahan,
                'luas_lahan_hektar' => $request->luas_lahan_hektar,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'polygon_area' => DB::raw("ST_GeomFromGeoJSON('{$geojson}')"),
                'created_at' => now(),
                'updated_at' => now()
            ]);
            return response()->json(['success' => true, 'message' => 'Lahan berhasil dipetakan.'], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menyimpan: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $updateData = $request->except(['geojson', '_method', '_token']);
        $updateData['updated_at'] = now();

        if ($request->has('geojson') && !empty($request->geojson)) {
            $geojson = json_encode(json_decode($request->geojson));
            $updateData['polygon_area'] = DB::raw("ST_GeomFromGeoJSON('{$geojson}')");
        }

        try {
            DB::table('lahan_sawah')->where('id', $id)->update($updateData);
            return response()->json(['success' => true, 'message' => 'Data spasial diperbarui.'], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal memperbarui: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        DB::table('lahan_sawah')->where('id', $id)->delete();
        return response()->json(['success' => true, 'message' => 'Lahan berhasil dihapus.'], 200);
    }

    // =========================================================================
    // FUNGSI LAMA (DISEMPURNAKAN UNTUK LEAFLET MAP FRONTEND)
    // =========================================================================
    public function index()
{
    $rows = DB::table('lahan_sawah')
        ->leftJoin('kecamatan', 'lahan_sawah.kecamatan_id', '=', 'kecamatan.id')
        ->leftJoin('kelurahan', 'lahan_sawah.kelurahan_id', '=', 'kelurahan.id')
        ->leftJoin('tipe_lahan', 'lahan_sawah.tipe_lahan_id', '=', 'tipe_lahan.id')
        ->select(
            'lahan_sawah.*',
            'kecamatan.nama_kecamatan',
            'kelurahan.nama_kelurahan',
            'tipe_lahan.nama_tipe',
            DB::raw('ST_AsGeoJSON(lahan_sawah.polygon_area) as geojson_polygon')
        )
        ->get();

    $features = [];

    foreach ($rows as $item) {
        $geometry = $item->geojson_polygon ? json_decode($item->geojson_polygon, true) : null;

        if (!$geometry && $item->latitude && $item->longitude) {
            $geometry = [
                'type' => 'Point',
                'coordinates' => [(float) $item->longitude, (float) $item->latitude],
            ];
        }

        if (!$geometry) {
            continue;
        }

        $features[] = [
            'type' => 'Feature',
            'geometry' => $geometry,
            'properties' => [
                'id' => $item->id,
                'user_id' => $item->user_id,
                'kecamatan_id' => $item->kecamatan_id,
                'kelurahan_id' => $item->kelurahan_id,
                'tipe_lahan_id' => $item->tipe_lahan_id,

                'nama_lahan' => $item->nama_lahan,
                'pemilik_lahan' => $item->pemilik_lahan,
                'pemilik' => $item->pemilik_lahan,

                'tipe_rawa' => $item->tipe_rawa,
                'nama_tipe' => $item->nama_tipe,
                'tahun_lbs' => $item->tahun_lbs,

                'luas_lahan_hektar' => (float) $item->luas_lahan_hektar,
                'luas_ha' => (float) $item->luas_lahan_hektar,

                'hasil_panen_ton' => (float) $item->hasil_panen_ton,
                'hasil_panen' => (float) $item->hasil_panen_ton,

                'produktivitas_ton_ha' => (float) $item->produktivitas_ton_ha,
                'produktivitas' => (float) $item->produktivitas_ton_ha,

                'alamat_detail' => $item->alamat_detail,
                'latitude' => $item->latitude ? (float) $item->latitude : null,
                'longitude' => $item->longitude ? (float) $item->longitude : null,

                'nama_kecamatan' => $item->nama_kecamatan,
                'nama_kelurahan' => $item->nama_kelurahan,
                'kecamatan' => $item->nama_kecamatan,
                'kelurahan' => $item->nama_kelurahan,
            ]
        ];
    }

    return response()->json([
        'success' => true,
        'type' => 'FeatureCollection',
        'features' => $features,
        'data' => [
            'type' => 'FeatureCollection',
            'features' => $features,
        ],
    ]);
}

    public function show($id)
    {
        $data = LahanSawah::selectRaw("
            id, nama_lahan, pemilik_lahan, luas_lahan_hektar, hasil_panen_ton, produktivitas_ton_ha,
            ST_AsGeoJSON(polygon_area) as polygon_area
        ")->find($id);

        if (!$data) return response()->json(['success' => false, 'message' => 'Data tidak ditemukan'], 404);

        return response()->json([
            'type' => 'Feature',
            'geometry' => json_decode($data->polygon_area),
            'properties' => $data
        ], 200);
    }
}