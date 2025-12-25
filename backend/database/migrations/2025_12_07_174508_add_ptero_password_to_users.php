<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Поле для хранения пароля от панели (в открытом виде, по твоей просьбе)
            if (!Schema::hasColumn('users', 'ptero_password')) {
                $table->string('ptero_password')->nullable()->after('pterodactyl_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('ptero_password');
        });
    }
};