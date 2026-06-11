<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiklusTanam extends Model
{
    protected $table = 'siklus_tanam';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'lahan_id',
        'bibit_id',
        'tanggal_tanam',
        'estimasi_panen',
        'status_aktif',
        'tanggal_panen',
        'hasil_panen',
        'status_verifikasi',
        'created_by',
        'verified_by',
        'verified_at',
        'catatan_verifikasi',
    ];

    protected $casts = [
        'hasil_panen' => 'float',
        'tanggal_tanam' => 'date',
        'tanggal_panen' => 'date',
        'verified_at' => 'datetime',
    ];

    public function lahan()
    {
        return $this->belongsTo(LahanSawah::class, 'lahan_id');
    }

    public function petani()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}