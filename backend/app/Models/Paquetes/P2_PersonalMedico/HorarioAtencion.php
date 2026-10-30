<?php

namespace App\Models\Paquetes\P2_PersonalMedico;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HorarioAtencion extends Model
{
    protected $table = 'horarios_atencion';

    protected $primaryKey = 'id_horario';

    public $timestamps = false;

    protected $fillable = ['id_medico', 'dia_semana', 'hora_inicio', 'hora_fin', 'duracion_cita_minutos'];

    protected function casts(): array
    {
        return [
            'id_horario' => 'integer',
            'id_medico' => 'integer',
            'dia_semana' => 'integer',
            // Son columnas TIME, sin fecha ni zona horaria.
            'hora_inicio' => 'string',
            'hora_fin' => 'string',
            'duracion_cita_minutos' => 'integer',
        ];
    }

    public function medico(): BelongsTo
    {
        return $this->belongsTo(Medico::class, 'id_medico', 'id_medico');
    }
}
