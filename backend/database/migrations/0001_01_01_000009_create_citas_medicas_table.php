<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citas_medicas', function (Blueprint $table) {
            $table->bigIncrements('id_cita');
            $table->uuid('codigo_unico')->unique();
            $table->unsignedBigInteger('id_paciente');
            $table->unsignedBigInteger('id_medico');
            $table->unsignedBigInteger('id_especialidad');
            $table->unsignedBigInteger('id_estado_cita');
            $table->date('fecha_cita');
            $table->time('hora_cita');
            $table->unsignedSmallInteger('duracion_minutos');
            $table->timestampTz('fecha_creacion')->useCurrent();
            $table->timestampTz('fecha_hora_llegada')->nullable();
            $table->text('motivo')->nullable();
            $table->text('motivo_cancelacion')->nullable();
            $table->text('observacion_atencion')->nullable();

            $table->foreign('id_paciente')
                ->references('id_paciente')
                ->on('pacientes')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreign(['id_medico', 'id_especialidad'])
                ->references(['id_medico', 'id_especialidad'])
                ->on('medicos_especialidades')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreign('id_estado_cita')
                ->references('id_estado_cita')
                ->on('estados_cita')
                ->restrictOnDelete()
                ->restrictOnUpdate();
        });

        DB::statement(<<<'SQL'
            ALTER TABLE citas_medicas
            ADD CONSTRAINT citas_medicas_duracion_positiva CHECK (duracion_minutos > 0)
        SQL);

        DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');
        // PostgreSQL checks this exclusion constraint atomically, including concurrent transactions.
        // IDs fijados por estados_cita_catalogo_oficial y creados por EstadosCitaSeeder.
        DB::statement(<<<'SQL'
            ALTER TABLE citas_medicas
            ADD CONSTRAINT citas_medicas_no_superpuestas
            EXCLUDE USING gist (
                id_medico WITH =,
                tsrange(
                    (fecha_cita + hora_cita),
                    (fecha_cita + hora_cita + (duracion_minutos * interval '1 minute')),
                    '[)'
                ) WITH &&
            ) WHERE (id_estado_cita IN (1, 2))
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('citas_medicas');
    }
};
