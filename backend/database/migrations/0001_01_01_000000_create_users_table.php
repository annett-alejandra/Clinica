<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->bigIncrements('id_rol');
            $table->string('nombre_rol', 100)->unique();
            $table->text('descripcion')->nullable();
        });

        Schema::create('usuarios', function (Blueprint $table) {
            $table->bigIncrements('id_usuario');
            $table->string('nombre_usuario', 100)->unique();
            $table->string('correo', 255)->unique();
            $table->string('password_hash');
            $table->boolean('estado')->default(true);
            $table->unsignedBigInteger('id_rol');
            $table->unsignedBigInteger('id_paciente')->nullable()->unique();
            $table->unsignedBigInteger('id_medico')->nullable()->unique();

            $table->foreign('id_rol')
                ->references('id_rol')
                ->on('roles')
                ->restrictOnDelete()
                ->restrictOnUpdate();
        });

        DB::statement(<<<'SQL'
            ALTER TABLE usuarios
            ADD CONSTRAINT usuarios_vinculo_exclusivo
            CHECK (id_paciente IS NULL OR id_medico IS NULL)
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuarios');
        Schema::dropIfExists('roles');
    }
};
