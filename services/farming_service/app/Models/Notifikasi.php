<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notifikasi extends Model
{
    protected $table = 'notifikasi';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'role_id_penerima',
        'user_id_penerima',
        'judul',
        'pesan',
        'ref_type',
        'ref_id',
        'target_url',
        'is_read',
    ];
}