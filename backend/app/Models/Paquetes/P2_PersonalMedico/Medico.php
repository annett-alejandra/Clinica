<?php

namespace App\Models\Paquetes\P2_PersonalMedico;

use App\Models\Paquetes\P1_SeguridadAdministracion\Especialidad;
use App\Models\Paquetes\P4_GestionCitas\CitaMedica;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Medico extends Model
{
    protected $table = 'medicos';

    protected $primaryKey = 'id_medico';

    public $timestamps = false;

    protected $fillable = ['nombre_completo', 'registro_profesional', 'correo', 'telefono', 'estado'];

    protected function casts(): array
    {
        return [
            'id_medico' => 'integer',
            'estado' => 'boolean',
        ];
    }

    public function usuario(): HasOne
    {
        return $this->hasOne(User::class, 'id_medico', 'id_medico');
    }

    public function especialidades(): BelongsToMany
    {
        return $this->belongsToMany(Especialidad::class, 'medicos_especialidades', 'id_medico', 'id_especialidad', 'id_medico', 'id_especialidad');
    }

    public function horariosAtencion(): HasMany
    {
        return $this->hasMany(HorarioAtencion::class, 'id_medico', 'id_medico');
    }

    public function citas(): HasMany
    {
        return $this->hasMany(CitaMedica::class, 'id_medico', 'id_medico');
    }
}
