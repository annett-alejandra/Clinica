<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pacientes', function (Blueprint $table) {
            $table->bigIncrements('id_paciente');
            $table->string('ci', 50)->nullable()->unique();
            $table->string('nombre_completo', 255);
            $table->date('fecha_nacimiento');
            $table->string('genero', 50);
            $table->string('correo', 255)->nullable();
            $table->string('telefono', 50)->nullable();
            $table->string('direccion_domicilio', 255)->nullable();
            $table->string('contacto_emergencia', 255)->nullable();
            $table->string('telefono_emergencia', 50)->nullable();
            // Opcional hasta que la clínica defina una clasificación oficial.
            $table->string('tipo_paciente', 100)->nullable();
            $table->text('alergias')->nullable();
            $table->text('informacion_medica_basica')->nullable();
            $table->boolean('estado')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pacientes');
    }
};
