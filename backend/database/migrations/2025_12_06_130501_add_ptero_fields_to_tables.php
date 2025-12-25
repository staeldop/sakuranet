<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Добавляем ID птеродактиля пользователю
        Schema::table('users', function (Blueprint $table) {
            $table->integer('pterodactyl_id')->nullable()->after('id');
        });

        // 2. Добавляем настройки сервера в товары
        Schema::table('products', function (Blueprint $table) {
            // ID Гнезда и Яйца (например, Minecraft -> Paper)
            $table->integer('ptero_nest_id')->nullable();
            $table->integer('ptero_egg_id')->nullable();
            
            // Технические параметры
            $table->string('ptero_docker_image')->nullable()->default('ghcr.io/pterodactyl/yolks:java_17');
            $table->string('ptero_startup')->nullable()->default('java -Xms128M -Xmx{{SERVER_MEMORY}}M -jar server.jar');
            
            // Лимиты ресурсов (переопределяют specs, если нужно, или используются как основные)
            $table->integer('memory_mb')->default(1024);
            $table->integer('disk_mb')->default(5120);
            $table->integer('cpu_limit')->default(100);
            $table->integer('databases')->default(0);
            $table->integer('backups')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('pterodactyl_id');
        });
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'ptero_nest_id', 'ptero_egg_id', 'ptero_docker_image', 
                'ptero_startup', 'memory_mb', 'disk_mb', 'cpu_limit', 
                'databases', 'backups'
            ]);
        });
    }
};