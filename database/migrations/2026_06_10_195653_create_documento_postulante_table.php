<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documento_postulante', function (Blueprint $table) {
            $table->id();

            $table->foreignId('postulante_id')
                ->constrained('postulante')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('nombre', 150);

            $table->enum('tipo', [
                'titulo_bachiller',
                'cedula_identidad',
                'boletin_sexto',
                'comprobante_pago',
                'otro',
            ]);

            $table->string('archivo', 255)->nullable();

            $table->text('otro')->nullable();

            $table->enum('estado', [
                'pendiente',
                'validado',
                'observado',
                'rechazado',
            ])->default('validado');

            $table->date('fecha_subida')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documento_postulante');
    }
};