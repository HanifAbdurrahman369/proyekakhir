<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiklusTanam extends Model
{
    protected $table = 'tanam_padi';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'lahan_id',
        'luas_tanam_hektar',
        'bibit_id',
        'pemilik_id',
        'pupuk_id',
        'tanggal_tanam',
        'tanggal_pemupukan',
        'takaran_pupuk_kg',
        'pemupukan_dicatat_oleh',
        'pemupukan_dicatat_at',
        'estimasi_hari',
        'estimasi_tanggal_panen',
        'status_aktif',
        'status_verifikasi',
        'diverifikasi_oleh',
        'diverifikasi_at',
        'catatan_verifikasi',
    ];

    protected $casts = [
        'tanggal_tanam' => 'date',
        'estimasi_tanggal_panen' => 'date',
        'tanggal_pemupukan' => 'date',
        'luas_tanam_hektar' => 'float',
        'takaran_pupuk_kg' => 'float',
        'pemupukan_dicatat_at' => 'datetime',
        'diverifikasi_at' => 'datetime',
    ];

    public function lahan()
    {
        return $this->belongsTo(LahanSawah::class, 'lahan_id');
    }

    public function bibit()
    {
        return $this->belongsTo(JenisBibit::class, 'bibit_id');
    }

    public function pupuk()
    {
        return $this->belongsTo(JenisPupuk::class, 'pupuk_id');
    }



    public function panen()
    {
        return $this->hasOne(LaporPanen::class, 'tanam_padi_id');
    }
}
