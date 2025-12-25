<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'country',
        'game_type',
        'price',
        'specs', 
        
        // 🔥 Настройки Pterodactyl
        'ptero_nest_id',      // ID Гнезда
        'ptero_egg_id',       // ID Яйца
        'ptero_docker_image', // Docker Image
        'ptero_startup',      // Startup Command
        
        // Лимиты ресурсов
        'memory',             // ОЗУ (MB)
        'disk',               // Диск (MB)
        'cpu',                // Процессор (%)
        'databases',          // Лимит баз данных
        'backups',            // Лимит бэкапов
        'allocations'         // Лимит портов
    ];

    protected $casts = [
        'specs' => 'array',
        'price' => 'decimal:2',
    ];
}