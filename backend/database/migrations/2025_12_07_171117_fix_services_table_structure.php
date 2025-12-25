<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            // Делаем поле core необязательным, чтобы ошибки не ломали покупку
            if (Schema::hasColumn('services', 'core')) {
                $table->string('core')->nullable()->change();
            }
            
            // Убеждаемся, что поле для ID сервера существует
            if (!Schema::hasColumn('services', 'ptero_server_id')) {
                $table->integer('ptero_server_id')->nullable()->after('identifier');
            }
        });
    }

    public function down(): void
    {
        // Необязательно
    }
};