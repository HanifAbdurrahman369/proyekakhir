<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LahanSawah extends Model
{
    use HasFactory;

    protected $table = 'lahan_sawah';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'kecamatan_id',
        'kelurahan_id',
        'tipe_lahan_id',
        'nama_lahan',
        'pemilik_lahan',
        'tipe_rawa',
        'tahun_lbs',
        'luas_lahan_hektar',
        'hasil_panen_ton',
        'produktivitas_ton_ha',
        'alamat_detail',
        'koordinat_tengah',
        'polygon_area',
        'latitude',
        'longitude',
        'foto_lahan',
        'status_verifikasi'
    ];

    public function siklusTanam()
    {
        return $this->hasMany(SiklusTanam::class, 'lahan_id');
    }

    public function kecamatanLahan()
    {
        return $this->belongsTo(Kecamatan::class, 'kecamatan_id');
    }

    public function kelurahan()
    {
        return $this->belongsTo(Kelurahan::class, 'kelurahan_id');
    }

    public function tipeLahan()
    {
        return $this->belongsTo(TipeLahan::class, 'tipe_lahan_id');
    }
}