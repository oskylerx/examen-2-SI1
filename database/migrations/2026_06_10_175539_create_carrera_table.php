<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carrera', function (Blueprint $table) {
            $table->id();

            $table->string('codigo_carrera', 20)->unique();

            $table->string('nombre', 150);

            $table->text('descripcion')->nullable();

            $table->integer('cupos')->default(0);

            $table->decimal('nota_min_ingreso', 5, 2)->default(60.00);

            $table->enum('estado', [
                'activa',
                'inactiva',
            ])->default('activa');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carrera');
    }
};