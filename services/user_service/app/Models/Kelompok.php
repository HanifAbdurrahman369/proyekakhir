<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kelompok extends Model
{
    protected $table = 'komunitas';

    protected $fillable = [
        'nik',
        'jenis_komunitas',
        'nama',
        'nama_komunitas',
        'nomor_hp',
        'alamat',
        'status_keanggotaan',
        'komunitas_induk_id',
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'komunitas_id');
    }

    public function kelompokTaniInduk()
    {
        return $this->belongsTo(self::class, 'komunitas_induk_id');
    }

    public function anggotaBrigade()
    {
        return $this->hasMany(self::class, 'komunitas_induk_id');
    }
}
