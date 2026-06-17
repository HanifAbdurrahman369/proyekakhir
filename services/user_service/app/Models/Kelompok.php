<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kelompok extends Model
{
    protected $table = 'kelompok';

    protected $fillable = [
        'brigade_pangan',
        'kelompok_tani',
        'nama',
        'nomor_hp',
        'alamat',
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'kelompok_id');
    }
}
