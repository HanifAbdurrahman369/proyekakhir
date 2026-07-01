<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatistikPadiKecamatan extends Model
{
    protected $table = 'statistik_padi_kecamatan';

    protected $fillable = [
        'kecamatan_id',
        'tahun',
        'luas_tanam_ha',
        'luas_panen_ha',
        'produktivitas_kw_ha',
        'produktivitas_ton_ha',
        'produksi_ton',
        'is_sementara',
        'sumber_data'
    ];

    public function kecamatan()
    {
        return $this->belongsTo(Kecamatan::class, 'kecamatan_id');
    }
}
