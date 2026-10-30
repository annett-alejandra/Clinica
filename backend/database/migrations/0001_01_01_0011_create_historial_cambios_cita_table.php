<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historial_cambios_cita', function (Blueprint $table) {
            $table->bigIncrements('id_historial');
            $table->unsignedBigInteger('id_cita');
            $table->unsignedBigInteger('id_usuario')->nullable();
            $table->string('tipo_cambio', 100);
            $table->dateTimeTz('fecha_hora_anterior')->nullable();
            $table->dateTimeTz('fecha_hora_nueva')->nullable();
            $table->unsignedBigInteger('estado_anterior')->nullable();
            $table->unsignedBigInteger('estado_nuevo')->nullable();
            $table->timestampTz('fecha_modificacion')->useCurrent();
            $table->text('observacion')->nullable();

            $table->foreign('id_cita')
                ->references('id_cita')
                ->on('citas_medicas')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreign('id_usuario')
                ->references('id_usuario')
                ->on('usuarios')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreign('estado_anterior')
                ->references('id_estado_cita')
                ->on('estados_cita')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreign('estado_nuevo')
                ->references('id_estado_cita')
                ->on('estados_cita')
                ->restrictOnDelete()
                ->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_cambios_cita');
    }
};
