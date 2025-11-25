<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            
            $table->string('name'); // Название сервера (my-server)
            $table->string('identifier')->unique(); // Уникальный ID (srv-xxxxx)
            $table->string('ip_address')->nullable();
            
            $table->string('core'); // Ядро (Minecraft Vanilla 1.20)
            $table->string('status')->default('active'); // active, suspended, cancelled
            
            $table->integer('price_monthly'); // Зафиксированная цена при покупке
            $table->timestamp('expires_at'); // Дата окончания
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('services');
    }
};