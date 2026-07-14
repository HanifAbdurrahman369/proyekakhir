<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LahanSawah extends Model
{
    protected $table = 'lahan_sawah';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'pemilik_id',
        'assigned_petugas_id',
        'kecamatan_id',
        'kelurahan_id',
        'tipe_lahan_id',
        'nama_lahan',
        'tahun_lbs',
        'luas_lahan_hektar',
        'luas_tanam_hektar',
        'hasil_panen_ton',
        'panen_terakhir_id',
        'produktivitas_ton_ha',
        'alamat_detail',
        'koordinat_tengah',
        'latitude',
        'longitude',
        'status_verifikasi',
        'alasan_penolakan',
        'status_spasial',
        'verified_by',
        'verified_at',
        'catatan_verifikasi',
        'created_at',
        'updated_at',
    ];

    protected $hidden = [
        'polygon_area',
    ];

    public function kecamatanLahan()
    {
        return $this->belongsTo(\App\Models\Kecamatan::class, 'kecamatan_id');
    }

    public function kelurahanLahan()
    {
        return $this->belongsTo(\App\Models\Kelurahan::class, 'kelurahan_id');
    }

    public function riwayatPanen()
    {
        return $this->hasMany(RiwayatPanen::class, 'lahan_id');
    }

    public function riwayatPanenTerakhir()
    {
        return $this->belongsTo(RiwayatPanen::class, 'panen_terakhir_id');
    }

    public function pemilik()
    {
        return $this->belongsTo(User::class, 'pemilik_id');
    }


}
