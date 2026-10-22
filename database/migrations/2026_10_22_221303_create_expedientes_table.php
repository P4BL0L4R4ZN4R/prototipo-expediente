<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expedientes', function (Blueprint $table) {
            $table->id();

            // Datos JSONB del parser
            $table->jsonb('ia');
            $table->jsonb('data');

            // Metadatos y secciones generadas por IA
            $table->jsonb('meta')->nullable();
            $table->jsonb('propuesta_ia')->nullable();
            $table->jsonb('entregable')->nullable();

            // Estado del expediente
            $table->string('estado_expediente', 50)->default('borrador');
            $table->string('status', 50)->default('active');

            // Datos manuales
            $table->string('direccion', 500)->nullable();

            // Fechas manuales (en $fillable y $casts del modelo)
            $table->date('fecha_propuesta')->nullable();
            $table->date('fecha_entregable')->nullable();

            // Timestamps con zona horaria
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expedientes');
    }
};