<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiklusTanam extends Model
{
protected $table = 'siklus_tanam';
public function panen() {
    return $this->hasOne(HasilPanen::class, 'siklus_tanam_id', 'id');
}
}
