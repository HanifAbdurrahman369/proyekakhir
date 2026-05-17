<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiklusTanam extends Model
{
    use HasFactory;
    
    protected $table = 'siklus_tanam';

    protected $fillable = [
        'lahan_id',
        'bibit_id',
        'tanggal_tanam',
        'estimasi_panen',
        'status_aktif',
        'tanggal_panen',
        'hasil_panen',
        'status_verifikasi',
        'created_by'
    ];

    public function bibit()
    {
        return $this->belongsTo(JenisBibit::class, 'bibit_id');
    }

    public function lahan()
    {
        return $this->belongsTo(LahanSawah::class, 'lahan_id');
    }
}
