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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            
            // ID пользователя, которому пришло уведомление
            // constrained() проверяет, что такой юзер существует
            // onDelete('cascade') удалит уведомления, если удалить юзера
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); 
            
            $table->string('title'); // Заголовок (например: "Пополнение баланса")
            $table->text('message'); // Текст сообщения
            
            // Тип уведомления (влияет на иконку и цвет)
            // По умолчанию 'info'
            $table->enum('type', ['info', 'success', 'warning', 'error'])->default('info');
            
            // Прочитано или нет (по умолчанию нет)
            $table->boolean('is_read')->default(false);
            
            $table->timestamps(); // created_at и updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
