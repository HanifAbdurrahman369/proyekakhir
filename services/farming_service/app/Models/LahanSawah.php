<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LahanSawah extends Model
{
    use HasFactory;

    protected $table = 'lahan_sawah';

    protected $fillable = [
        'nama_lahan',
        'pemilik_lahan'
 
    ];

    public function siklusTanam()
    {
        return $this->hasMany(SiklusTanam::class, 'lahan_id');
    }
}