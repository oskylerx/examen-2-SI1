<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asignacion_academica', function (Blueprint $table) {
            $table->id();

            $table->foreignId('horario_id')
                ->constrained('horario')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('aula_id')
                ->constrained('aula')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('grupo_id')
                ->constrained('grupo')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('docente_id')
                ->constrained('docente')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('materia_id')
                ->constrained('materia')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('gestion', 20);

            $table->date('fecha_asignacion')->nullable();

            $table->enum('estado', [
                'activa',
                'inactiva',
                'finalizada',
                'cancelada',
            ])->default('activa');

            $table->timestamps();

            $table->unique([
                'grupo_id',
                'materia_id',
            ]);

            $table->unique([
                'docente_id',
                'horario_id',
                'gestion',
            ]);

            $table->unique([
                'aula_id',
                'horario_id',
                'gestion',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asignacion_academica');
    }
};