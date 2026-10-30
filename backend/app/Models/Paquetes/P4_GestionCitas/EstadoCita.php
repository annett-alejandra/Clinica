<?php

namespace App\Models\Paquetes\P4_GestionCitas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EstadoCita extends Model
{
    protected $table = 'estados_cita';

    protected $primaryKey = 'id_estado_cita';

    // El catálogo oficial utiliza IDs explícitos, sin secuencia de PostgreSQL.
    public $incrementing = false;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = ['id_estado_cita', 'nombre_estado', 'descripcion'];

    protected function casts(): array
    {
        return ['id_estado_cita' => 'integer'];
    }

    public function citas(): HasMany
    {
        return $this->hasMany(CitaMedica::class, 'id_estado_cita', 'id_estado_cita');
    }
}
