<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AdministracionModel;

class AdministracionController extends Controller
{
    public function ltipoDocumento()
    {
        $resultado = AdministracionModel::ltipoDocumento();
        return response()->json($resultado);
    }

}
