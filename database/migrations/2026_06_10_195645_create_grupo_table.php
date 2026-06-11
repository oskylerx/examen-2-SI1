<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grupo', function (Blueprint $table) {
            $table->id();

            $table->foreignId('docente_id')
                ->nullable()
                ->constrained('docente')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('coordinador_id')
                ->nullable()
                ->constrained('coordinador')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('carrera_id')
                ->constrained('carrera')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('nombre', 100);

            $table->string('gestion', 20);

            $table->integer('cupos_maximo');

            $table->boolean('activo')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grupo');
    }
};