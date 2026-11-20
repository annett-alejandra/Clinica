<?php

namespace App\Models\Paquetes\P2_PersonalMedico;

use App\Models\Paquetes\P1_SeguridadAdministracion\Especialidad;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerfilClinicoMedico extends Model
{
    protected $table = 'perfiles_clinicos_medicos';

    protected $primaryKey = 'id_perfil_clinico';

    public $timestamps = false;

    protected $fillable = [
        'id_medico',
        'id_especialidad',
        'subespecialidades',
        'condiciones_tratadas',
        'perfil_textual',
        'vector_embedding',
        'proveedor_embedding',
        'tasa_efectividad_base',
        'activo',
    ];

    protected $hidden = ['vector_embedding'];

    protected function casts(): array
    {
        return [
            'id_perfil_clinico' => 'integer',
            'id_medico' => 'integer',
            'id_especialidad' => 'integer',
            'subespecialidades' => 'array',
            'condiciones_tratadas' => 'array',
            'vector_embedding' => 'array',
            'tasa_efectividad_base' => 'float',
            'activo' => 'boolean',
            'actualizado_en' => 'immutable_datetime',
        ];
    }

    public function medico(): BelongsTo
    {
        return $this->belongsTo(Medico::class, 'id_medico', 'id_medico');
    }

    public function especialidad(): BelongsTo
    {
        return $this->belongsTo(Especialidad::class, 'id_especialidad', 'id_especialidad');
    }
}
