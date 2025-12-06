<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    // 🔥 ИЗМЕНИЛ ЗДЕСЬ: заменил department на priority
    protected $fillable = ['user_id', 'subject', 'priority', 'status'];

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
