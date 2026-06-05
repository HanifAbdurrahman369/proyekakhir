<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Camat extends Model
{
    use HasFactory;

    protected $table = 'kecamatan';

    protected $fillable = [
        'nama_kecamatan'
    ];

    public function lahanSawah()
    {
        return $this->hasMany(LahanSawah::class, 'kecamatan_id');
    }
}