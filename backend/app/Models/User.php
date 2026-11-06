<?php

namespace App\Models;

use App\Models\Paquetes\P1_SeguridadAdministracion\Auditoria;
use App\Models\Paquetes\P1_SeguridadAdministracion\Rol;
use App\Models\Paquetes\P2_PersonalMedico\Medico;
use App\Models\Paquetes\P3_GestionPacientes\Paciente;
use App\Models\Paquetes\P4_GestionCitas\HistorialCambioCita;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['nombre_usuario', 'correo', 'password_hash', 'estado', 'id_rol', 'id_paciente', 'id_medico'])]
#[Hidden(['password_hash'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $table = 'usuarios';

    protected $primaryKey = 'id_usuario';

    // El esquema actual no almacena tokens de «recordarme».
    protected $rememberTokenName = '';

    public $timestamps = false;

    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'id_rol', 'id_rol');
    }

    /** Nombre del rol asignado (Administrador, Recepcionista, Médico o Paciente). */
    public function nombreRol(): ?string
    {
        return $this->rol?->nombre_rol;
    }

    public function tieneRol(string ...$roles): bool
    {
        return in_array($this->nombreRol(), $roles, true);
    }

    /** Nombre visible del perfil clínico asociado, o el nombre de usuario como respaldo. */
    public function nombreVisible(): string
    {
        return $this->medico?->nombre_completo
            ?? $this->paciente?->nombre_completo
            ?? $this->nombre_usuario;
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'id_paciente', 'id_paciente');
    }

    public function medico(): BelongsTo
    {
        return $this->belongsTo(Medico::class, 'id_medico', 'id_medico');
    }

    public function auditorias(): HasMany
    {
        return $this->hasMany(Auditoria::class, 'id_usuario', 'id_usuario');
    }

    public function historialCambiosCita(): HasMany
    {
        return $this->hasMany(HistorialCambioCita::class, 'id_usuario', 'id_usuario');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password_hash' => 'hashed',
            'estado' => 'boolean',
            'id_rol' => 'integer',
            'id_paciente' => 'integer',
            'id_medico' => 'integer',
        ];
    }
}
