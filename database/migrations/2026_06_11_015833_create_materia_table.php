<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materia', function (Blueprint $table) {
            $table->id();

            $table->string('nombre', 150);

            $table->decimal('porcentaje_p1', 5, 2)->default(0);
            $table->decimal('porcentaje_p2', 5, 2)->default(0);
            $table->decimal('porcentaje_ef', 5, 2)->default(0);

            $table->decimal('nota_min_aprob', 5, 2)->default(51);

            $table->boolean('activo')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materia');
    }
};