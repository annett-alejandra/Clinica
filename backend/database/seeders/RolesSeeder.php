<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        // La clave natural única permite repetir el seeder sin cambiar roles existentes.
        DB::table('roles')->insertOrIgnore([
            ['nombre_rol' => 'Administrador', 'descripcion' => 'Administración de usuarios, médicos y especialidades.'],
            ['nombre_rol' => 'Recepcionista', 'descripcion' => 'Gestión de pacientes y programación de citas.'],
            ['nombre_rol' => 'Médico', 'descripcion' => 'Consulta de agenda y registro de atención.'],
            ['nombre_rol' => 'Paciente', 'descripcion' => 'Acceso del paciente a sus citas.'],
        ]);
    }
}
