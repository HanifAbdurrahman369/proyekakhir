<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kecamatan extends Model
{
    protected $table = 'kecamatan'; // Nama tabel di SQL 
protected $primaryKey = 'Ide'; // Primary key unik di SQL Anda 
public function kelurahan() {
    return $this->hasMany(Kelurahan::class, 'kecamatan_id', 'Ide');
}
}
