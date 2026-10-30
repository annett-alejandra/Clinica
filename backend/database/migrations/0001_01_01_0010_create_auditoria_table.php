<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditoria', function (Blueprint $table) {
            $table->bigIncrements('id_auditoria');
            $table->unsignedBigInteger('id_usuario')->nullable();
            $table->string('accion', 100);
            $table->string('tabla_afectada', 100);
            $table->unsignedBigInteger('id_registro_afectado')->nullable();
            $table->timestampTz('fecha_hora')->useCurrent();
            // Guardar solo metadatos necesarios; excluir contraseñas, tokens y datos clínicos.
            $table->jsonb('detalle')->nullable();

            $table->foreign('id_usuario')
                ->references('id_usuario')
                ->on('usuarios')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->index(['tabla_afectada', 'id_registro_afectado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditoria');
    }
};
