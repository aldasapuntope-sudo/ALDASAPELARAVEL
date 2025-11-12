<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MenuController extends Controller
{
    public function index()
    {
        // Obtener tipos de propiedad
        $tiposPropiedad = DB::table('tipos_propiedad')
            ->where('is_active', 1)
            ->select('id', 'nombre')
            ->orderBy('nombre')
            ->get();

        // Obtener solo las 5 ubicaciones más buscadas
        $ubicaciones = DB::table('ubicaciones')
            ->join('propiedades', 'propiedades.ubicacion_id', '=', 'ubicaciones.id')
            ->select(
                'ubicaciones.id',
                'ubicaciones.nombre',
                DB::raw('SUM(propiedades.visitas) as total_vistas')
            )
            ->where('ubicaciones.is_active', 1)
            ->where('propiedades.is_active_publish', 1)
            ->groupBy('ubicaciones.id', 'ubicaciones.nombre')
            ->orderByDesc(DB::raw('SUM(propiedades.visitas)')) // más vistas primero
            ->limit(5) // 🔹 solo las 5 más vistas
            ->get();


        // Obtener operaciones (venta, alquiler, etc.)
        $operaciones = DB::table('operaciones')
            ->where('is_active', 1)
            ->select('id', 'nombre')
            ->orderBy('nombre')
            ->get();


        $propiedadesMasVistas = DB::table('propiedades')
            ->join('ubicaciones', 'ubicaciones.id', '=', 'propiedades.ubicacion_id')
            ->select(
                'propiedades.id',
                'propiedades.titulo',
                'ubicaciones.nombre as ubicacion'
            )
            ->where('propiedades.is_active_publish', 1)
            ->where('propiedades.is_active', 1)
            ->orderByDesc('propiedades.visitas')
            ->limit(5)
            ->get()
            ->map(function ($propiedad) {
                // Crear slug amigable del título
                $slugTitulo = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $propiedad->titulo)));

                // Crear slug amigable de la ubicación
                $slugUbicacion = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $propiedad->ubicacion)));

                // Crear URL completa con ambos slugs
                $propiedad->url = url("http://localhost:3000/anuncio/{$propiedad->id}-{$slugTitulo}-{$slugUbicacion}");

                return $propiedad;
            });



        // Construir dinámicamente las secciones por operación
        $menu = []; 

        foreach ($operaciones as $operacion) {
            $nombreOperacion = strtolower($operacion->nombre); // ejemplo: "comprar", "alquiler", etc.

            $menu[$nombreOperacion] = [
                'tipo' => $tiposPropiedad,
                'ciudad' => $ubicaciones,
                'propiedades_mas_vistas' => $propiedadesMasVistas,
            ];
        }

        // Agregar sección de servicios fija
        $menu['servicios'] = [
            ['nombre' => 'Publica tu inmueble', 'url' => 'https://aldasa.pe/publica-tu-aviso'],
            ['nombre' => 'Revista Aldasa', 'url' => 'https://aldasa.pe/revista-aldasa'],
        ];

        return response()->json($menu);
    }
}
