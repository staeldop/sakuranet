<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketMessage extends Model
{
    use HasFactory;

    // 🔥 Добавили 'is_support' в разрешенные поля
    protected $fillable = [
        'ticket_id', 
        'user_id', 
        'message', 
        'is_support' 
    ];

    // Автоматическая конвертация 0/1 в false/true
    protected $casts = [
        'is_support' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
}