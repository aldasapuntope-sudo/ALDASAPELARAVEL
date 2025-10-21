<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BitacoraModel;
use Illuminate\Support\Facades\Auth;

class BitacoraController extends Controller
{
    protected $bitacora;

    public function __construct()
    {
        $this->bitacora = new BitacoraModel();
    }

    /**
     * Registrar una acción en la bitácora.
     *
     * @param string $accion
     * @param string $tabla
     * @param int|null $registro_id
     * @param string|null $descripcion
     */
    public function registrar($accion, $tabla, $registro_id = null, $descripcion = null)
    {
        $user = Auth::user();

        $data = [
            'user_id' => $user ? $user->id : null,
            'accion' => $accion,
            'tabla_afectada' => $tabla,
            'registro_id' => $registro_id,
            'descripcion' => $descripcion,
            'ip' => request()->ip(),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        return $this->bitacora->insertar($data);
    }
}
