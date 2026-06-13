<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LahanSawah extends Model
{
    protected $table = 'lahan_sawah';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'kecamatan_id',
        'kelurahan_id',
        'tipe_lahan_id',
        'tipe_rawa',
        'nama_lahan',
        'pemilik_lahan',
        'tahun_lbs',
        'luas_lahan_hektar',
        'hasil_panen_ton',
        'produktivitas_ton_ha',
        'alamat_detail',
        'koordinat_tengah',
        'latitude',
        'longitude',
        'foto_lahan',
        'status_verifikasi',
        'alasan_penolakan',
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
}
