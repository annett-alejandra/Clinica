<?php

namespace App\Models\Paquetes\P1_SeguridadAdministracion;

use App\Models\Paquetes\P2_PersonalMedico\Medico;
use App\Models\Paquetes\P4_GestionCitas\CitaMedica;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Especialidad extends Model
{
    protected $table = 'especialidades';

    protected $primaryKey = 'id_especialidad';

    public $timestamps = false;

    protected $fillable = ['nombre_especialidad', 'descripcion', 'estado'];

    protected function casts(): array
    {
        return [
            'id_especialidad' => 'integer',
            'estado' => 'boolean',
        ];
    }

    public function medicos(): BelongsToMany
    {
        return $this->belongsToMany(Medico::class, 'medicos_especialidades', 'id_especialidad', 'id_medico', 'id_especialidad', 'id_medico');
    }

    public function citas(): HasMany
    {
        return $this->hasMany(CitaMedica::class, 'id_especialidad', 'id_especialidad');
    }
}
