<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class AnunciosModel extends Model
{
    public static function tiposPropiedad()
    {
        return DB::select("SELECT * FROM tipos_propiedad WHERE is_active = 1 ORDER BY nombre ASC");
    }

    public static function tiposOperacion()
    {
        return DB::select("SELECT * FROM operaciones WHERE is_active = 1 ORDER BY nombre ASC");
    }

    public static function tiposUbicaciones()
    {
        return DB::select("SELECT * FROM ubicaciones WHERE is_active = 1 ORDER BY nombre ASC");
    }

    public static function crearAnuncio($data, $rutaImagen = null)
    {
        return DB::table('propiedades')->insertGetId([
            'tipo_id' => $data['tipo_id'],
            'operacion_id' => $data['operacion_id'],
            'ubicacion_id' => $data['ubicacion_id'],
            'titulo' => $data['titulo'],
            'descripcion' => $data['descripcion'],
            'precio' => $data['precio'],
            'imagen_principal' => $rutaImagen,
            'user_id' => $data['user_id'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public static function guardarCaracteristicas($propiedadId, $caracteristicas)
    {
        // Obtener todas las características actuales de la propiedad
        $caracActuales = DB::table('propiedad_caracteristicas')
            ->where('propiedad_id', $propiedadId)
            ->pluck('valor', 'caracteristica_id') // ['id_carac' => 'valor']
            ->toArray();

        // IDs de las características enviadas desde el frontend
        $idsEnviados = array_map(fn($c) => $c['id'], $caracteristicas);

        // 1️⃣ Insertar nuevas y actualizar existentes
        foreach ($caracteristicas as $carac) {
            $id = $carac['id'];
            $valor = $carac['valor'] ?? '';

            if (array_key_exists($id, $caracActuales)) {
                // Ya existe → actualizar valor si cambió
                if ($caracActuales[$id] !== $valor) {
                    DB::table('propiedad_caracteristicas')
                        ->where('propiedad_id', $propiedadId)
                        ->where('caracteristica_id', $id)
                        ->update([
                            'valor' => $valor,
                            'updated_at' => now(),
                        ]);
                }
            } else {
                // No existe → insertar
                DB::table('propiedad_caracteristicas')->insert([
                    'propiedad_id' => $propiedadId,
                    'caracteristica_id' => $id,
                    'valor' => $valor,
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 2️⃣ Eliminar las características que fueron desmarcadas
        $idsAEliminar = array_diff(array_keys($caracActuales), $idsEnviados);
        if (!empty($idsAEliminar)) {
            DB::table('propiedad_caracteristicas')
                ->where('propiedad_id', $propiedadId)
                ->whereIn('caracteristica_id', $idsAEliminar)
                ->delete();
        }
    }

    public static function guardarCaracteristicasSecundarias($propiedadId, $caracteristicas_secundarias)
    {
        // Obtener todas las amenities actuales de la propiedad
        $caracActuales = DB::table('propiedad_amenities')
            ->where('propiedad_id', $propiedadId)
            ->pluck('is_active', 'amenity_id') // ['id_amenity' => is_active]
            ->toArray();

        // IDs de las características enviadas desde el frontend
        $idsEnviados = array_map(fn($c) => $c['id'], $caracteristicas_secundarias);

        // 1️⃣ Insertar nuevas o reactivar existentes
        foreach ($caracteristicas_secundarias as $carac) {
            $id = $carac['id'];

            if (array_key_exists($id, $caracActuales)) {
                // Ya existe → reactivar si estaba inactiva
                if ($caracActuales[$id] == 0) {
                    DB::table('propiedad_amenities')
                        ->where('propiedad_id', $propiedadId)
                        ->where('amenity_id', $id)
                        ->update([
                            'is_active' => 1,
                            'updated_at' => now(),
                        ]);
                }
            } else {
                // No existe → insertar
                DB::table('propiedad_amenities')->insert([
                    'propiedad_id' => $propiedadId,
                    'amenity_id' => $id,
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 2️⃣ Desactivar las que fueron desmarcadas
        $idsAEliminar = array_diff(array_keys($caracActuales), $idsEnviados);
        if (!empty($idsAEliminar)) {
            DB::table('propiedad_amenities')
                ->where('propiedad_id', $propiedadId)
                ->whereIn('amenity_id', $idsAEliminar)
                ->update([
                    'is_active' => 0,
                    'updated_at' => now(),
                ]);
        }
    }




    public static function listaranuncio($idpublish, $id)
    {
        return DB::select("SELECT p.id, u.id as id_ubicacion, u.nombre as ubicacion, tp.id as id_tipopropiedad, tp.nombre as tipo_propiedad, o.id as id_operacion, o.nombre as operaciones, p.titulo, p.descripcion, p.precio, p.imagen_principal, p.is_active_publish FROM propiedades p INNER JOIN ubicaciones u ON p.ubicacion_id = u.id INNER JOIN tipos_propiedad tp ON p.tipo_id = tp.id INNER JOIN operaciones o ON p.operacion_id = o.id WHERE p.is_active = 1 AND p.is_active_publish = $idpublish AND p.user_id = $id ORDER BY p.id ASC");
    }

    public static function actualizarAnuncio($id, $data, $rutaImagen = null)
    {
        DB::table('propiedades')
            ->where('id', $id)
            ->update([
                'tipo_id' => $data['tipo_id'],
                'operacion_id' => $data['operacion_id'],
                'ubicacion_id' => $data['ubicacion_id'],
                'titulo' => $data['titulo'],
                'descripcion' => $data['descripcion'],
                'precio' => $data['precio'],
                'imagen_principal' => $rutaImagen,
                'updated_at' => now(),
            ]);
    }


    public static function categoriasCatalogo()
    {
        return DB::select("SELECT * FROM caracteristicas_catalogo WHERE is_active = 1 ORDER BY nombre ASC");
    }
    
    public static function categoriasCatalogoid($id)
    {
        return DB::select("SELECT * FROM propiedad_caracteristicas WHERE propiedad_id = $id AND is_active = 1");
    }


    public static function amenities()
    {
        return DB::select("SELECT * FROM amenities WHERE is_active = 1 ORDER BY nombre ASC");
    }
    
    public static function amenitiesid($id)
    {
        return DB::select("SELECT * FROM propiedad_amenities WHERE propiedad_id = $id AND is_active = 1");
    }



}
