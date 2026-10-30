<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicos', function (Blueprint $table) {
            $table->bigIncrements('id_medico');
            $table->string('nombre_completo', 255);
            $table->string('registro_profesional', 100)->unique();
            $table->string('correo', 255)->nullable();
            $table->string('telefono', 50)->nullable();
            $table->boolean('estado')->default(true);
        });

        Schema::table('usuarios', function (Blueprint $table) {
            $table->foreign('id_paciente')
                ->references('id_paciente')
                ->on('pacientes')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreign('id_medico')
                ->references('id_medico')
                ->on('medicos')
                ->restrictOnDelete()
                ->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropForeign(['id_paciente']);
            $table->dropForeign(['id_medico']);
        });

        Schema::dropIfExists('medicos');
    }
};
