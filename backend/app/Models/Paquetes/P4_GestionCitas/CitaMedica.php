<?php

namespace App\Models\Paquetes\P4_GestionCitas;

use App\Models\Paquetes\P1_SeguridadAdministracion\Especialidad;
use App\Models\Paquetes\P2_PersonalMedico\Medico;
use App\Models\Paquetes\P3_GestionPacientes\Paciente;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CitaMedica extends Model
{
    protected $table = 'citas_medicas';

    protected $primaryKey = 'id_cita';

    public $timestamps = false;

    // fecha_creacion conserva el valor por defecto de PostgreSQL.
    protected $fillable = [
        'codigo_unico',
        'id_paciente',
        'id_medico',
        'id_especialidad',
        'id_estado_cita',
        'fecha_cita',
        'hora_cita',
        'duracion_minutos',
        'fecha_hora_llegada',
        'motivo',
        'motivo_cancelacion',
        'observacion_atencion',
    ];

    protected $hidden = ['motivo', 'motivo_cancelacion', 'observacion_atencion'];

    protected function casts(): array
    {
        return [
            'id_cita' => 'integer',
            'id_paciente' => 'integer',
            'id_medico' => 'integer',
            'id_especialidad' => 'integer',
            'id_estado_cita' => 'integer',
            'fecha_cita' => 'immutable_date:Y-m-d',
            'hora_cita' => 'string',
            'duracion_minutos' => 'integer',
            'fecha_creacion' => 'immutable_datetime',
            'fecha_hora_llegada' => 'immutable_datetime',
        ];
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'id_paciente', 'id_paciente');
    }

    // Estas relaciones consultan cada entidad; PostgreSQL valida la pareja médico–especialidad.
    public function medico(): BelongsTo
    {
        return $this->belongsTo(Medico::class, 'id_medico', 'id_medico');
    }

    public function especialidad(): BelongsTo
    {
        return $this->belongsTo(Especialidad::class, 'id_especialidad', 'id_especialidad');
    }

    public function estado(): BelongsTo
    {
        return $this->belongsTo(EstadoCita::class, 'id_estado_cita', 'id_estado_cita');
    }

    public function estadoCita(): BelongsTo
    {
        return $this->estado();
    }

    public function historialCambios(): HasMany
    {
        return $this->hasMany(HistorialCambioCita::class, 'id_cita', 'id_cita');
    }
}
