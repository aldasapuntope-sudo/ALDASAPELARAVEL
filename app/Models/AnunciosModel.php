<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AnunciosModel extends Model
{

    

    public static function listarplanos($id)
    {
        return DB::select("SELECT * FROM propiedad_planos WHERE propiedad_id= $id AND is_active = 1 ORDER BY id ASC");
    }


    public static function eliminarplanos($id)
    {
        return DB::update("UPDATE propiedad_planos SET is_active = 0 WHERE id = ?", [$id]);
    }
    

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
            'direccion' => $data['direccion'],
            'descripcion' => $data['descripcion'],
            'precio' => $data['precio'],
            'imagen_principal' => $rutaImagen,
            'user_id' => $data['user_id'],
            
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public static function guardarPlanos($id, $titulo, $nombrePlano)
    {
        return DB::table('propiedad_planos')->insert([
                            'propiedad_id' => $id,
                            'titulo' => $titulo,
                            'imagen' => 'planos/' . $nombrePlano,
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




    /*public static function listaranuncio($idpublish, $id)
    {
        return DB::select("SELECT p.id, u.id as id_ubicacion, u.nombre as ubicacion, tp.id as id_tipopropiedad, tp.nombre as tipo_propiedad, o.id as id_operacion, o.nombre as operaciones, p.titulo, p.descripcion, p.precio, p.imagen_principal, p.is_active_publish FROM propiedades p INNER JOIN ubicaciones u ON p.ubicacion_id = u.id INNER JOIN tipos_propiedad tp ON p.tipo_id = tp.id INNER JOIN operaciones o ON p.operacion_id = o.id WHERE p.is_active = 1 AND p.is_active_publish = $idpublish AND p.user_id = $id ORDER BY p.id ASC");
    }*/
    public static function listaranuncio($idpublish, $id)
    {
        // Traer los anuncios base
        $anuncios = DB::select("
            SELECT 
                p.id, 
                u.id as id_ubicacion, 
                u.nombre as ubicacion, 
                tp.id as id_tipopropiedad, 
                tp.nombre as tipo_propiedad, 
                o.id as id_operacion, 
                o.nombre as operaciones, 
                p.titulo, 
                p.descripcion, 
                p.precio, 
                p.direccion, 
                p.imagen_principal, 
                p.is_active_publish 
            FROM propiedades p 
            INNER JOIN ubicaciones u ON p.ubicacion_id = u.id 
            INNER JOIN tipos_propiedad tp ON p.tipo_id = tp.id 
            INNER JOIN operaciones o ON p.operacion_id = o.id 
            WHERE p.is_active = 1 
            AND p.is_active_publish = $idpublish 
            AND p.user_id = $id 
            ORDER BY p.id DESC
        ");

        // Para cada anuncio, traer sus características principales y secundarias
        foreach ($anuncios as $anuncio) {
            // Características principales
            $anuncio->caracteristicas = DB::table('propiedad_caracteristicas as pc')
                ->join('caracteristicas_catalogo as cc', 'pc.caracteristica_id', '=', 'cc.id')
                ->select('cc.nombre', 'cc.icono', 'cc.unidad', 'pc.valor')
                ->where('pc.propiedad_id', $anuncio->id)
                ->get();

            // Características secundarias (amenities)
            $anuncio->amenities = DB::table('propiedad_amenities as pa')
                ->join('amenities as ac', 'pa.amenity_id', '=', 'ac.id')
                ->select('ac.nombre', 'ac.icon_url')
                ->where('pa.propiedad_id', $anuncio->id)
                ->get();

            $imagenPrincipal = collect();

            if (!empty($anuncio->imagen_principal)) {
                $imagenPrincipal->push((object)[
                    'id' => 0,
                    'titulo' => 'Imagen principal',
                    'imagen' => $anuncio->imagen_principal,
                ]);
            }

            // Otras imágenes
            $imagenesSecundarias = DB::table('propiedad_imagenes as img')
                ->select('img.id', 'img.titulo', 'img.imagen')
                ->where('img.propiedad_id', $anuncio->id)
                ->where('img.is_active', 1)
                ->get();

            // Unir principal + secundarias
            $anuncio->imagenes = $imagenPrincipal->merge($imagenesSecundarias);

            $anuncio->planos = DB::table('propiedad_planos as pp')
                ->select('pp.id', 'pp.titulo', 'pp.imagen')
                ->where('pp.propiedad_id', $anuncio->id)
                ->where('pp.is_active', 1)
                ->get()
                ->map(function ($plano) {
                    $plano->caracteristicas = DB::table('plano_caracteristicas as pc')
                        ->select('pc.nombre', 'pc.valor', 'pc.icono')
                        ->where('pc.plano_id', $plano->id)
                        ->where('pc.is_active', 1)
                        ->get();
                    return $plano;
                });

            
            $anuncio->videos = DB::table('propiedad_videos as pv')
                ->select('pv.id', 'pv.titulo', 'pv.url', 'pv.tipo')
                ->where('pv.propiedad_id', $anuncio->id)
                ->where('pv.is_active', 1)
                ->get();
        }

        return $anuncios;
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
                'direccion' => $data['direccion'],
                'is_active_publish' => 0,
                'updated_at' => now(),
            ]);
    }


    public static function categoriasCatalogo($tpropiedad)
    {
        return DB::select("SELECT * FROM caracteristicas_catalogo WHERE tpropiedad_id = $tpropiedad AND is_active = 1 ORDER BY nombre ASC");
    }
    
    public static function categoriasCatalogoid($id)
    {
        return DB::select("SELECT pc.id, pc.caracteristica_id, pc.valor, cc.nombre, cc.icono, pc.is_active FROM propiedad_caracteristicas pc INNER JOIN caracteristicas_catalogo cc on pc.caracteristica_id = cc.id WHERE pc.propiedad_id = $id AND pc.is_active = 1");
    }


    public static function amenities($tpropiedad)
    {
        return DB::select("SELECT * FROM amenities WHERE tpropiedad_id = $tpropiedad AND  is_active = 1 ORDER BY nombre ASC");
    }
    
    public static function amenitiesid($id)
    {
        return DB::select("SELECT pa.id, pa.amenity_id, a.nombre, a.icon_url, pa.is_active FROM propiedad_amenities pa INNER JOIN amenities a on pa.amenity_id = a.id WHERE pa.propiedad_id = $id AND pa.is_active = 1");
    }


    //PAGINA PRINCIPAL
    public static function listaranuncioprincipal($idpublish)
    {
        // Traer los anuncios base
        $anuncios = DB::select("
            SELECT 
                p.id, 
                u.id as id_ubicacion, 
                u.nombre as ubicacion, 
                tp.id as id_tipopropiedad, 
                tp.nombre as tipo_propiedad, 
                o.id as id_operacion, 
                o.nombre as operaciones, 
                p.titulo, 
                p.descripcion, 
                p.precio, 
                p.direccion, 
                p.imagen_principal, 
                p.is_active_publish 
            FROM propiedades p 
            INNER JOIN ubicaciones u ON p.ubicacion_id = u.id 
            INNER JOIN tipos_propiedad tp ON p.tipo_id = tp.id 
            INNER JOIN operaciones o ON p.operacion_id = o.id 
            WHERE p.is_active = 1 
            AND p.is_active_publish = $idpublish 
            ORDER BY p.id ASC
        ");

        // Para cada anuncio, traer sus características principales y secundarias
        foreach ($anuncios as $anuncio) {
            // Características principales
            $anuncio->caracteristicas = DB::table('propiedad_caracteristicas as pc')
                ->join('caracteristicas_catalogo as cc', 'pc.caracteristica_id', '=', 'cc.id')
                ->select('cc.nombre', 'cc.icono', 'cc.unidad', 'pc.valor')
                ->where('pc.propiedad_id', $anuncio->id)
                ->get();

            // Características secundarias (amenities)
            $anuncio->amenities = DB::table('propiedad_amenities as pa')
                ->join('amenities as ac', 'pa.amenity_id', '=', 'ac.id')
                ->select('ac.nombre', 'ac.icon_url')
                ->where('pa.propiedad_id', $anuncio->id)
                ->get();
        }


        return $anuncios;
    }


    public static function listardetalleprincipal($idpublish)
    {
        // Traer los anuncios base
        $anuncios = DB::select("
            SELECT 
                p.id, 
                u.id as id_ubicacion, 
                u.nombre as ubicacion, 
                tp.id as id_tipopropiedad, 
                tp.nombre as tipo_propiedad, 
                o.id as id_operacion, 
                o.nombre as operaciones, 
                p.titulo, 
                p.descripcion, 
                p.precio, 
                p.direccion, 
                p.imagen_principal, 
                p.is_active_publish,
                p.created_at
            FROM propiedades p 
            INNER JOIN ubicaciones u ON p.ubicacion_id = u.id 
            INNER JOIN tipos_propiedad tp ON p.tipo_id = tp.id 
            INNER JOIN operaciones o ON p.operacion_id = o.id 
            WHERE p.is_active = 1 
            AND p.is_active_publish = 1
            AND p.id = $idpublish 
            ORDER BY p.id ASC
        ");

        // Para cada anuncio, traer sus características principales y secundarias
        foreach ($anuncios as $anuncio) {
            // Características principales
            $anuncio->caracteristicas = DB::table('propiedad_caracteristicas as pc')
                ->join('caracteristicas_catalogo as cc', 'pc.caracteristica_id', '=', 'cc.id')
                ->select('cc.nombre', 'cc.icono', 'cc.unidad', 'pc.valor')
                ->where('pc.propiedad_id', $anuncio->id)
                ->get();

            // Características secundarias (amenities)
            $anuncio->amenities = DB::table('propiedad_amenities as pa')
                ->join('amenities as ac', 'pa.amenity_id', '=', 'ac.id')
                ->select('ac.nombre', 'ac.icon_url')
                ->where('pa.propiedad_id', $anuncio->id)
                ->where('pa.is_active', 1)
                ->get();

            $imagenPrincipal = collect();

            if (!empty($anuncio->imagen_principal)) {
                $imagenPrincipal->push((object)[
                    'id' => 0,
                    'titulo' => 'Imagen principal',
                    'imagen' => $anuncio->imagen_principal,
                ]);
            }

            // Otras imágenes
            $imagenesSecundarias = DB::table('propiedad_imagenes as img')
                ->select('img.id', 'img.titulo', 'img.imagen')
                ->where('img.propiedad_id', $anuncio->id)
                ->where('img.is_active', 1)
                ->get();

            // Unir principal + secundarias
            $anuncio->imagenes = $imagenPrincipal->merge($imagenesSecundarias);

            $anuncio->planos = DB::table('propiedad_planos as pp')
                ->select('pp.id', 'pp.titulo', 'pp.imagen')
                ->where('pp.propiedad_id', $anuncio->id)
                ->where('pp.is_active', 1)
                ->get()
                ->map(function ($plano) {
                    $plano->caracteristicas = DB::table('plano_caracteristicas as pc')
                        ->select('pc.nombre', 'pc.valor', 'pc.icono')
                        ->where('pc.plano_id', $plano->id)
                        ->where('pc.is_active', 1)
                        ->get();
                    return $plano;
                });

            
            $anuncio->videos = DB::table('propiedad_videos as pv')
                ->select('pv.id', 'pv.titulo', 'pv.url', 'pv.tipo')
                ->where('pv.propiedad_id', $anuncio->id)
                ->where('pv.is_active', 1)
                ->get();

            $anuncio->imagen360 = DB::table('propiedad_imagenes360 as pimg')
                ->select('pimg.id', 'pimg.titulo', 'pimg.imagen')
                ->where('pimg.propiedad_id', $anuncio->id)
                ->where('pimg.is_active', 1)
                ->get();
        }

        
        return $anuncios;
    }

}
