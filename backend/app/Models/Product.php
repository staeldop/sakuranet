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
        'specs', // 🔥 ПЕРЕИМЕНОВАЛИ (было attributes)
    ];

    protected $casts = [
        'specs' => 'array', // 🔥 ПЕРЕИМЕНОВАЛИ
        'price' => 'decimal:2',
    ];
}