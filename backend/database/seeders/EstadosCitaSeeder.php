<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use LogicException;

class EstadosCitaSeeder extends Seeder
{
    public function run(): void
    {
        // Contrato compartido con estados_cita_catalogo_oficial y citas_medicas_no_superpuestas.
        $estados = [
            ['id_estado_cita' => 1, 'nombre_estado' => 'Programada', 'descripcion' => 'Cita reservada pendiente de confirmación.'],
            ['id_estado_cita' => 2, 'nombre_estado' => 'Confirmada', 'descripcion' => 'Cita confirmada que ocupa un horario.'],
            ['id_estado_cita' => 3, 'nombre_estado' => 'Atendida', 'descripcion' => 'Cita cuya atención ha finalizado.'],
            ['id_estado_cita' => 4, 'nombre_estado' => 'Cancelada', 'descripcion' => 'Cita cancelada que libera el horario.'],
            ['id_estado_cita' => 5, 'nombre_estado' => 'Inasistencia', 'descripcion' => 'Cita a la que el paciente no asistió.'],
        ];

        DB::transaction(function () use ($estados): void {
            DB::table('estados_cita')->insertOrIgnore($estados);

            if (DB::table('estados_cita')->count() !== count($estados)) {
                throw new LogicException('El catálogo contiene estados ajenos al catálogo oficial. Revisar los datos sin eliminarlos ni renumerarlos automáticamente.');
            }

            foreach ($estados as $estado) {
                $coincide = DB::table('estados_cita')
                    ->where('id_estado_cita', $estado['id_estado_cita'])
                    ->where('nombre_estado', $estado['nombre_estado'])
                    ->exists();

                if (! $coincide) {
                    throw new LogicException('El catálogo de estados existente no coincide con los IDs oficiales. Revisar los datos y preparar una migración antes de continuar.');
                }
            }
        });
    }
}
