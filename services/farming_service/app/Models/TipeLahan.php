<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipeLahan extends Model
{
    use HasFactory;

    protected $table = 'tipe_lahan';

    protected $fillable = [
        'nama_tipe'
    ];

    public function lahanSawah()
    {
        return $this->hasMany(LahanSawah::class, 'tipe_lahan_id');
    }
}