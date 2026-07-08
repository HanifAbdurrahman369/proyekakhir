<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LaporPanen extends Model
{
    protected $table = 'panen_padi';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'tanam_padi_id',
        'lahan_id',
        'bibit_id',
        'pemilik_id',
        'diverifikasi_oleh',
        'nama_lahan',
        'nama_bibit',
        'varietas',
        'tanggal_tanam',
        'tanggal_panen',
        'hasil_panen_ton',
        'luas_lahan_ha',
        'luas_tanam_hektar',
        'produktivitas_ton_ha',
        'status_verifikasi',
        'catatan_verifikasi',
        'diverifikasi_at',
    ];

    protected $casts = [
        'hasil_panen_ton' => 'float',
        'luas_lahan_ha' => 'float',
        'luas_tanam_hektar' => 'float',
        'produktivitas_ton_ha' => 'float',
        'tanggal_tanam' => 'date',
        'tanggal_panen' => 'date',
        'diverifikasi_at' => 'datetime',
    ];

    public function siklusTanam()
    {
        return $this->belongsTo(SiklusTanam::class, 'tanam_padi_id');
    }
}
