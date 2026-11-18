<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reportes_incidentes', function (Blueprint $table) {
            if (!Schema::hasColumn('reportes_incidentes', 'observaciones_cierre')) {
                $table->text('observaciones_cierre')->nullable()->after('estado');
            }
            if (!Schema::hasColumn('reportes_incidentes', 'revisado_en')) {
                $table->timestamp('revisado_en')->nullable()->after('observaciones_cierre');
            }
            if (!Schema::hasColumn('reportes_incidentes', 'atendido_en')) {
                $table->timestamp('atendido_en')->nullable()->after('revisado_en');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reportes_incidentes', function (Blueprint $table) {
            $table->dropColumn(['observaciones_cierre', 'revisado_en', 'atendido_en']);
        });
    }
};
