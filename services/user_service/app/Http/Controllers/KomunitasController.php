<?php

namespace App\Http\Controllers;

use App\Models\Kelompok;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class KomunitasController extends Controller
{
    /**
     * Display a listing of the komunitas.
     */
    public function index(Request $request)
    {
        $query = Kelompok::query();

        if ($request->has('jenis_komunitas') && $request->jenis_komunitas) {
            $query->where('jenis_komunitas', $request->jenis_komunitas);
        }

        if ($request->has('komunitas_induk_id')) {
            $query->where('komunitas_induk_id', $request->komunitas_induk_id);
        }

        $komunitas = $query->with('kelompokTaniInduk')->get();

        return response()->json([
            'success' => true,
            'data' => $komunitas
        ]);
    }

    /**
     * Store a newly created komunitas in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nik' => 'nullable|string|max:20|unique:komunitas,nik',
            'jenis_komunitas' => 'required|string|max:30|in:kelompok_tani,brigade_pangan',
            'nama' => 'required|string|max:150',
            'nama_komunitas' => 'nullable|string|max:150',
            'nomor_hp' => 'nullable|string|max:20',
            'alamat' => 'nullable|string',
            'komunitas_induk_id' => 'nullable|exists:komunitas,id',
            'wilayah_kecamatan_id' => 'nullable|integer',
            'wilayah_kelurahan_ids' => 'nullable|array',
            'instansi_asal' => 'nullable|string|max:100',
            'nama_bpp' => 'nullable|string|max:150',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $komunitas = Kelompok::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Data komunitas berhasil ditambahkan.',
            'data' => $komunitas
        ], 201);
    }

    /**
     * Update the specified komunitas in storage.
     */
    public function update(Request $request, $id)
    {
        $komunitas = Kelompok::find($id);

        if (!$komunitas) {
            return response()->json([
                'success' => false,
                'message' => 'Data komunitas tidak ditemukan.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nik' => 'nullable|string|max:20|unique:komunitas,nik,' . $id,
            'jenis_komunitas' => 'sometimes|string|max:30|in:kelompok_tani,brigade_pangan',
            'nama' => 'sometimes|string|max:150',
            'nama_komunitas' => 'nullable|string|max:150',
            'nomor_hp' => 'nullable|string|max:20',
            'alamat' => 'nullable|string',
            'komunitas_induk_id' => 'nullable|exists:komunitas,id',
            'status_keanggotaan' => 'sometimes|string|in:AKTIF,TIDAK_AKTIF',
            'wilayah_kecamatan_id' => 'nullable|integer',
            'wilayah_kelurahan_ids' => 'nullable|array',
            'instansi_asal' => 'nullable|string|max:100',
            'nama_bpp' => 'nullable|string|max:150',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $komunitas->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Data komunitas berhasil diperbarui.',
            'data' => $komunitas
        ]);
    }

    /**
     * Remove the specified komunitas from storage.
     */
    public function destroy($id)
    {
        $komunitas = Kelompok::find($id);

        if (!$komunitas) {
            return response()->json([
                'success' => false,
                'message' => 'Data komunitas tidak ditemukan.'
            ], 404);
        }

        // Check for users linked to this komunitas
        if ($komunitas->users()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat menghapus komunitas karena ada akun pengguna yang terdaftar pada komunitas ini.'
            ], 422);
        }

        $komunitas->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data komunitas berhasil dihapus.'
        ]);
    }
}
