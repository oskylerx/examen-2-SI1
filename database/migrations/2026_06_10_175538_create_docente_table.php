<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('docente', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->unique()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('coordinador_id')
                ->nullable()
                ->constrained('coordinador')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->string('profesion', 100);
            $table->string('especialidad', 100);
            $table->string('maestria', 150)->nullable();
            $table->string('diplomado', 150)->nullable();

            $table->enum('estado_validacion', [
                'pendiente',
                'aceptado',
                'rechazado',
            ])->default('pendiente');

            $table->text('observacion')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('docente');
    }
};
