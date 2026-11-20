<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('perfiles_clinicos_medicos')) {
            return;
        }

        Schema::create('perfiles_clinicos_medicos', function (Blueprint $table) {
            $table->bigIncrements('id_perfil_clinico');
            $table->unsignedBigInteger('id_medico');
            $table->unsignedBigInteger('id_especialidad');
            $table->jsonb('subespecialidades')->nullable();
            $table->jsonb('condiciones_tratadas')->nullable();
            $table->text('perfil_textual');
            $table->jsonb('vector_embedding')->nullable();
            $table->string('proveedor_embedding', 60)->default('local_tfidf');
            $table->decimal('tasa_efectividad_base', 5, 2)->default(92.00);
            $table->boolean('activo')->default(true);
            $table->timestampTz('actualizado_en')->useCurrent();

            $table->unique(['id_medico', 'id_especialidad'], 'perfiles_clinicos_medico_especialidad_unique');

            $table->foreign('id_medico')
                ->references('id_medico')
                ->on('medicos')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->foreign('id_especialidad')
                ->references('id_especialidad')
                ->on('especialidades')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->index(['activo', 'id_especialidad']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perfiles_clinicos_medicos');
    }
};
