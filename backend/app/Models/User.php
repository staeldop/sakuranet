<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'avatar',
        'balance',
        'pterodactyl_id',
        'ptero_password',
        // 🔥 ДОБАВЛЕНО: Поля для 2FA
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        // 🔥 ДОБАВЛЕНО: Скрываем секреты
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'balance' => 'decimal:2',
        // 🔥 ДОБАВЛЕНО: Каст даты
        'two_factor_confirmed_at' => 'datetime',
    ];

    public function services()
    {
        return $this->hasMany(Service::class);
    }
}