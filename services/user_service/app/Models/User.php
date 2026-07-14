<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * Atribut yang dapat diisi secara massal (Mass Assignment).
     */
    protected $fillable = [
        'nik',
        'nip',
        'role_id',
        'komunitas_id',
        'nama_lengkap',
        'email',
        'password',
        'no_hp',
        'alamat',
    ];

    /**
     * Atribut yang harus disembunyikan saat serialisasi (JSON).
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Konversi tipe data otomatis.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Kustomisasi pengiriman email reset password.
     */
     public function sendPasswordResetNotification($token)
     {
         $baseUrl = env('WEB_APP_URL', 'http://127.0.0.1:8080');
         $url = rtrim($baseUrl, '/') . '/reset-password/' . $token . '?email=' . urlencode($this->email);

         $this->notify(
             new ResetPasswordNotification($url)
         );
     }

     public function komunitas()
     {
         return $this->belongsTo(Kelompok::class, 'komunitas_id');
     }
}
