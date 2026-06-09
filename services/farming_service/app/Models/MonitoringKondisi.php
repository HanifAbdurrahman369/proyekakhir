<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonitoringKondisi extends Model
{
    protected $table = 'monitoring_kondisi';

    public $timestamps = false;

    protected $fillable = [
        'lahan_id',
        'tanggal_cek',
        'ph_air',
        'tinggi_muka_air',
        'status_air',
        'kekeruhan_air',
        'catatan_petugas',
        'latitude',
        'longitude',
        'created_by',
    ];

    public function lahan()
    {
        return $this->belongsTo(LahanSawah::class, 'lahan_id');
    }
}