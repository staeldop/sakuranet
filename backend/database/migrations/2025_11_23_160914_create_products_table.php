<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category'); // gaming, virtual, dedicated
            $table->string('country')->nullable(); // RU, DE, FI
            $table->string('game_type')->nullable(); // gaming, coding
            $table->decimal('price', 10, 2);
            $table->json('attributes')->nullable(); // Здесь будем хранить характеристики (CPU, RAM и т.д.)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};