<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kelurahan extends Model
{
  protected $table = 'kelurahan';
public function lahan() {
    return $this->hasMany(LahanSawah::class, 'kelurahan_id', 'id');
}
}
