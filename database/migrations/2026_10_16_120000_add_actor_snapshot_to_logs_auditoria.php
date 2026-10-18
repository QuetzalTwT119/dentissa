<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logs_auditoria', function (Blueprint $table) {
            $table->unsignedBigInteger('actor_user_id')->nullable()->after('id_usuario');
            $table->string('actor_nombre', 201)->nullable()->after('actor_user_id');
            $table->string('actor_rol', 30)->nullable()->after('actor_nombre');
        });
    }

    public function down(): void
    {
        Schema::table('logs_auditoria', function (Blueprint $table) {
            $table->dropColumn(['actor_user_id', 'actor_nombre', 'actor_rol']);
        });
    }
};
