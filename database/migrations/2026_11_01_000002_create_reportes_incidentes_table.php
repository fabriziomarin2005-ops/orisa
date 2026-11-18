<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reportes_incidentes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_incidente', 30)->unique();
            $table->foreignId('zona_id')->nullable()->constrained('zonas')->nullOnDelete();
            $table->string('lugar')->index();
            $table->string('titulo');
            $table->text('descripcion');
            $table->text('causas');
            $table->enum('nivel_riesgo', ['bajo', 'medio', 'alto', 'critico'])->default('bajo')->index();
            $table->json('soluciones');
            $table->dateTime('fecha')->index();
            $table->string('encargado');
            $table->string('foto_path')->nullable();
            $table->enum('estado', ['pendiente', 'en_revision', 'atendido'])->default('pendiente')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reportes_incidentes');
    }
};
