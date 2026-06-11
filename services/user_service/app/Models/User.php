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
        'role_id',
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
         // Menggunakan 127.0.0.1 agar lebih stabil antar-servis di Laragon
         $url = 'http://127.0.0.1:8080/reset-password/' . $token . '?email=' . urlencode($this->email);

         $this->notify(
             new ResetPasswordNotification($url)
         );
     }
}