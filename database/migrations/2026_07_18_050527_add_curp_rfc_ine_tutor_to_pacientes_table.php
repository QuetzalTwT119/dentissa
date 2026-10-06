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
        Schema::table('pacientes', function (Blueprint $table) {
            $table->char('curp', 18)->nullable()->unique()->after('numero_seguro');
            $table->string('rfc', 13)->nullable()->unique()->after('curp');
            $table->string('ine_clave', 18)->nullable()->unique()->after('rfc');
            $table->foreignId('tutor_id')->nullable()->after('ine_clave')
                ->constrained('pacientes')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pacientes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tutor_id');
            $table->dropColumn(['curp', 'rfc', 'ine_clave']);
        });
    }
};
