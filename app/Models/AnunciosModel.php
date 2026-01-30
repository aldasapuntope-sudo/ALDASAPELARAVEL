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

    protected $table = 'propiedades'; // 👈 Aquí le indicas a qué tabla apunta
    protected $fillable = [
        'user_id',
        'tipo_id',
        'operacion_id',
        'ubicacion_id',
        'titulo',
        'direccion',
        'descripcion',
        'precio',
        'dormitorios',
        'banos',
        'area',
        'imagen_principal',
        'is_active_publish',
        'visitas',
        'is_active',
    ];


    public static function getmonedas()
    {
        return DB::select("SELECT * FROM monedas WHERE is_active = 1 ORDER BY id ASC");
    }

    public static function getMensajeanuncio($userId)
    {
        return DB::select("
            SELECT mc.*, p.titulo AS propiedad_titulo, p.imagen_principal AS propiedad_imagen
            FROM mensajes_contacto mc
            INNER JOIN propiedades p ON mc.anuncio_id = p.id
            WHERE p.user_id = ? AND mc.is_active = 1
            ORDER BY mc.id DESC
        ", [$userId]);
    }


    public static function getMensajesoporte($userId)
    {
        return DB::select("
            SELECT * FROM soporte_tickets WHERE user_id = ?
            ORDER BY id DESC
        ", [$userId]);
    }

    

    public static function getanunciosFavoritos($userId)
    {
        return DB::select("
            SELECT 
                f.id AS favorito_id,
                p.id,
                p.titulo AS propiedad_titulo,
                p.imagen_principal AS propiedad_imagen,
                p.direccion AS ubicacion,
                p.precio,
                p.area,
                p.dormitorios,
                p.banos,
                p.is_active_publish,
                p.is_active,
                p.created_at,
                p.visitas,
                ubi.nombre as ubicacion
            FROM favoritos f
            INNER JOIN propiedades p ON f.anuncio_id = p.id
            INNER JOIN ubicaciones ubi ON p.ubicacion_id = ubi.id
            WHERE f.usuario_id = ?
            ORDER BY f.id DESC
        ", [$userId]);
    }




    public static function sumarVisita($propiedadId, $userId = null)
    {
        $propiedad = self::find($propiedadId);

        if (!$propiedad) {
            return [
                'success' => false,
                'message' => 'Propiedad no encontrada'
            ];
        }

        DB::beginTransaction();

        try {
            // 1️⃣ Incrementar contador
            $propiedad->increment('visitas');

            // 2️⃣ Registrar historial si hay usuario
            if ($userId) {
                DB::table('propiedad_visitas')->updateOrInsert(
                    [
                        'user_id' => $userId,
                        'propiedad_id' => $propiedadId,
                    ],
                    [
                        'created_at' => now()
                    ]
                );
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Visita registrada correctamente',
                'visitas' => $propiedad->visitas + 1
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'message' => 'Error al registrar visita',
                'error' => $e->getMessage()
            ];
        }
    }

    public static function listarplanos($id)
    {
        return DB::select("SELECT * FROM propiedad_planos WHERE propiedad_id= $id AND is_active = 1 ORDER BY id ASC");
    }

    public static function listaimgsecundarias($id)
    {
        return DB::select("SELECT * FROM propiedad_imagenes WHERE propiedad_id= $id AND is_active = 1 ORDER BY id ASC");
    }

    

    


    


    public static function eliminarplanos($id)
    {
        return DB::update("UPDATE propiedad_planos SET is_active = 0 WHERE id = ?", [$id]);
    }

    public static function eliminarimgsecundarias($id)
    {
        return DB::update("UPDATE propiedad_imagenes SET is_active = 0 WHERE id = ?", [$id]);
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
            'moneda_id' => $data['moneda_id'],
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

    public static function guardarImagenes($idPropiedad, $titulo, $nombreArchivo)
    {
        return DB::table('propiedad_imagenes')->insert([
            'propiedad_id' => $idPropiedad,
            'titulo' => $titulo,
            'imagen' => 'propiedades_imagenes/' . $nombreArchivo,
            'is_active' => 1,
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


    public static function guardarvideourl($idPropiedad, $url)
    {
        return DB::table('propiedad_videos')->insert([
            'propiedad_id' => $idPropiedad,
            'titulo' => '',
            'url' => $url,
            'tipo' => 'youtube',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }




    /*public static function listaranuncio($idpublish, $id)
    {
        return DB::select("SELECT p.id, u.id as id_ubicacion, u.nombre as ubicacion, tp.id as id_tipopropiedad, tp.nombre as tipo_propiedad, o.id as id_operacion, o.nombre as operaciones, p.titulo, p.descripcion, p.precio, p.imagen_principal, p.is_active_publish FROM propiedades p INNER JOIN ubicaciones u ON p.ubicacion_id = u.id INNER JOIN tipos_propiedad tp ON p.tipo_id = tp.id INNER JOIN operaciones o ON p.operacion_id = o.id WHERE p.is_active = 1 AND p.is_active_publish = $idpublish AND p.user_id = $id ORDER BY p.id ASC");
    }*/
    /*public static function listaranuncio($idpublish, $id)
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
                m.simbolo AS moneda_simbolo,
                p.moneda_id,
                p.direccion,
                p.imagen_principal, 
                p.visitas,
                p.is_active_publish
            FROM propiedades p 
            INNER JOIN ubicaciones u ON p.ubicacion_id = u.id 
            INNER JOIN tipos_propiedad tp ON p.tipo_id = tp.id 
            INNER JOIN operaciones o ON p.operacion_id = o.id 
            INNER JOIN monedas m ON p.moneda_id = m.id
            WHERE p.is_active = 1 
            AND p.is_active_publish = $idpublish 
            AND p.user_id = '$id' 
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
                ->select('pv.url')
                ->where('pv.propiedad_id', $anuncio->id)
                ->where('pv.is_active', 1)
                ->get();
        }

        return $anuncios;
    }*/

    public static function listaranuncio($idpublish, $id)
    {
        $id = (int) $id;
        
        $query = DB::table('propiedades as p')
            ->join('ubicaciones as u', 'p.ubicacion_id', '=', 'u.id')
            ->join('tipos_propiedad as tp', 'p.tipo_id', '=', 'tp.id')
            ->join('operaciones as o', 'p.operacion_id', '=', 'o.id')
            ->join('monedas as m', 'p.moneda_id', '=', 'm.id')
            ->select(
                'p.id',
                'u.id as id_ubicacion',
                'u.nombre as ubicacion',
                'tp.id as id_tipopropiedad',
                'tp.nombre as tipo_propiedad',
                'o.id as id_operacion',
                'o.nombre as operaciones',
                'p.titulo',
                'p.descripcion',
                'p.precio',
                'm.simbolo as moneda_simbolo',
                'p.moneda_id',
                'p.direccion',
                'p.imagen_principal',
                'p.visitas',
                'p.is_active_publish',
                'p.user_id'
            )
            ->where('p.is_active', 1)
            ->where('p.is_active_publish', $idpublish);

        // 👑 SOLO si NO es admin, filtrar por usuario
        if ($id !== 0) {
            $query->where('p.user_id', $id);
        }

        $anuncios = $query->orderByDesc('p.id')->get();

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
                ->select('pv.url')
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
                'moneda_id' => $data['moneda_id'],
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
                m.simbolo AS moneda_simbolo,
                p.direccion, 
                p.imagen_principal, 
                p.is_active_publish 
            FROM propiedades p 
            INNER JOIN ubicaciones u ON p.ubicacion_id = u.id 
            INNER JOIN tipos_propiedad tp ON p.tipo_id = tp.id 
            INNER JOIN operaciones o ON p.operacion_id = o.id 
            INNER JOIN monedas m ON p.moneda_id = m.id
            WHERE p.is_active = 1 
            AND p.is_active_publish = $idpublish 
            ORDER BY p.id ASC
        ");

        // Para cada anuncio, traer sus características principales y secundarias
        foreach ($anuncios as $anuncio) {

            $perfil = DB::table('usuario as usu')
                ->join('propiedades as p', 'p.user_id', '=', 'usu.id')
                ->select('usu.id', 'usu.nombre', 'usu.apellido', 'usu.email', 'usu.telefono', 'usu.telefono_movil', 'usu.imagen')
                ->where('p.id', $anuncio->id)
                ->where('p.is_active', 1)
                ->first();

            if ($perfil) {
                $perfil->idanunciante = $perfil->id;
                unset($perfil->id); // opcional: ocultar el ID real
            }

            $anuncio->perfilanunciante = $perfil;
            
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
                m.nombre AS moneda_nombre,
                m.simbolo AS moneda_simbolo,
                m.codigo AS moneda_codigo,
                p.direccion, 
                p.imagen_principal, 
                p.is_active_publish,
                p.visitas,
                p.created_at
            FROM propiedades p 
            INNER JOIN ubicaciones u ON p.ubicacion_id = u.id 
            INNER JOIN tipos_propiedad tp ON p.tipo_id = tp.id 
            INNER JOIN operaciones o ON p.operacion_id = o.id 
            INNER JOIN monedas m ON p.moneda_id = m.id
            WHERE p.is_active = 1 
            AND p.is_active_publish = 1
            AND p.id = $idpublish 
            ORDER BY p.id ASC
        ");

        // Para cada anuncio, traer sus características principales y secundarias
        foreach ($anuncios as $anuncio) {

            $perfil = DB::table('usuario as usu')
                ->join('propiedades as p', 'p.user_id', '=', 'usu.id')
                ->select('usu.id', 'usu.nombre', 'usu.apellido', 'usu.email', 'usu.telefono', 'usu.telefono_movil', 'usu.imagen')
                ->where('p.id', $anuncio->id)
                ->where('p.is_active', 1)
                ->first();

            if ($perfil) {
                $perfil->idanunciante = $perfil->id;
                unset($perfil->id); // opcional: ocultar el ID real
            }

            $anuncio->perfilanunciante = $perfil;

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

    //PARTE DE MENSAES DE LA PAGINA PRINCIPAL DETALLE AL ANUNCIANTE
    public static function guardarMensaje($nombre, $email, $telefono, $dni, $mensaje, $anuncio_id)
    {
        $data = [
            'nombre' => $nombre,
            'email' => $email,
            'mensaje' => $mensaje,
            'telefono' => $telefono,
            'dni' => $dni,
            'anuncio_id' => $anuncio_id,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('mensajes_contacto')->insert($data);

        return $data;
    }


    public static function getpagina($id)
    {
        return DB::select("SELECT * FROM paginas WHERE slug = '$id' AND is_active = 1 ORDER BY id ASC");
    }

    public static function guardarFavorito($usuario_id, $anuncio_id)
    {
        // Verificar si ya existe ese favorito
        $existe = DB::table('favoritos')
            ->where('usuario_id', $usuario_id)
            ->where('anuncio_id', $anuncio_id)
            ->first();

        if ($existe) {
            throw new \Exception('Ya está agregado a favoritos.');
        }

        // Insertar nuevo favorito
        $data = [
            'usuario_id' => $usuario_id,
            'anuncio_id' => $anuncio_id,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('favoritos')->insert($data);

        return $data;
    }

    public static function actualizarEstadoVendido($id, $estado)
    {
        return DB::table('propiedades')
            ->where('id', $id)
            ->update([
                'is_active_publish' => $estado,
                'updated_at' => now()
            ]);
    }





    //MI PROYECTO INVERSION

    public static function getMisProyectos()
    {
        return DB::select("
            SELECT 
                p.id,
                p.titulo,
                p.descripcion,
                p.ubicacion,
                p.porcentaje_avance,
                p.imagen_principal,
                p.is_active,
                u.name AS administrador
            FROM proyectos_inversion p
            INNER JOIN users u ON u.id = p.user_id
            WHERE p.is_active = 1
            ORDER BY p.id DESC
        ");
    }

    /**
     * Obtener el proyecto permitido para un usuario inversionista
     */
    public static function getProyectoPermitido($userId)
    {
        $fila = DB::selectOne("
            SELECT proyecto_id, estado
            FROM proyecto_inversionistas
            WHERE interesado_id = ?
            AND estado = 'aceptado'
            AND is_active = 1
            LIMIT 1
        ", [$userId]);

        if ($fila) {
            return $fila; // { proyecto_id: X, estado: "aceptado" }
        }

        return (object)[
            'proyecto_id' => null,
            'estado' => null
        ];
    }


    public static function listarDetalleProyecto($id)
    {
        // 📌 1. Traer información principal del proyecto
        $proyectos = DB::select("
            SELECT 
                p.id,
                p.user_id,
                u.nombre AS creador_nombre,
                u.apellido AS creador_apellido,
                u.email AS creador_email,
                u.telefono AS creador_telefono,
                p.titulo,
                p.descripcion,
                p.ubicacion,
                p.porcentaje_avance,
                p.imagen_principal,
                p.is_active,
                p.created_at
            FROM proyectos_inversion p
            INNER JOIN usuario u ON u.id = p.user_id
            WHERE p.id = $id
            LIMIT 1
        ");

        if (empty($proyectos)) {
            return null;
        }

        $proyecto = $proyectos[0];

        // 📌 2. Características
        $proyecto->caracteristicas = DB::table('proyecto_caracteristicas')
            ->select('id', 'titulo', 'descripcion')
            ->where('proyecto_id', $id)
            ->where('is_active', 1)
            ->get();

        // 📌 3. Multimedia (galería)
        $proyecto->multimedia = DB::table('proyecto_multimedia')
            ->select('id', 'tipo', 'archivo')
            ->where('proyecto_id', $id)
            ->where('is_active', 1)
            ->get();

        // 📌 4. Imagen Principal + Galería unificada
        $imagenes = collect();

        if (!empty($proyecto->imagen_principal)) {
            $imagenes->push((object)[
                'id' => 0,
                'titulo' => 'Imagen principal',
                'archivo' => $proyecto->imagen_principal,
                'tipo' => 'imagen'
            ]);
        }

        $imagenesGaleria = DB::table('proyecto_multimedia')
            ->where('proyecto_id', $id)
            ->where('tipo', 'imagen')
            ->where('is_active', 1)
            ->select('id', 'archivo', 'tipo')
            ->get();

        $proyecto->imagenes = $imagenes->merge($imagenesGaleria);

        // 📌 5. Videos
        $proyecto->videos = DB::table('proyecto_multimedia')
            ->where('proyecto_id', $id)
            ->where('tipo', 'video')
            ->where('is_active', 1)
            ->select('id', 'archivo', 'tipo')
            ->get();

        // 📌 6. Inversionistas asignados
        $proyecto->inversionistas = DB::table('proyecto_inversionistas AS pi')
            ->join('usuario AS u', 'pi.interesado_id', '=', 'u.id')
            ->select(
                'pi.id',
                'pi.interesado_id',
                'pi.estado',
                'u.nombre',
                'u.apellido',
                'u.email',
                'u.telefono'
            )
            ->where('pi.proyecto_id', $id)
            ->where('pi.is_active', 1)
            ->get();

        // 📌 7. Etapas del proyecto (ordenadas correctamente)
        $proyecto->etapas = DB::table('proyecto_etapas')
            ->select(
                'id',
                'nombre',
                'descripcion',
                'orden',
                'completado',
                'fecha_completado'
            )
            ->where('proyecto_id', $id)
            ->where('is_active', 1)
            ->orderBy('orden', 'asc')
            ->get();

        return $proyecto;
    }


    public static function eliminarmultimedia($id)
    {
        return DB::update("UPDATE proyecto_multimedia SET is_active = 0 WHERE id = ?", [$id]);
    }

    public static function eliminaretapas($id)
    {
        return DB::update("UPDATE proyecto_etapas SET is_active = 0 WHERE id = ?", [$id]);
    }

    public static function eliminarcaracteristicas($id)
    {
        return DB::update("UPDATE proyecto_caracteristicas SET is_active = 0 WHERE id = ?", [$id]);
    }

    public static function eliminarinversionista($id)
    {
        return DB::update("UPDATE proyecto_inversionistas SET is_active = 0 WHERE id = ?", [$id]);
    }


    public static function actualizarProyecto($id, $data, $rutaImagen = null)
    {
        $updateData = [
            'titulo' => $data['titulo'],
            'descripcion' => $data['descripcion'],
            'ubicacion' => $data['ubicacion'],
            'updated_at' => now(),
        ];

        if ($rutaImagen !== null) {
            $updateData['imagen_principal'] = $rutaImagen;
        }

        DB::table('proyectos_inversion')
            ->where('id', $id)
            ->update($updateData);
    }


    public static function guardarInversionistas($proyectoId, $inversionistas)
    {
        // Obtener inversionistas actuales de este proyecto
        $actuales = DB::table('proyecto_inversionistas')
            ->where('proyecto_id', $proyectoId)
            ->pluck('interesado_id', 'id') // [ id_registro => interesado_id ]
            ->toArray();

        // IDs actuales de registros
        $idsActuales = array_keys($actuales);

        // IDs enviados desde el front (id del registro, no del usuario)
        $idsEnviados = [];

        foreach ($inversionistas as $inv) {

            $registroId = $inv['id'] ?? null;             // id de la tabla proyecto_inversionistas
            $usuarioId  = $inv['interesado_id'] ?? null;  // id del usuario
            $estado     = $inv['estado'];

            // Guardar ids enviados para luego borrar los no enviados
            if ($registroId) {
                $idsEnviados[] = $registroId;
            }

            // 🔹 1. ACTUALIZAR registro existente
            if ($registroId && in_array($registroId, $idsActuales)) {

                DB::table('proyecto_inversionistas')
                    ->where('id', $registroId)
                    ->update([
                        'interesado_id' => $usuarioId,
                        'estado' => $estado,
                        'updated_at' => now(),
                    ]);

                continue;
            }

            // 🔹 2. INSERTAR si es nuevo
            if (!$registroId) {
                DB::table('proyecto_inversionistas')->insert([
                    'proyecto_id' => $proyectoId,
                    'interesado_id' => $usuarioId,
                    'estado' => $estado,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 🔹 3. ELIMINAR SOLO LOS QUE YA NO VIENEN DEL FRONT
        $idsAEliminar = array_diff($idsActuales, $idsEnviados);

        if (!empty($idsAEliminar)) {
            DB::table('proyecto_inversionistas')
                ->whereIn('id', $idsAEliminar)
                ->delete();
        }
    }

    public static function guardarCaracteristicasproyecto($proyectoId, $caracteristicas) {
        // Obtener registros actuales
        $actuales = DB::table('proyecto_caracteristicas')
            ->where('proyecto_id', $proyectoId)
            ->pluck('id')   // Solo ids
            ->toArray();

        $idsActuales = $actuales;
        $idsEnviados = [];

        foreach ($caracteristicas as $carac) {

            $registroId   = $carac['id'] ?? null;
            $titulo       = $carac['titulo'] ?? null;
            $descripcion  = $carac['descripcion'] ?? null;

            // Guardar ids enviados
            if ($registroId) {
                $idsEnviados[] = $registroId;
            }

            /** 🔹 1. ACTUALIZAR */
            if ($registroId && in_array($registroId, $idsActuales)) {
                DB::table('proyecto_caracteristicas')
                    ->where('id', $registroId)
                    ->update([
                        'titulo' => $titulo,
                        'descripcion' => $descripcion,
                        'updated_at' => now(),
                    ]);

                continue;
            }

            /** 🔹 2. INSERTAR */
            if (!$registroId) {
                DB::table('proyecto_caracteristicas')->insert([
                    'proyecto_id' => $proyectoId,
                    'titulo' => $titulo,
                    'descripcion' => $descripcion,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        /** 🔹 3. ELIMINAR LOS QUE YA NO VIENEN DESDE EL FRONT */
        $idsAEliminar = array_diff($idsActuales, $idsEnviados);

        if (!empty($idsAEliminar)) {
            DB::table('proyecto_caracteristicas')
                ->whereIn('id', $idsAEliminar)
                ->delete();
        }
    }

    public static function guardarEtapasProyecto($proyectoId, $etapas)
    {
        // Obtener registros actuales (solo IDs)
        $actuales = DB::table('proyecto_etapas')
            ->where('proyecto_id', $proyectoId)
            ->pluck('id')
            ->toArray();

        $idsActuales = $actuales;
        $idsEnviados = [];

        foreach ($etapas as $etapa) {

            $registroId        = $etapa['id'] ?? null;
            $nombre            = $etapa['nombre'] ?? null;
            $descripcion       = $etapa['descripcion'] ?? null;
            $orden             = $etapa['orden'] ?? 1;
            $completado        = $etapa['completado'] ?? 0;
            $fechaCompletado   = $etapa['fecha_completado'] ?? null;

            // Guardar IDs enviados
            if ($registroId) {
                $idsEnviados[] = $registroId;
            }

            /** 🔹 1. ACTUALIZAR */
            if ($registroId && in_array($registroId, $idsActuales)) {

                DB::table('proyecto_etapas')
                    ->where('id', $registroId)
                    ->update([
                        'nombre'            => $nombre,
                        'descripcion'       => $descripcion,
                        'orden'             => $orden,
                        'completado'        => $completado,
                        'fecha_completado'  => $fechaCompletado,
                        'updated_at'        => now(),
                    ]);

                continue;
            }

            /** 🔹 2. INSERTAR */
            if (!$registroId) {
                DB::table('proyecto_etapas')->insert([
                    'proyecto_id'       => $proyectoId,
                    'nombre'            => $nombre,
                    'descripcion'       => $descripcion,
                    'orden'             => $orden,
                    'completado'        => $completado,
                    'fecha_completado'  => $fechaCompletado,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);
            }
        }

        /** 🔹 3. ELIMINAR LOS QUE YA NO VIENEN DEL FRONT */
        $idsAEliminar = array_diff($idsActuales, $idsEnviados);

        if (!empty($idsAEliminar)) {
            DB::table('proyecto_etapas')
                ->whereIn('id', $idsAEliminar)
                ->delete();
        }
    }


    public static function guardarMultimediaProyecto($proyectoId, $items)
    {
        $actuales = DB::table('proyecto_multimedia')
            ->where('proyecto_id', $proyectoId)
            ->pluck('id')
            ->toArray();

        $idsActuales = $actuales;
        $idsEnviados = [];

        foreach ($items as $item) {

            $registroId = $item['id'] ?? null;
            $tipo       = $item['tipo'];
            $archivo    = $item['archivo']; // ya es la ruta final o url

            // registrar ids enviados
            if ($registroId) {
                $idsEnviados[] = $registroId;
            }

            /** 🔹 UPDATE */
            if ($registroId && in_array($registroId, $idsActuales)) {
                DB::table('proyecto_multimedia')
                    ->where('id', $registroId)
                    ->update([
                        'tipo'     => $tipo,
                        'archivo'  => $archivo,
                        'updated_at' => now(),
                    ]);
                continue;
            }

            /** 🔹 INSERT */
            if (!$registroId) {
                DB::table('proyecto_multimedia')->insert([
                    'proyecto_id' => $proyectoId,
                    'tipo'        => $tipo,
                    'archivo'     => $archivo,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
        }

        /** 🔹 ELIMINAR LOS QUE YA NO VIENEN */
        $idsAEliminar = array_diff($idsActuales, $idsEnviados);

        if (!empty($idsAEliminar)) {
            DB::table('proyecto_multimedia')
                ->whereIn('id', $idsAEliminar)
                ->delete();
        }
    }



    //CRUD ALDASA CLUB

    public static function listardetalleprincipalclub($idpublish)
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
                m.nombre AS moneda_nombre,
                m.simbolo AS moneda_simbolo,
                m.codigo AS moneda_codigo,
                p.direccion, 
                p.imagen_principal, 
                p.is_active_publish,
                p.visitas,
                p.created_at
            FROM propiedadesclub p 
            INNER JOIN ubicaciones u ON p.ubicacion_id = u.id 
            INNER JOIN tipos_propiedad tp ON p.tipo_id = tp.id 
            INNER JOIN operaciones o ON p.operacion_id = o.id 
            INNER JOIN monedas m ON p.moneda_id = m.id
            WHERE p.is_active = 1 
            AND p.is_active_publish = 1
            AND p.id = $idpublish 
            ORDER BY p.id ASC
        ");

        // Para cada anuncio, traer sus características principales y secundarias
        foreach ($anuncios as $anuncio) {

            $perfil = DB::table('usuario as usu')
                ->join('propiedadesclub as p', 'p.user_id', '=', 'usu.id')
                ->select('usu.id', 'usu.nombre', 'usu.apellido', 'usu.email', 'usu.telefono', 'usu.telefono_movil', 'usu.imagen')
                ->where('p.id', $anuncio->id)
                ->where('p.is_active', 1)
                ->first();

            if ($perfil) {
                $perfil->idanunciante = $perfil->id;
                unset($perfil->id); // opcional: ocultar el ID real
            }

            $anuncio->perfilanunciante = $perfil;

            // Características principales
            $anuncio->caracteristicas = DB::table('propiedad_caracteristicasclub as pc')
                ->join('caracteristicas_catalogoclub as cc', 'pc.caracteristica_id', '=', 'cc.id')
                ->select('cc.nombre', 'cc.icono', 'cc.unidad', 'pc.valor')
                ->where('pc.propiedad_id', $anuncio->id)
                ->get();

            // Características secundarias (amenities)
            $anuncio->amenities = DB::table('propiedad_amenitiesclub as pa')
                ->join('amenitiesclub as ac', 'pa.amenity_id', '=', 'ac.id')
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
            $imagenesSecundarias = DB::table('propiedad_imagenesclub as img')
                ->select('img.id', 'img.titulo', 'img.imagen')
                ->where('img.propiedad_id', $anuncio->id)
                ->where('img.is_active', 1)
                ->get();

            // Unir principal + secundarias
            $anuncio->imagenes = $imagenPrincipal->merge($imagenesSecundarias);

            $anuncio->planos = DB::table('propiedad_planosclub as pp')
                ->select('pp.id', 'pp.titulo', 'pp.imagen')
                ->where('pp.propiedad_id', $anuncio->id)
                ->where('pp.is_active', 1)
                ->get()
                ->map(function ($plano) {
                    $plano->caracteristicas = DB::table('plano_caracteristicasclub as pc')
                        ->select('pc.nombre', 'pc.valor', 'pc.icono')
                        ->where('pc.plano_id', $plano->id)
                        ->where('pc.is_active', 1)
                        ->get();
                    return $plano;
                });

            
            $anuncio->videos = DB::table('propiedad_videosclub as pv')
                ->select('pv.id', 'pv.titulo', 'pv.url', 'pv.tipo')
                ->where('pv.propiedad_id', $anuncio->id)
                ->where('pv.is_active', 1)
                ->get();

            $anuncio->imagen360 = DB::table('propiedad_imagenes360club as pimg')
                ->select('pimg.id', 'pimg.titulo', 'pimg.imagen')
                ->where('pimg.propiedad_id', $anuncio->id)
                ->where('pimg.is_active', 1)
                ->get();
        }

        
        return $anuncios;
    }


    public static function listaranuncioaldasaclub($idpublish, $id)
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
                m.simbolo AS moneda_simbolo,
                p.moneda_id,
                p.direccion,
                p.imagen_principal, 
                p.visitas,
                p.is_active_publish
            FROM propiedadesclub p 
            INNER JOIN ubicaciones u ON p.ubicacion_id = u.id 
            INNER JOIN tipos_propiedad tp ON p.tipo_id = tp.id 
            INNER JOIN operaciones o ON p.operacion_id = o.id 
            INNER JOIN monedas m ON p.moneda_id = m.id
            WHERE p.is_active = 1 
            AND p.is_active_publish = $idpublish 
            ORDER BY p.id DESC
        ");

        // Para cada anuncio, traer sus características principales y secundarias
        foreach ($anuncios as $anuncio) {
            // Características principales
            $anuncio->caracteristicas = DB::table('propiedad_caracteristicasclub as pc')
                ->join('caracteristicas_catalogoclub as cc', 'pc.caracteristica_id', '=', 'cc.id')
                ->select('cc.nombre', 'cc.icono', 'cc.unidad', 'pc.valor')
                ->where('pc.propiedad_id', $anuncio->id)
                ->get();

            // Características secundarias (amenities)
            $anuncio->amenities = DB::table('propiedad_amenitiesclub as pa')
                ->join('amenitiesclub as ac', 'pa.amenity_id', '=', 'ac.id')
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
            $imagenesSecundarias = DB::table('propiedad_imagenesclub as img')
                ->select('img.id', 'img.titulo', 'img.imagen')
                ->where('img.propiedad_id', $anuncio->id)
                ->where('img.is_active', 1)
                ->get();

            // Unir principal + secundarias
            $anuncio->imagenes = $imagenPrincipal->merge($imagenesSecundarias);

            $anuncio->planos = DB::table('propiedad_planosclub as pp')
                ->select('pp.id', 'pp.titulo', 'pp.imagen')
                ->where('pp.propiedad_id', $anuncio->id)
                ->where('pp.is_active', 1)
                ->get()
                ->map(function ($plano) {
                    $plano->caracteristicas = DB::table('plano_caracteristicasclub as pc')
                        ->select('pc.nombre', 'pc.valor', 'pc.icono')
                        ->where('pc.plano_id', $plano->id)
                        ->where('pc.is_active', 1)
                        ->get();
                    return $plano;
                });

            
            $anuncio->videos = DB::table('propiedad_videosclub as pv')
                ->select('pv.url')
                ->where('pv.propiedad_id', $anuncio->id)
                ->where('pv.is_active', 1)
                ->get();
        }

        return $anuncios;
    }


    public static function actualizarEstadoVendidoclub($id, $estado)
    {
        return DB::table('propiedadesclub')
            ->where('id', $id)
            ->update([
                'is_active_publish' => $estado,
                'updated_at' => now()
            ]);
    }


    public static function listarplanosclub($id)
    {
        return DB::select("SELECT * FROM propiedad_planosclub WHERE propiedad_id= $id AND is_active = 1 ORDER BY id ASC");
    }

    public static function categoriasCatalogoidclub($id)
    {
        return DB::select("SELECT pc.id, pc.caracteristica_id, pc.valor, cc.nombre, cc.icono, pc.is_active FROM propiedad_caracteristicasclub pc INNER JOIN caracteristicas_catalogoclub cc on pc.caracteristica_id = cc.id WHERE pc.propiedad_id = $id AND pc.is_active = 1");
    }
    

    public static function amenitiesidclub($id)
    {
        return DB::select("SELECT pa.id, pa.amenity_id, a.nombre, a.icon_url, pa.is_active FROM propiedad_amenitiesclub pa INNER JOIN amenitiesclub a on pa.amenity_id = a.id WHERE pa.propiedad_id = $id AND pa.is_active = 1");
    }

    public static function listaimgsecundariasclub($id)
    {
        return DB::select("SELECT * FROM propiedad_imagenesclub WHERE propiedad_id= $id AND is_active = 1 ORDER BY id ASC");
    }

    public static function categoriasCatalogoclub($tpropiedad)
    {
        return DB::select("SELECT * FROM caracteristicas_catalogoclub WHERE tpropiedad_id = $tpropiedad AND is_active = 1 ORDER BY nombre ASC");
    }

    public static function amenitiesclub($tpropiedad)
    {
        return DB::select("SELECT * FROM amenitiesclub WHERE tpropiedad_id = $tpropiedad AND  is_active = 1 ORDER BY nombre ASC");
    }

    public static function actualizarAnuncioclub($id, $data, $rutaImagen = null)
    {
        DB::table('propiedadesclub')
            ->where('id', $id)
            ->update([
                'tipo_id' => $data['tipo_id'],
                'operacion_id' => $data['operacion_id'],
                'ubicacion_id' => $data['ubicacion_id'],
                'moneda_id' => $data['moneda_id'],
                'titulo' => $data['titulo'],
                'descripcion' => $data['descripcion'],
                'precio' => $data['precio'],
                'imagen_principal' => $rutaImagen,
                'direccion' => $data['direccion'],
                //'is_active_publish' => 0,
                'updated_at' => now(),
            ]);
    }

    public static function guardarCaracteristicasclub($propiedadId, $caracteristicas)
    {
        // Obtener todas las características actuales de la propiedad
        $caracActuales = DB::table('propiedad_caracteristicasclub')
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
                    DB::table('propiedad_caracteristicasclub')
                        ->where('propiedad_id', $propiedadId)
                        ->where('caracteristica_id', $id)
                        ->update([
                            'valor' => $valor,
                            'updated_at' => now(),
                        ]);
                }
            } else {
                // No existe → insertar
                DB::table('propiedad_caracteristicasclub')->insert([
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
            DB::table('propiedad_caracteristicasclub')
                ->where('propiedad_id', $propiedadId)
                ->whereIn('caracteristica_id', $idsAEliminar)
                ->delete();
        }
    }

    public static function guardarCaracteristicassecundariasclub($propiedadId, $caracteristicas_secundarias)
    {
        // Obtener todas las amenities actuales de la propiedad
        $caracActuales = DB::table('propiedad_amenitiesclub')
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
                    DB::table('propiedad_amenitiesclub')
                        ->where('propiedad_id', $propiedadId)
                        ->where('amenity_id', $id)
                        ->update([
                            'is_active' => 1,
                            'updated_at' => now(),
                        ]);
                }
            } else {
                // No existe → insertar
                DB::table('propiedad_amenitiesclub')->insert([
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
            DB::table('propiedad_amenitiesclub')
                ->where('propiedad_id', $propiedadId)
                ->whereIn('amenity_id', $idsAEliminar)
                ->update([
                    'is_active' => 0,
                    'updated_at' => now(),
                ]);
        }
    }

    public static function guardarPlanosclub($id, $titulo, $nombrePlano)
    {
        return DB::table('propiedad_planosclub')->insert([
                            'propiedad_id' => $id,
                            'titulo' => $titulo,
                            'imagen' => 'planos/' . $nombrePlano,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
    } 


    public static function guardarImagenesclub($idPropiedad, $titulo, $nombreArchivo)
    {
        return DB::table('propiedad_imagenesclub')->insert([
            'propiedad_id' => $idPropiedad,
            'titulo' => $titulo,
            'imagen' => 'propiedades_imagenesclub/' . $nombreArchivo,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public static function guardarvideourlclub($idPropiedad, $url)
    {
        return DB::table('propiedad_videosclub')->insert([
            'propiedad_id' => $idPropiedad,
            'titulo' => '',
            'url' => $url,
            'tipo' => 'youtube',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public static function crearAnuncioclub($data, $rutaImagen = null)
    {
        return DB::table('propiedadesclub')->insertGetId([
            'tipo_id' => $data['tipo_id'],
            'operacion_id' => $data['operacion_id'],
            'ubicacion_id' => $data['ubicacion_id'],
            'moneda_id' => $data['moneda_id'],
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

    public static function eliminarplanosclub($id)
    {
        return DB::update("UPDATE propiedad_planosclub SET is_active = 0 WHERE id = ?", [$id]);
    }

    public static function eliminarimgsecundariasclub($id)
    {
        return DB::update("UPDATE propiedad_imagenesclub SET is_active = 0 WHERE id = ?", [$id]);
    }

}
