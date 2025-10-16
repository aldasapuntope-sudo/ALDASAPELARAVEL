<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PlanesModel;
use Illuminate\Support\Facades\Http;

class PlanesController extends Controller
{
    public function listarPlanes()
    {
        $resultado = PlanesModel::listarPlanes();
        return response()->json($resultado);
    }

     public function verificarPlanUsuario($usuario)
    {
        $resultado = PlanesModel::verificarPlanUsuario($usuario);
        return response()->json($resultado);
    }
    
}
