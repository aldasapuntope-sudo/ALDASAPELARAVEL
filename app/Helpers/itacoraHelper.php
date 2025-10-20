<?php

namespace App\Helpers;

use App\Models\BitacoraModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class BitacoraHelper
{
    public static function registrar($accion, $tabla, $registro_id = null, $descripcion = null)
    {
        Bitacora::create([
            'user_id' => Auth::id(),
            'accion' => strtoupper($accion),
            'tabla_afectada' => $tabla,
            'registro_id' => $registro_id,
            'descripcion' => $descripcion,
            'ip' => Request::ip(),
        ]);
    }
}
