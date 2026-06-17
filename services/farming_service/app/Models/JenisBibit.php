<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JenisBibit extends Model
{
    use HasFactory;

    protected $table = 'jenis_bibit';

    protected $fillable = [
        'nama_bibit',
        'deskripsi'
    ];

    public function siklusTanam()
    {
        return $this->hasMany(SiklusTanam::class, 'bibit_id');
    }
}