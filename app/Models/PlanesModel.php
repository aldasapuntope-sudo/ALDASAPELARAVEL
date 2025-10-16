<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


class PlanesModel extends Model
{
    public static function listarPlanes()
    {
       return DB::select(
            "SELECT * FROM planes WHERE is_active = 1 "
        );
    }

    public static function verificarPlanUsuario($usuario_id)
    {
        $plan = DB::table('usuarios_planes')
            ->join('planes', 'usuarios_planes.plan_id', '=', 'planes.id')
            ->select(
                'planes.nombre',
                'planes.descripcion',
                'planes.precio',
                'usuarios_planes.fecha_inicio',
                'usuarios_planes.fecha_fin',
                'usuarios_planes.anuncios_disponibles',
                'usuarios_planes.is_active'
            )
            ->where('usuarios_planes.user_id', $usuario_id)
            ->where('usuarios_planes.is_active', '1')
            ->first();

        if (!$plan) {
            return response()->json(['tiene_plan' => false]);
        }

        // Verificar si ya venció
        $hoy = Carbon::now();
        if ($hoy->gt(Carbon::parse($plan->fecha_fin))) {
            DB::table('usuarios_planes')
                ->where('id', $plan->usuario_plan_id)
                ->update(['estado' => 'vencido']);

            return response()->json(['tiene_plan' => false]);
        }

        return response()->json([
            'tiene_plan' => true,
            'plan' => $plan
        ]);
    }
    
}
