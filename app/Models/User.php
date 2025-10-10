<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // 🔹 Importa HasApiTokens

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens; // 🔹 Agrega HasApiTokens aquí

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            // 'password' => 'hashed', // 🔹 opcional, si usas md5 puedes comentar
        ];
    }
}
