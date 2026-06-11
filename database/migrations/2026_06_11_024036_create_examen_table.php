<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('examen', function (Blueprint $table) {
            $table->id();

            $table->foreignId('materia_id')
                ->constrained('materia')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('postulante_id')
                ->constrained('postulante')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->date('fecha_registro')->nullable();

            $table->decimal('promedio_final', 5, 2)->nullable();

            $table->enum('estado', [
                'pendiente',
                'aprobado',
                'reprobado',
            ])->default('pendiente');

            $table->timestamps();

            $table->unique([
                'materia_id',
                'postulante_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('examen');
    }
};