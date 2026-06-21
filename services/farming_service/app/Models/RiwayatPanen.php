<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiwayatPanen extends Model
{
    protected $table = 'panen_padi';

    protected $fillable = [
        'tanam_padi_id',
        'lahan_id',
        'bibit_id',
        'pemilik_id',
        'petani_id',
        'diverifikasi_oleh',
        'nama_lahan',
        'nama_bibit',
        'varietas',
        'tanggal_tanam',
        'tanggal_panen',
        'hasil_panen_ton',
        'luas_lahan_ha',
        'produktivitas_ton_ha',
        'status_verifikasi',
        'diverifikasi_at',
    ];

    protected $casts = [
        'tanggal_tanam' => 'date',
        'tanggal_panen' => 'date',
        'hasil_panen_ton' => 'float',
        'luas_lahan_ha' => 'float',
        'produktivitas_ton_ha' => 'float',
        'diverifikasi_at' => 'datetime',
    ];

    public function lahan()
    {
        return $this->belongsTo(LahanSawah::class, 'lahan_id');
    }

    public function siklusTanam()
    {
        return $this->belongsTo(SiklusTanam::class, 'tanam_padi_id');
    }

    public function bibit()
    {
        return $this->belongsTo(JenisBibit::class, 'bibit_id');
    }

}
