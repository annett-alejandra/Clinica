<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estados_cita', function (Blueprint $table) {
            // IDs oficiales explícitos, cargados por EstadosCitaSeeder.
            $table->unsignedBigInteger('id_estado_cita')->primary();
            $table->string('nombre_estado', 100)->unique();
            $table->text('descripcion')->nullable();
        });

        // La exclusión de citas depende de los IDs 1 y 2. No se pueden reasignar sus significados.
        DB::statement(<<<'SQL'
            ALTER TABLE estados_cita
            ADD CONSTRAINT estados_cita_catalogo_oficial CHECK (
                (id_estado_cita, nombre_estado) IN (
                    (1, 'Programada'),
                    (2, 'Confirmada'),
                    (3, 'Atendida'),
                    (4, 'Cancelada'),
                    (5, 'Inasistencia')
                )
            )
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('estados_cita');
    }
};
