<?php

namespace Database\Seeders;

use App\Models\Paquetes\P1_SeguridadAdministracion\Auditoria;
use App\Models\Paquetes\P1_SeguridadAdministracion\Especialidad;
use App\Models\Paquetes\P1_SeguridadAdministracion\Rol;
use App\Models\Paquetes\P2_PersonalMedico\HorarioAtencion;
use App\Models\Paquetes\P2_PersonalMedico\Medico;
use App\Models\Paquetes\P3_GestionPacientes\Paciente;
use App\Models\Paquetes\P4_GestionCitas\CitaMedica;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ClinicaDemoSeeder extends Seeder
{
    public function run(): void
    {
        $passwordHash = Hash::make('clinica123');

        // 1. Roles
        $rolAdmin = Rol::where('nombre_rol', 'Administrador')->first();
        $rolRecep = Rol::where('nombre_rol', 'Recepcionista')->first();
        $rolMedico = Rol::where('nombre_rol', 'Médico')->first();
        $rolPaciente = Rol::where('nombre_rol', 'Paciente')->first();

        // 2. Especialidades
        $especialidadesData = [
            ['nombre_especialidad' => 'Cardiología', 'descripcion' => 'Evaluación preventiva, ecocardiografía y tratamiento cardiovascular.'],
            ['nombre_especialidad' => 'Pediatría', 'descripcion' => 'Atención integral del neonato, lactante, infancia y adolescencia.'],
            ['nombre_especialidad' => 'Traumatología y Ortopedia', 'descripcion' => 'Cirugía reconstructiva, lesiones deportivas y patologías articulares.'],
            ['nombre_especialidad' => 'Neurología Clínica', 'descripcion' => 'Diagnóstico avanzado de cefaleas, epilepsia y trastornos neuromusculares.'],
            ['nombre_especialidad' => 'Dermatología', 'descripcion' => 'Tratamientos clínicos y procedimientos láser de la piel y mucosas.'],
            ['nombre_especialidad' => 'Ginecología y Obstetricia', 'descripcion' => 'Salud reproductiva femenina, control prenatal y suites de parto.'],
            ['nombre_especialidad' => 'Medicina Interna', 'descripcion' => 'Manejo clínico integral de adultos con patologías complejas y crónicas.'],
            ['nombre_especialidad' => 'Oftalmología', 'descripcion' => 'Microcirugía ocular, retinografía y corrección refractiva.'],
        ];

        $especialidadesMap = [];
        foreach ($especialidadesData as $e) {
            $esp = Especialidad::firstOrCreate(
                ['nombre_especialidad' => $e['nombre_especialidad']],
                ['descripcion' => $e['descripcion'], 'estado' => true]
            );
            $especialidadesMap[$e['nombre_especialidad']] = $esp->id_especialidad;
        }

        // 3. Médicos y Asignación de Especialidades
        $medicosData = [
            [
                'nombre_completo' => 'Dr. Roberto Valenzuela Miranda',
                'registro_profesional' => 'MP-89211',
                'correo' => 'roberto.valenzuela@clinica.com',
                'telefono' => '+591 71029384',
                'usuario' => 'dr_valenzuela',
                'especialidades' => ['Cardiología', 'Medicina Interna'],
            ],
            [
                'nombre_completo' => 'Dra. Elena Villarroel Suárez',
                'registro_profesional' => 'MP-90432',
                'correo' => 'elena.villarroel@clinica.com',
                'telefono' => '+591 76543210',
                'usuario' => 'dra_villarroel',
                'especialidades' => ['Pediatría'],
            ],
            [
                'nombre_completo' => 'Dr. Marcos Castillo Antelo',
                'registro_profesional' => 'MP-77819',
                'correo' => 'marcos.castillo@clinica.com',
                'telefono' => '+591 78901234',
                'usuario' => 'dr_castillo',
                'especialidades' => ['Traumatología y Ortopedia'],
            ],
            [
                'nombre_completo' => 'Dra. Sofía Claros Rojas',
                'registro_profesional' => 'MP-88301',
                'correo' => 'sofia.claros@clinica.com',
                'telefono' => '+591 69012345',
                'usuario' => 'dra_claros',
                'especialidades' => ['Neurología Clínica'],
            ],
            [
                'nombre_completo' => 'Dr. Andrés Aguilera Peña',
                'registro_profesional' => 'MP-91024',
                'correo' => 'andres.aguilera@clinica.com',
                'telefono' => '+591 72345678',
                'usuario' => 'dr_aguilera',
                'especialidades' => ['Dermatología'],
            ],
        ];

        $medicosCreados = [];
        foreach ($medicosData as $m) {
            $medico = Medico::firstOrCreate(
                ['registro_profesional' => $m['registro_profesional']],
                [
                    'nombre_completo' => $m['nombre_completo'],
                    'correo' => $m['correo'],
                    'telefono' => $m['telefono'],
                    'estado' => true,
                ]
            );

            // Asociar especialidades en pivote
            $idsEsp = array_map(fn($nom) => $especialidadesMap[$nom], $m['especialidades']);
            $medico->especialidades()->syncWithoutDetaching($idsEsp);

            // Crear usuario asociado
            User::firstOrCreate(
                ['nombre_usuario' => $m['usuario']],
                [
                    'correo' => $m['correo'],
                    'password_hash' => $passwordHash,
                    'estado' => true,
                    'id_rol' => $rolMedico->id_rol,
                    'id_medico' => $medico->id_medico,
                ]
            );

            $medicosCreados[] = $medico;
        }

        // 4. Horarios de Atención Semanales
        // Configurar turnos de Lunes (1) a Viernes (5) para cada médico
        foreach ($medicosCreados as $idx => $med) {
            // Turno Mañana: 08:00 a 12:00
            for ($dia = 1; $dia <= 5; $dia++) {
                HorarioAtencion::firstOrCreate(
                    [
                        'id_medico' => $med->id_medico,
                        'dia_semana' => $dia,
                        'hora_inicio' => '08:00:00',
                        'hora_fin' => '12:00:00',
                    ],
                    ['duracion_cita_minutos' => 30]
                );
            }

            // Turno Tarde para los primeros 3 médicos: 14:00 a 18:00
            if ($idx < 3) {
                for ($dia = 1; $dia <= 5; $dia++) {
                    HorarioAtencion::firstOrCreate(
                        [
                            'id_medico' => $med->id_medico,
                            'dia_semana' => $dia,
                            'hora_inicio' => '14:00:00',
                            'hora_fin' => '18:00:00',
                        ],
                        ['duracion_cita_minutos' => 30]
                    );
                }
            }
        }

        // 5. Pacientes
        $pacientesData = [
            [
                'ci' => '6829104',
                'nombre_completo' => 'Carlos Eduardo Mendoza Pérez',
                'fecha_nacimiento' => '1988-06-15',
                'genero' => 'Masculino',
                'correo' => 'carlos.mendoza@mail.com',
                'telefono' => '+591 78912345',
                'direccion_domicilio' => 'Av. Las Palmas #420',
                'contacto_emergencia' => 'María Mendoza (Madre)',
                'telefono_emergencia' => '+591 71234567',
                'tipo_paciente' => 'General',
                'usuario' => 'paciente_carlos',
            ],
            [
                'ci' => '7912403',
                'nombre_completo' => 'Lucía Marcela Gómez Saucedo',
                'fecha_nacimiento' => '1995-11-28',
                'genero' => 'Femenino',
                'correo' => 'lucia.gomez@mail.com',
                'telefono' => '+591 69081234',
                'direccion_domicilio' => 'Condominio Sevilla Real dpto 3B',
                'contacto_emergencia' => 'Jorge Gómez (Hermano)',
                'telefono_emergencia' => '+591 67890123',
                'tipo_paciente' => 'Asegurado',
                'usuario' => 'paciente_lucia',
            ],
            [
                'ci' => '5432198',
                'nombre_completo' => 'Fernando Justiniano Vaca',
                'fecha_nacimiento' => '1975-03-10',
                'genero' => 'Masculino',
                'correo' => 'fernando.justiniano@mail.com',
                'telefono' => '+591 70011223',
                'direccion_domicilio' => 'Barrio Sirari, C. Los Cusis #12',
                'tipo_paciente' => 'Convenio',
                'usuario' => 'paciente_fernando',
            ],
            [
                'ci' => '8123456',
                'nombre_completo' => 'Mariana Arteaga Torrico',
                'fecha_nacimiento' => '2001-08-22',
                'genero' => 'Femenino',
                'correo' => 'mariana.arteaga@mail.com',
                'telefono' => '+591 76098765',
                'direccion_domicilio' => 'Equipetrol Norte, Calle 8 Este',
                'tipo_paciente' => 'General',
                'usuario' => 'paciente_mariana',
            ],
            [
                'ci' => '4567890',
                'nombre_completo' => 'Ricardo Paz Soldán Suárez',
                'fecha_nacimiento' => '1962-12-05',
                'genero' => 'Masculino',
                'correo' => 'ricardo.paz@mail.com',
                'telefono' => '+591 71345678',
                'direccion_domicilio' => 'Av. San Martín esq. 4to Anillo',
                'tipo_paciente' => 'VIP',
                'usuario' => 'paciente_ricardo',
            ],
        ];

        $pacientesCreados = [];
        foreach ($pacientesData as $p) {
            $paciente = Paciente::firstOrCreate(
                ['ci' => $p['ci']],
                [
                    'nombre_completo' => $p['nombre_completo'],
                    'fecha_nacimiento' => $p['fecha_nacimiento'],
                    'genero' => $p['genero'],
                    'correo' => $p['correo'],
                    'telefono' => $p['telefono'],
                    'direccion_domicilio' => $p['direccion_domicilio'],
                    'contacto_emergencia' => $p['contacto_emergencia'] ?? null,
                    'telefono_emergencia' => $p['telefono_emergencia'] ?? null,
                    'tipo_paciente' => $p['tipo_paciente'],
                    'estado' => true,
                ]
            );

            User::firstOrCreate(
                ['nombre_usuario' => $p['usuario']],
                [
                    'correo' => $p['correo'],
                    'password_hash' => $passwordHash,
                    'estado' => true,
                    'id_rol' => $rolPaciente->id_rol,
                    'id_paciente' => $paciente->id_paciente,
                ]
            );

            $pacientesCreados[] = $paciente;
        }

        // 6. Usuarios Administrativos Adicionales
        User::firstOrCreate(
            ['nombre_usuario' => 'admin'],
            [
                'correo' => 'admin@clinica.com',
                'password_hash' => $passwordHash,
                'estado' => true,
                'id_rol' => $rolAdmin->id_rol,
            ]
        );

        User::firstOrCreate(
            ['nombre_usuario' => 'recepcion'],
            [
                'correo' => 'recepcion@clinica.com',
                'password_hash' => $passwordHash,
                'estado' => true,
                'id_rol' => $rolRecep->id_rol,
            ]
        );

        User::firstOrCreate(
            ['nombre_usuario' => 'recepcion2'],
            [
                'correo' => 'recepcion2@clinica.com',
                'password_hash' => $passwordHash,
                'estado' => true,
                'id_rol' => $rolRecep->id_rol,
            ]
        );

        // 7. Citas Médicas Programadas de Demostración
        $proximoLunes = Carbon::now()->next(Carbon::MONDAY)->format('Y-m-d');
        $proximoMartes = Carbon::now()->next(Carbon::TUESDAY)->format('Y-m-d');
        $proximoMiercoles = Carbon::now()->next(Carbon::WEDNESDAY)->format('Y-m-d');

        $citasDemo = [
            [
                'id_paciente' => $pacientesCreados[0]->id_paciente,
                'id_medico' => $medicosCreados[0]->id_medico,
                'id_especialidad' => $especialidadesMap['Cardiología'],
                'fecha_cita' => $proximoLunes,
                'hora_cita' => '08:00:00',
                'duracion_minutos' => 30,
                'motivo' => 'Control de presión arterial y evaluación ergométrica',
            ],
            [
                'id_paciente' => $pacientesCreados[1]->id_paciente,
                'id_medico' => $medicosCreados[1]->id_medico,
                'id_especialidad' => $especialidadesMap['Pediatría'],
                'fecha_cita' => $proximoLunes,
                'hora_cita' => '09:00:00',
                'duracion_minutos' => 30,
                'motivo' => 'Control de desarrollo y esquema de vacunación infantil',
            ],
            [
                'id_paciente' => $pacientesCreados[2]->id_paciente,
                'id_medico' => $medicosCreados[2]->id_medico,
                'id_especialidad' => $especialidadesMap['Traumatología y Ortopedia'],
                'fecha_cita' => $proximoMartes,
                'hora_cita' => '10:00:00',
                'duracion_minutos' => 30,
                'motivo' => 'Revisión post-quirúrgica de menisco y articulación',
            ],
            [
                'id_paciente' => $pacientesCreados[3]->id_paciente,
                'id_medico' => $medicosCreados[3]->id_medico,
                'id_especialidad' => $especialidadesMap['Neurología Clínica'],
                'fecha_cita' => $proximoMiercoles,
                'hora_cita' => '11:00:00',
                'duracion_minutos' => 30,
                'motivo' => 'Estudio de cefaleas recurrentes y resonancia magnética',
            ],
            [
                'id_paciente' => $pacientesCreados[4]->id_paciente,
                'id_medico' => $medicosCreados[4]->id_medico,
                'id_especialidad' => $especialidadesMap['Dermatología'],
                'fecha_cita' => $proximoMiercoles,
                'hora_cita' => '08:30:00',
                'duracion_minutos' => 30,
                'motivo' => 'Dermatoscopía de lesiones pigmentadas y control solar',
            ],
        ];

        foreach ($citasDemo as $c) {
            $existe = CitaMedica::where('id_medico', $c['id_medico'])
                ->where('fecha_cita', $c['fecha_cita'])
                ->where('hora_cita', $c['hora_cita'])
                ->exists();

            if (!$existe) {
                CitaMedica::create([
                    'codigo_unico' => (string) Str::uuid(),
                    'id_paciente' => $c['id_paciente'],
                    'id_medico' => $c['id_medico'],
                    'id_especialidad' => $c['id_especialidad'],
                    'id_estado_cita' => 1, // Programada
                    'fecha_cita' => $c['fecha_cita'],
                    'hora_cita' => $c['hora_cita'],
                    'duracion_minutos' => $c['duracion_minutos'],
                    'motivo' => $c['motivo'],
                ]);
            }
        }

        // 8. Evento de Auditoría Inicial
        Auditoria::create([
            'id_usuario' => 1,
            'accion' => 'POBLACION_DEMO_SISTEMA',
            'tabla_afectada' => 'sistema',
            'id_registro_afectado' => 1,
            'detalle' => [
                'especialidades_creadas' => count($especialidadesData),
                'medicos_creados' => count($medicosData),
                'pacientes_creados' => count($pacientesData),
                'citas_creadas' => count($citasDemo),
            ],
        ]);
    }
}
