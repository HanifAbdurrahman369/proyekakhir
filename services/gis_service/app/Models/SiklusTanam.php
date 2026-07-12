<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiklusTanam extends Model
{
protected $table = 'tanam_padi';
public function panen() {
    return $this->hasOne(HasilPanen::class, 'tanam_padi_id', 'id');
}
}
