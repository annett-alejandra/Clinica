<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre_usuario' => fake()->unique()->userName(),
            'correo' => fake()->unique()->safeEmail(),
            'password_hash' => Hash::make(Str::password(40)),
            'estado' => true,
            // Resolver por nombre evita asumir que el ID 1 existe o es un rol apropiado.
            'id_rol' => function (): int {
                DB::table('roles')->insertOrIgnore([
                    'nombre_rol' => 'Paciente',
                    'descripcion' => 'Acceso del paciente a sus citas.',
                ]);

                return (int) DB::table('roles')->where('nombre_rol', 'Paciente')->soleValue('id_rol');
            },
            'id_paciente' => null,
            'id_medico' => null,
        ];
    }
}
