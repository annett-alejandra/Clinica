<?php

namespace App\Models\Paquetes\P3_GestionPacientes;

use App\Models\Paquetes\P4_GestionCitas\CitaMedica;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Paciente extends Model
{
    protected $table = 'pacientes';

    protected $primaryKey = 'id_paciente';

    public $timestamps = false;

    protected $fillable = [
        'ci',
        'nombre_completo',
        'fecha_nacimiento',
        'genero',
        'correo',
        'telefono',
        'direccion_domicilio',
        'contacto_emergencia',
        'telefono_emergencia',
        'tipo_paciente',
        'alergias',
        'informacion_medica_basica',
        'estado',
    ];

    protected $hidden = [
        'ci',
        'fecha_nacimiento',
        'direccion_domicilio',
        'contacto_emergencia',
        'telefono_emergencia',
        'alergias',
        'informacion_medica_basica',
    ];

    protected function casts(): array
    {
        return [
            'id_paciente' => 'integer',
            'fecha_nacimiento' => 'immutable_date:Y-m-d',
            'estado' => 'boolean',
        ];
    }

    public function usuario(): HasOne
    {
        return $this->hasOne(User::class, 'id_paciente', 'id_paciente');
    }

    public function citas(): HasMany
    {
        return $this->hasMany(CitaMedica::class, 'id_paciente', 'id_paciente');
    }
}
