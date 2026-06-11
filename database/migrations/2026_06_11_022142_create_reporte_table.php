<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reporte', function (Blueprint $table) {
            $table->id();

            $table->foreignId('grupo_id')
                ->constrained('grupo')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('coordinador_id')
                ->constrained('coordinador')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->date('fecha_reporte');

            $table->string('descripcion', 200);

            $table->text('observaciones')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reporte');
    }
};