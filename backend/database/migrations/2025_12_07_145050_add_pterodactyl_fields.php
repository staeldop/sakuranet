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
        // 1. Добавляем ID птеродактиля пользователю
        Schema::table('users', function (Blueprint $table) {
            // Проверяем, чтобы не дублировать, если вдруг уже запускал
            if (!Schema::hasColumn('users', 'pterodactyl_id')) {
                $table->integer('pterodactyl_id')->nullable()->after('id');
            }
        });

        // 2. Добавляем настройки ресурсов для товаров (какой сервер выдавать)
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'ptero_egg_id')) {
                $table->integer('ptero_egg_id')->nullable(); // ID Яйца (например Minecraft)
                $table->integer('ptero_nest_id')->nullable(); // ID Гнезда
                $table->integer('memory')->default(1024); // МБ
                $table->integer('disk')->default(5000);   // МБ
                $table->integer('cpu')->default(100);     // %
                $table->integer('databases')->default(0);
                $table->integer('backups')->default(0);
                $table->integer('allocations')->default(0);
            }
        });
        
        // 3. Добавляем ID сервера в услуги
        Schema::table('services', function (Blueprint $table) {
            if (!Schema::hasColumn('services', 'ptero_server_id')) {
                $table->integer('ptero_server_id')->nullable()->after('identifier');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('pterodactyl_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'ptero_egg_id', 
                'ptero_nest_id', 
                'memory', 
                'disk', 
                'cpu', 
                'databases', 
                'backups', 
                'allocations'
            ]);
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('ptero_server_id');
        });
    }
};