<?php

namespace App\Services\Paquetes\P3_GestionPacientes;

use App\Models\Paquetes\P1_SeguridadAdministracion\Rol;
use App\Models\Paquetes\P3_GestionPacientes\Paciente;
use App\Models\User;
use App\Services\Paquetes\P1_SeguridadAdministracion\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use InvalidArgumentException;

class RegistroPacienteService
{
    /**
     * Auto-registro público de paciente con vinculación o creación de ficha clínica.
     */
    public function registrar(array $datos): array
    {
        $ci = trim($datos['ci']);
        $correo = trim($datos['correo']);
        $nombreUsuario = trim($datos['nombre_usuario'] ?? explode('@', $correo)[0]);

        // Validar unicidad en usuarios
        if (User::where('correo', $correo)->exists()) {
            throw new InvalidArgumentException('El correo electrónico ya está registrado.');
        }

        // Si el nombre de usuario ya existe, añadir sufijo aleatorio
        if (User::where('nombre_usuario', $nombreUsuario)->exists()) {
            $nombreUsuario .= '_' . Str::random(4);
        }

        return DB::transaction(function () use ($datos, $ci, $correo, $nombreUsuario) {
            // Obtener el rol Paciente
            $rolPaciente = Rol::where('nombre_rol', 'Paciente')->first();
            $idRol = $rolPaciente ? $rolPaciente->id_rol : 4;

            // Verificar si el paciente ya tenía ficha física por CI
            $paciente = Paciente::where('ci', $ci)->first();

            if ($paciente) {
                // Verificar si ya tenía un usuario asociado
                if ($paciente->usuario()->exists()) {
                    throw new InvalidArgumentException("Ya existe una cuenta de usuario vinculada a la cédula de identidad {$ci}.");
                }
                // Actualizar correo y teléfono en la ficha si faltaban
                $paciente->correo = $paciente->correo ?: $correo;
                $paciente->telefono = $paciente->telefono ?: ($datos['telefono'] ?? null);
                $paciente->save();
            } else {
                // Crear nueva ficha de paciente
                $paciente = Paciente::create([
                    'ci' => $ci,
                    'nombre_completo' => trim($datos['nombre_completo']),
                    'fecha_nacimiento' => $datos['fecha_nacimiento'],
                    'genero' => $datos['genero'],
                    'correo' => $correo,
                    'telefono' => $datos['telefono'] ?? null,
                    'direccion_domicilio' => $datos['direccion_domicilio'] ?? null,
                    'tipo_paciente' => 'General',
                    'estado' => true,
                ]);
            }

            // Crear el usuario web para el paciente
            $usuario = User::create([
                'nombre_usuario' => $nombreUsuario,
                'correo' => $correo,
                'password_hash' => Hash::make($datos['password']),
                'estado' => true,
                'id_rol' => $idRol,
                'id_paciente' => $paciente->id_paciente,
            ]);

            // Generar token de sesión inicial
            $token = hash('sha256', Str::random(40) . microtime(true));

            AuditService::registrar(
                $usuario->id_usuario,
                'AUTOREGISTRO_PACIENTE',
                'usuarios',
                $usuario->id_usuario,
                ['ci' => $ci, 'correo' => $correo, 'id_paciente' => $paciente->id_paciente]
            );

            return [
                'token' => $token,
                'usuario' => [
                    'id_usuario' => $usuario->id_usuario,
                    'nombre_usuario' => $usuario->nombre_usuario,
                    'correo' => $usuario->correo,
                    'rol' => 'Paciente',
                    'id_rol' => $idRol,
                    'id_paciente' => $paciente->id_paciente,
                    'perfil' => $paciente->nombre_completo,
                ],
                'paciente' => $paciente->makeVisible(['ci', 'fecha_nacimiento']),
            ];
        });
    }
}

