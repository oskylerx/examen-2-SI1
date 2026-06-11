<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('postulante_id')
                ->constrained('postulante')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('concepto', 100)->default('Pago CUP');

            $table->date('fecha_pago')->nullable();

            $table->decimal('monto', 8, 2)->default(700.00);

            $table->enum('estado', [
                'pendiente',
                'pagado',
                'observado',
                'rechazado',
            ])->default('aceptado');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pago');
    }
};