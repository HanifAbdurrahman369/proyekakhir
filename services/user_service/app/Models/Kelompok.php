<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kelompok extends Model
{
    protected $table = 'kelompok';

    protected $fillable = [
        'kode_anggota',
        'nik',
        'jenis_kelompok',
        'nama',
        'nama_kelompok',
        'nomor_hp',
        'alamat',
        'status_keanggotaan',
        'kelompok_tani_induk_id',
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'kelompok_id');
    }

    public function kelompokTaniInduk()
    {
        return $this->belongsTo(self::class, 'kelompok_tani_induk_id');
    }

    public function anggotaBrigade()
    {
        return $this->hasMany(self::class, 'kelompok_tani_induk_id');
    }
}
