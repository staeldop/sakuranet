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
        Schema::table('tickets', function (Blueprint $table) {
            // 1. Удаляем старую колонку department
            $table->dropColumn('department');

            // 2. Добавляем новую колонку priority
            // Используем enum для ограничения значений
            // after('subject') ставит колонку после темы тикета (для красоты в БД)
            $table->enum('priority', ['low', 'medium', 'high'])
                  ->default('low')
                  ->after('subject');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Возвращаем всё как было, если нужно отменить миграцию
            $table->dropColumn('priority');
            $table->string('department')->default('tech');
        });
    }
};

