<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'user_id', 
        'product_id', 
        'name', 
        'identifier', 
        'ptero_server_id', // ID сервера в панели
        'ip_address', 
        'core',            // Название ядра (Egg #5)
        'status', 
        'price_monthly', 
        'expires_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'price_monthly' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}