<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicos_especialidades', function (Blueprint $table) {
            $table->unsignedBigInteger('id_medico');
            $table->unsignedBigInteger('id_especialidad');
            $table->primary(['id_medico', 'id_especialidad']);

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
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicos_especialidades');
    }
};
