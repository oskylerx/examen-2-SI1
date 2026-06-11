<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calificacion', function (Blueprint $table) {
            $table->id();

            $table->foreignId('examen_id')
                ->constrained('examen')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->enum('tipo', [
                'p1',
                'p2',
                'ef',
            ]);

            $table->decimal('nota', 5, 2);

            $table->timestamps();

            $table->unique([
                'examen_id',
                'tipo',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calificacion');
    }
};