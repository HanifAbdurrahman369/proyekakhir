<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kecamatan extends Model
{
    protected $table = 'kecamatan';

    protected $primaryKey = 'id';

    protected $fillable = [
        'nama_kecamatan',
        'produktivitas',
        'produksi',
        'luas_tanam_ha',
        'luas_panen_ha',
        'tahun_data_padi',
        'sumber_data_padi',
        'polygon_geojson',
    ];

    public function kelurahan()
    {
        return $this->hasMany(Kelurahan::class, 'kecamatan_id', 'id');
    }

    public function statistikPadi()
    {
        return $this->hasMany(StatistikPadiKecamatan::class, 'kecamatan_id', 'id');
    }
}
