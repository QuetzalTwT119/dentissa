<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('solicitudes_urgencia');
        Schema::dropIfExists('lista_espera');
    }

    public function down(): void
    {
        // Deliberately empty: this migration removes an obsolete feature and
        // its operational data; restoring it requires a backup, not a schema
        // recreation without the original records.
    }
};
