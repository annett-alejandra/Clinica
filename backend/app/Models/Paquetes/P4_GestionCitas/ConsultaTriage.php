<?php

namespace App\Models\Paquetes\P4_GestionCitas;

use App\Models\Paquetes\P1_SeguridadAdministracion\Especialidad;
use App\Models\Paquetes\P2_PersonalMedico\Medico;
use App\Models\Paquetes\P3_GestionPacientes\Paciente;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsultaTriage extends Model
{
    public const ACCIONES_DERIVACION = [
        'DERIVADO_URGENCIAS' => 'Derivado a Urgencias 24/7 (Box / Shock)',
        'AMBULANCIA_DESPACHADA' => 'Ambulancia despachada a domicilio',
        'CITA_PRIORITARIA' => 'Derivado a Cita Médica Prioritaria',
        'CONTACTO_TELEFONICO' => 'Contacto telefónico / Paciente orientado',
        'FALSO_POSITIVO' => 'Descartado / Falso positivo verificado',
    ];

    protected $table = 'consultas_triage';

    protected $primaryKey = 'id_triage';

    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'id_paciente',
        'sintomas_texto',
        'vector_consulta',
        'proveedor_ia',
        'nivel_urgencia',
        'es_emergencia',
        'palabras_emergencia_detectadas',
        'id_especialidad_recomendada',
        'id_medico_recomendado',
        'puntaje_afinidad',
        'explicacion_clinica',
        'recomendaciones_json',
        'alerta_atendida',
        'id_usuario_atendio',
        'fecha_atencion_alerta',
    ];

    protected $hidden = ['vector_consulta'];

    protected function casts(): array
    {
        return [
            'id_triage' => 'integer',
            'id_usuario' => 'integer',
            'id_paciente' => 'integer',
            'vector_consulta' => 'array',
            'es_emergencia' => 'boolean',
            'palabras_emergencia_detectadas' => 'array',
            'id_especialidad_recomendada' => 'integer',
            'id_medico_recomendado' => 'integer',
            'puntaje_afinidad' => 'float',
            'recomendaciones_json' => 'array',
            'alerta_atendida' => 'boolean',
            'id_usuario_atendio' => 'integer',
            'fecha_atencion_alerta' => 'immutable_datetime',
            'fecha_registro' => 'immutable_datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'id_paciente', 'id_paciente');
    }

    public function especialidadRecomendada(): BelongsTo
    {
        return $this->belongsTo(Especialidad::class, 'id_especialidad_recomendada', 'id_especialidad');
    }

    public function medicoRecomendado(): BelongsTo
    {
        return $this->belongsTo(Medico::class, 'id_medico_recomendado', 'id_medico');
    }

    public function usuarioAtendio(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario_atendio', 'id_usuario');
    }

    public function resolucionManual(): ?array
    {
        $datos = $this->recomendaciones_json;
        if (! is_array($datos)) {
            return null;
        }

        return isset($datos['resolucion_manual']) && is_array($datos['resolucion_manual'])
            ? $datos['resolucion_manual']
            : null;
    }
}
