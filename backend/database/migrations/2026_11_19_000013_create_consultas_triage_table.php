<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('consultas_triage')) {
            return;
        }

        Schema::create('consultas_triage', function (Blueprint $table) {
            $table->bigIncrements('id_triage');
            $table->unsignedBigInteger('id_usuario')->nullable();
            $table->unsignedBigInteger('id_paciente')->nullable();
            $table->text('sintomas_texto');
            $table->jsonb('vector_consulta')->nullable();
            $table->string('proveedor_ia', 60)->default('local_tfidf');
            $table->string('nivel_urgencia', 20)->default('NORMAL');
            $table->boolean('es_emergencia')->default(false);
            $table->jsonb('palabras_emergencia_detectadas')->nullable();
            $table->unsignedBigInteger('id_especialidad_recomendada')->nullable();
            $table->unsignedBigInteger('id_medico_recomendado')->nullable();
            $table->decimal('puntaje_afinidad', 5, 2)->nullable();
            $table->text('explicacion_clinica')->nullable();
            $table->jsonb('recomendaciones_json')->nullable();
            $table->boolean('alerta_atendida')->default(false);
            $table->unsignedBigInteger('id_usuario_atendio')->nullable();
            $table->timestampTz('fecha_atencion_alerta')->nullable();
            $table->timestampTz('fecha_registro')->useCurrent();

            $table->foreign('id_usuario')
                ->references('id_usuario')
                ->on('usuarios')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->foreign('id_paciente')
                ->references('id_paciente')
                ->on('pacientes')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->foreign('id_especialidad_recomendada')
                ->references('id_especialidad')
                ->on('especialidades')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->foreign('id_medico_recomendado')
                ->references('id_medico')
                ->on('medicos')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->foreign('id_usuario_atendio')
                ->references('id_usuario')
                ->on('usuarios')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->index(['es_emergencia', 'alerta_atendida', 'fecha_registro'], 'consultas_triage_alertas_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultas_triage');
    }
};
