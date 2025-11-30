<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    // 🔥 ВОТ ЭТОЙ СТРОКИ НЕ ХВАТАЛО
    protected $fillable = ['user_id', 'subject', 'department', 'status'];

    public function messages()
    {
        return $this->hasMany(TicketMessage::class);
    }

    public function latestMessage()
    {
        return $this->hasOne(TicketMessage::class)->latestOfMany();
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}