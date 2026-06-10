<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('postulante', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->unique()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->date('fecha_nacimiento')->nullable();

            $table->enum('genero', [
                'masculino',
                'femenino',
                'otro'
            ])->nullable();

            $table->string('direccion', 200)->nullable();
            $table->string('colegio', 150)->nullable();
            $table->string('ciudad', 100)->nullable();

            $table->boolean('titulo_bachiller')->default(false);
            $table->text('otros_documentos')->nullable();

            $table->date('fecha_registro')->nullable();

            $table->enum('estado_inscripcion', [
                'pendiente',
                'observado',
                'aceptado',
                'rechazado'
            ])->default('pendiente');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('postulante');
    }
};