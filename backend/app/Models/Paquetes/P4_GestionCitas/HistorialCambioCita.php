<?php

namespace App\Models\Paquetes\P4_GestionCitas;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialCambioCita extends Model
{
    protected $table = 'historial_cambios_cita';

    protected $primaryKey = 'id_historial';

    public $timestamps = false;

    // fecha_modificacion la establece PostgreSQL al insertar el registro.
    protected $fillable = [
        'id_cita',
        'id_usuario',
        'tipo_cambio',
        'fecha_hora_anterior',
        'fecha_hora_nueva',
        'estado_anterior',
        'estado_nuevo',
        'observacion',
    ];

    protected $hidden = ['observacion'];

    protected function casts(): array
    {
        return [
            'id_historial' => 'integer',
            'id_cita' => 'integer',
            'id_usuario' => 'integer',
            'fecha_hora_anterior' => 'immutable_datetime',
            'fecha_hora_nueva' => 'immutable_datetime',
            'estado_anterior' => 'integer',
            'estado_nuevo' => 'integer',
            'fecha_modificacion' => 'immutable_datetime',
        ];
    }

    public function cita(): BelongsTo
    {
        return $this->belongsTo(CitaMedica::class, 'id_cita', 'id_cita');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }

    public function estadoAnterior(): BelongsTo
    {
        return $this->belongsTo(EstadoCita::class, 'estado_anterior', 'id_estado_cita');
    }

    public function estadoNuevo(): BelongsTo
    {
        return $this->belongsTo(EstadoCita::class, 'estado_nuevo', 'id_estado_cita');
    }
}
