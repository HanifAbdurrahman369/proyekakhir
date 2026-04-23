<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * Nama tabel (opsional jika sudah 'users')
     */
    protected $table = 'users';

    /**
     * Primary key (default 'id', jadi opsional)
     */
    protected $primaryKey = 'id';

    /**
     * Field yang boleh diisi (mass assignment)
     */
    protected $fillable = [
        'role_id',
        'nama_lengkap',
        'email',
        'password',
        'no_hp',
        'alamat',
    ];

    /**
     * Field yang disembunyikan saat response JSON
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Casting tipe data
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}