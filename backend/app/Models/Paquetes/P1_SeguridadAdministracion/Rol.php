<?php

namespace App\Models\Paquetes\P1_SeguridadAdministracion;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rol extends Model
{
    protected $table = 'roles';

    protected $primaryKey = 'id_rol';

    public $timestamps = false;

    protected $fillable = ['nombre_rol', 'descripcion'];

    protected function casts(): array
    {
        return ['id_rol' => 'integer'];
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class, 'id_rol', 'id_rol');
    }
}
