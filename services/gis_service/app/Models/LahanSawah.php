<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LahanSawah extends Model
{
    protected $table = 'lahan_sawah';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $guarded = [];

    public function siklus()
    {
        return $this->hasMany(SiklusTanam::class, 'lahan_id', 'id');
    }
}
