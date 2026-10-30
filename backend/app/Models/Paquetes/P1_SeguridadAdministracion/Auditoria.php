<?php

namespace App\Models\Paquetes\P1_SeguridadAdministracion;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Auditoria extends Model
{
    protected $table = 'auditoria';

    protected $primaryKey = 'id_auditoria';

    public $timestamps = false;

    // fecha_hora la establece PostgreSQL; detalle debe contener solo metadatos necesarios.
    protected $fillable = ['id_usuario', 'accion', 'tabla_afectada', 'id_registro_afectado', 'detalle'];

    protected $hidden = ['detalle'];

    protected function casts(): array
    {
        return [
            'id_auditoria' => 'integer',
            'id_usuario' => 'integer',
            'id_registro_afectado' => 'integer',
            'fecha_hora' => 'immutable_datetime',
            'detalle' => 'array',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }
}
