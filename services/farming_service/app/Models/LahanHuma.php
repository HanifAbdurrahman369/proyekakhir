<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LahanHuma extends Model
{
    use HasFactory;

    protected $table = 'lahan_huma';

    protected $guarded = ['id'];
}
