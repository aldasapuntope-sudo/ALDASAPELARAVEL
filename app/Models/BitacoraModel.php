<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BitacoraModel extends Model
{
    protected $table = 'bitacora';
    protected $fillable = [
        'user_id',
        'accion',
        'tabla_afectada',
        'registro_id',
        'descripcion',
        'ip'
    ];
}
