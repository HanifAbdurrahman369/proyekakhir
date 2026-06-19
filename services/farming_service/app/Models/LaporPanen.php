<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LaporPanen extends Model
{
    protected $table = 'lapor_panen';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'siklus_tanam_id',
        'tanggal_panen',
        'hasil_panen',
        'estimasi_panen',
        'status_verifikasi',
        'catatan_verifikasi',
        'created_by',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'hasil_panen' => 'float',
        'tanggal_panen' => 'date',
        'verified_at' => 'datetime',
    ];

    public function siklusTanam()
    {
        return $this->belongsTo(SiklusTanam::class, 'siklus_tanam_id');
    }
}
