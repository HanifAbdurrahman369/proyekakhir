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
        'petani_id',
        'nama_lahan',
        'nama_bibit',
        'varietas',
        'tanggal_tanam',
        'diverifikasi_oleh',
        'tanggal_panen',
        'hasil_panen_ton',
        'status_verifikasi',
        'catatan_verifikasi',
        'diverifikasi_at',
    ];

    protected $casts = [
        'hasil_panen_ton' => 'float',
        'tanggal_panen' => 'date',
        'diverifikasi_at' => 'datetime',
    ];

    public function siklusTanam()
    {
        return $this->belongsTo(SiklusTanam::class, 'tanam_padi_id');
    }
}
