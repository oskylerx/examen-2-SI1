<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE asignacion_cupo ALTER COLUMN carrera_id DROP NOT NULL");

        DB::statement("ALTER TABLE asignacion_cupo DROP CONSTRAINT IF EXISTS asignacion_cupo_estado_check");

        DB::statement("UPDATE asignacion_cupo SET estado = 'primera_opcion' WHERE estado = 'asignado'");

        DB::statement("ALTER TABLE asignacion_cupo ALTER COLUMN estado SET DEFAULT 'primera_opcion'");

        DB::statement("
            ALTER TABLE asignacion_cupo
            ADD CONSTRAINT asignacion_cupo_estado_check
            CHECK (estado IN (
                'primera_opcion',
                'segunda_opcion',
                'aprobado_sin_cupo',
                'reprobado',
                'anulado'
            ))
        ");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE asignacion_cupo DROP CONSTRAINT IF EXISTS asignacion_cupo_estado_check");

        DB::statement("
            UPDATE asignacion_cupo
            SET estado = 'asignado'
            WHERE estado IN (
                'primera_opcion',
                'segunda_opcion',
                'aprobado_sin_cupo',
                'reprobado'
            )
        ");

        DB::statement("ALTER TABLE asignacion_cupo ALTER COLUMN estado SET DEFAULT 'asignado'");

        DB::statement("
            ALTER TABLE asignacion_cupo
            ADD CONSTRAINT asignacion_cupo_estado_check
            CHECK (estado IN ('asignado', 'anulado'))
        ");

        DB::statement("ALTER TABLE asignacion_cupo ALTER COLUMN carrera_id SET NOT NULL");
    }
};