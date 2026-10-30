<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('horarios_atencion', function (Blueprint $table) {
            $table->bigIncrements('id_horario');
            $table->unsignedBigInteger('id_medico');
            $table->unsignedSmallInteger('dia_semana');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->unsignedSmallInteger('duracion_cita_minutos');

            $table->foreign('id_medico')
                ->references('id_medico')
                ->on('medicos')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->unique(['id_medico', 'dia_semana', 'hora_inicio', 'hora_fin']);
        });

        DB::statement(<<<'SQL'
            ALTER TABLE horarios_atencion
            ADD CONSTRAINT horarios_atencion_dia_semana_valido CHECK (dia_semana BETWEEN 1 AND 7),
            ADD CONSTRAINT horarios_atencion_intervalo_valido CHECK (hora_fin > hora_inicio),
            ADD CONSTRAINT horarios_atencion_duracion_positiva CHECK (duracion_cita_minutos > 0)
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('horarios_atencion');
    }
};
