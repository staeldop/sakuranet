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
        'attributes'
    ];

    // Автоматически превращает JSON из базы в массив PHP и наоборот
    protected $casts = [
        'attributes' => 'array',
        'price' => 'decimal:2'
    ];
}