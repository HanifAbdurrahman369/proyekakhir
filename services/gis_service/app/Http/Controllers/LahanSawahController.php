<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LahanSawahController extends Controller
{
    /**
     * AMBIL DATA REFERENSI (Untuk Dropdown Form Petugas)
     */
    public function getReferensiData()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'petani' => DB::table('users')->where('role_id', 1)->select('id', 'name')->get(),
                'kecamatan' => DB::table('kecamatan')->select('id', 'nama_kecamatan')->get(),
                'kelurahan' => DB::table('kelurahan')->select('id', 'kecamatan_id', 'nama_kelurahan')->get(),
                'tipe_lahan' => DB::table('tipe_lahan')->select('id', 'nama_tipe')->get()
            ]
        ], 200);
    }

    /**
     * BACA DATA SPASIAL: Tarik Semua Poligon (Format GeoJSON)
     */
    public function index()
    {
        $lahan = DB::table('lahan_sawah')
            ->leftJoin('kecamatan', 'lahan_sawah.kecamatan_id', '=', 'kecamatan.id')
            ->leftJoin('kelurahan', 'lahan_sawah.kelurahan_id', '=', 'kelurahan.id')
            ->selectRaw("
                lahan_sawah.id, lahan_sawah.user_id, lahan_sawah.nama_lahan, lahan_sawah.pemilik_lahan,
                lahan_sawah.luas_lahan_hektar, lahan_sawah.tipe_rawa, lahan_sawah.alamat_detail,
                lahan_sawah.latitude, lahan_sawah.longitude,
                kecamatan.nama_kecamatan, kelurahan.nama_kelurahan,
                ST_AsGeoJSON(lahan_sawah.polygon_area) as geojson_polygon
            ")->get();

        $features = [];
        foreach ($lahan as $item) {
            $features[] = [
                'type' => 'Feature',
                'geometry' => json_decode($item->geojson_polygon),
                'properties' => [
                    'id' => $item->id,
                    'nama_lahan' => $item->nama_lahan,
                    'pemilik_lahan' => $item->pemilik_lahan,
                    'luas_lahan_hektar' => $item->luas_lahan_hektar,
                    'tipe_rawa' => $item->tipe_rawa,
                    'alamat_detail' => $item->alamat_detail,
                    'kecamatan' => $item->nama_kecamatan,
                    'kelurahan' => $item->nama_kelurahan,
                    'center' => ['latitude' => $item->latitude, 'longitude' => $item->longitude]
                ]
            ];
        }

        return response()->json(['type' => 'FeatureCollection', 'features' => $features], 200);
    }

    /**
     * TAMBAH DATA: Simpan Poligon Lahan Baru via Fungsi Spasial MySQL
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'kecamatan_id' => 'required|integer',
            'kelurahan_id' => 'required|integer',
            'tipe_lahan_id' => 'required|integer',
            'nama_lahan' => 'required|string',
            'pemilik_lahan' => 'required|string',
            'luas_lahan_hektar' => 'required|numeric',
            'geojson' => 'required|string'
        ]);

        try {
            DB::table('lahan_sawah')->insert([
                'user_id' => $request->user_id,
                'kecamatan_id' => $request->kecamatan_id,
                'kelurahan_id' => $request->kelurahan_id,
                'tipe_lahan_id' => $request->tipe_lahan_id,
                'nama_lahan' => $request->nama_lahan,
                'pemilik_lahan' => $request->pemilik_lahan,
                'luas_lahan_hektar' => $request->luas_lahan_hektar,
                'tipe_rawa' => $request->tipe_rawa ?? null,
                'alamat_detail' => $request->alamat_detail ?? null,
                'latitude' => $request->latitude ?? null,
                'longitude' => $request->longitude ?? null,
                'polygon_area' => DB::raw("ST_GeomFromGeoJSON('" . $request->geojson . "')"),
                'created_at' => now(),
                'updated_at' => now()
            ]);
            return response()->json(['success' => true, 'message' => 'Lahan berhasil dipetakan.'], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menyimpan: ' . $e->getMessage()], 500);
        }
    }

    /**
     * UBAH DATA: Perbarui Informasi / Gambar Poligon Ulang
     */
    public function update(Request $request, $id)
    {
        $updateData = $request->except(['geojson']);
        $updateData['updated_at'] = now();

        if ($request->has('geojson') && !empty($request->geojson)) {
            $updateData['polygon_area'] = DB::raw("ST_GeomFromGeoJSON('" . $request->geojson . "')");
        }

        try {
            DB::table('lahan_sawah')->where('id', $id)->update($updateData);
            return response()->json(['success' => true, 'message' => 'Data spasial diperbarui.'], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal memperbarui: ' . $e->getMessage()], 500);
        }
    }

    /**
     * HAPUS DATA: Hapus Lahan beserta Poligonnya
     */
    public function destroy($id)
    {
        DB::table('lahan_sawah')->where('id', $id)->delete();
        return response()->json(['success' => true, 'message' => 'Lahan berhasil dihapus.'], 200);
    }
}