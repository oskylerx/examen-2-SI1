<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asignacion_cupo', function (Blueprint $table) {
            $table->id();

            $table->foreignId('postulante_id')
                ->unique()
                ->constrained('postulante')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('carrera_id')
                ->constrained('carrera')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->date('fecha_asignacion')->nullable();

            $table->decimal('promedio_final', 5, 2)->nullable();

            $table->integer('posicion_ranking')->nullable();

            $table->enum('estado', [
                'asignado',
                'anulado',
            ])->default('asignado');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asignacion_cupo');
    }
};