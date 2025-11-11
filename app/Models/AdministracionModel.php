<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AdministracionModel extends Model
{
    

    public static function listarusuarioscombox()
    {
        return DB::select("SELECT id, CONCAT(nombre, ' ', apellido) AS nombre_completo FROM usuario");
    }


    public static function listarplanescombox()
    {
        return DB::select('SELECT * FROM planes');
    }

    public static function tiposPropiedad()
    {
        return DB::select('SELECT * FROM tipos_propiedad');
    }


    //CRUD MODULO PLANES

    public static function listarPlanes()
    {
        return DB::select('SELECT * FROM planes');
    }

    public static function crearPlan($data)
    {
        return DB::table('planes')->insertGetId([
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? '',
            'precio' => $data['precio'],
            'duracion_dias' => $data['duracion_dias'],
            'is_active' => $data['is_active'] ?? 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public static function actualizarPlan($id, $data)
    {
        DB::table('planes')
            ->where('id', $id)
            ->update([
                'nombre' => $data['nombre'],
                'descripcion' => $data['descripcion'] ?? '',
                'precio' => $data['precio'],
                'duracion_dias' => $data['duracion_dias'],
                'is_active' => $data['is_active'] ?? 1,
                'updated_at' => now(),
            ]);
    }
    

    public static function eliminarPlan($id, $data)
    {
        DB::table('planes')
            ->where('id', $id)
            ->update([
                'nombre' => $data['nombre'],
                'descripcion' => $data['descripcion'] ?? '',
                'precio' => $data['precio'],
                'duracion_dias' => $data['duracion_dias'],
                'is_active' => $data['is_active'] ?? 1,
                'updated_at' => now(),
            ]);
    }


    // ================================
    // PLANES DE USUARIOS
    // ================================

    public static function listarPlanesUsuario()
    {
        return DB::table('usuarios_planes AS up')
            ->join('usuario AS u', 'up.user_id', '=', 'u.id')
            ->join('planes AS p', 'up.plan_id', '=', 'p.id')
            ->select(
                'up.id',
                'u.nombre AS usuario',
                'p.nombre AS plan',
                'up.fecha_inicio',
                'up.fecha_fin',
                'up.anuncios_disponibles',
                'up.estado',
                'up.user_id',
                'plan_id'
            )
            ->orderBy('up.id', 'desc')
            ->get();
    }

    public static function crearPlanUsuario($data)
    {
        return DB::table('usuarios_planes')->insertGetId([
            'user_id' => $data['usuario_id'],
            'plan_id' => $data['plan_id'],
            'fecha_inicio' => $data['fecha_inicio'],
            'fecha_fin' => $data['fecha_fin'],
            'anuncios_disponibles' => $data['anuncios_disponibles'] ?? 0,
            'estado' => $data['estado'] ?? 'activo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public static function actualizarPlanUsuario($id, $data)
    {
        return DB::table('usuarios_planes')->where('id', $id)->update([
            'plan_id' => $data['plan_id'],
            'fecha_inicio' => date('Y-m-d H:i:s', strtotime($data['fecha_inicio'])),
            'fecha_fin' => date('Y-m-d H:i:s', strtotime($data['fecha_fin'])),
            'anuncios_disponibles' => $data['anuncios_disponibles'] ?? 0,
            'estado' => $data['estado'] ?? 'activo',
            'updated_at' => now(),
        ]);
    }



    //CRUD MODULO TIPO DOCUMENTO

    public static function ltipoDocumento()
    {
        return DB::select('SELECT * FROM tipos_documento ');
    }


    public static function registrarTipoDocumento($data)
    {
        DB::table('tipos_documento')->insert([
            'nombre' => $data['nombre'],
            'is_active' => $data['is_active'] ?? 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }


    public static function actualizarTipoDocumento($id, $data)
    {
        DB::table('tipos_documento')
            ->where('id', $id)
            ->update([
                'nombre' => $data['nombre'],
                'is_active' => $data['is_active'] ?? 1,
                'updated_at' => now(),
            ]);
    }


    //CURD MODULO AMENIDADES
    public static function listarAmenities()
    {
        return DB::select(' SELECT 
            a.*, 
            p.nombre AS propiedad_titulo
        FROM amenities a
        INNER JOIN  tipos_propiedad p ON a.tpropiedad_id = p.id');
    }

    public static function registrarAmenity($data)
    {
        DB::table('amenities')->insert([
            'nombre' => $data['nombre'],
            'tpropiedad_id' => $data['tpropiedad_id'],
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    public static function actualizarAmenity($id, $data)
    {
        DB::table('amenities')
            ->where('id', $id)
            ->update([
                'nombre' => $data['nombre'],
                'tpropiedad_id' => $data['tpropiedad_id'],
                'is_active' => $data['is_active'] ?? 1,
                'updated_at' => now()
            ]);
    }

    // CRUD para CARACTERÍSTICAS CATALOGO
    public static function listarCaracteristicasCatalogo()
    {
        return DB::select('
            SELECT c.*, p.nombre AS propiedad_titulo
            FROM caracteristicas_catalogo c
            INNER JOIN tipos_propiedad p ON c.tpropiedad_id = p.id
        ');
    }

    public static function registrarCaracteristicaCatalogo($data)
    {
        DB::table('caracteristicas_catalogo')->insert([
            'nombre' => $data['nombre'],
            'tipo' => $data['tipo'],
            'icono' => $data['icono'],
            'unidad' => $data['unidad'],
            'tpropiedad_id' => $data['tpropiedad_id'],
            'is_active' => $data['is_active'] ?? 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public static function actualizarCaracteristicaCatalogo($id, $data, $rutaIcono)
    {
        // Obtener el registro actual
        $caracteristica = DB::table('caracteristicas_catalogo')->where('id', $id)->first();

        // Si no se envía icono, mantener el valor actual
        $icono = $data['icono'] ?? $caracteristica->icono;

        DB::table('caracteristicas_catalogo')
            ->where('id', $id)
            ->update([
                'nombre' => $data['nombre'],
                'icono' => $rutaIcono,
                'unidad' => $data['unidad'],
                'tpropiedad_id' => $data['tpropiedad_id'],
                'is_active' => $data['is_active'] ?? 1,
                'updated_at' => now(),
            ]);
    }

    //CRUD MODULO OPERACIONES
    public static function listarOperaciones()
    {
        return DB::select('SELECT * FROM operaciones');
    }

    // Registrar operación
    public static function registrarOperacion($data)
    {
        return DB::table('operaciones')->insertGetId([
            'nombre' => $data['nombre'],
            'is_active' => $data['is_active'] ?? 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // Actualizar operación
    public static function actualizarOperacion($id, $data)
    {
        DB::table('operaciones')
            ->where('id', $id)
            ->update([
                'nombre' => $data['nombre'],
                'is_active' => $data['is_active'] ?? 1,
                'updated_at' => now(),
            ]);
    }


    //CRUD MODULO TIPO PROPIEDAD
    public static function listarTiposPropiedad()
    {
        return DB::select('SELECT * FROM tipos_propiedad');
    }

   
    public static function registrarTipoPropiedad($data)
    {
        return DB::table('tipos_propiedad')->insertGetId([
            'nombre' => $data['nombre'],
            'is_active' => $data['is_active'] ?? 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public static function actualizarTipoPropiedad($id, $data)
    {
        DB::table('tipos_propiedad')
            ->where('id', $id)
            ->update([
                'nombre' => $data['nombre'],
                'is_active' => $data['is_active'] ?? 1,
                'updated_at' => now(),
            ]);
    }


    //  CRUD MODULO PAGINAS
    public static function listarpaginas()
    {
        return DB::select('SELECT * FROM paginas');
    }

    // Registrar página
    public static function registrarpaginas($data, $rutaImagen)
    {
        return DB::table('paginas')->insertGetId([
            'slug' => $data['slug'],
            'titulo' => $data['titulo'],
            'contenido' => $data['contenido'] ?? null,
            'meta_titulo' => $data['meta_titulo'] ?? null,
            'meta_descripcion' => $data['meta_descripcion'] ?? null,
            'imagen_destacada' => $rutaImagen ?? null,
            'is_active' => $data['is_active'] ?? 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // Actualizar página
    public static function actualizarpaginas($id, $data, $rutaImagen)
    {
        DB::table('paginas')
            ->where('id', $id)
            ->update([
                'slug' => $data['slug'],
                'titulo' => $data['titulo'],
                'contenido' => $data['contenido'] ?? null,
                'meta_titulo' => $data['meta_titulo'] ?? null,
                'meta_descripcion' => $data['meta_descripcion'] ?? null,
                'imagen_destacada' => $rutaImagen ?? null,
                'is_active' => $data['is_active'] ?? 1,
                'updated_at' => now(),
            ]);
    }

    // Cambiar estado (activar/desactivar página)
    public static function cambiarEstadopaginas($id, $is_active)
    {
        DB::table('paginas')
            ->where('id', $id)
            ->update([
                'is_active' => $is_active,
                'updated_at' => now(),
            ]);
    }



    // CRUD MODULO CONFIGURACIONES
    public static function listarconfiguracion()
    {
        return DB::table('configuraciones')
            ->orderBy('id', 'asc')
            ->get();
    }

    // ✅ REGISTRAR CONFIGURACIÓN
    public static function registrarconfiguracion($data, $rutaValor)
    {
        return DB::table('configuraciones')->insertGetId([
            'clave' => $data['clave'],
            'valor' => $rutaValor,
            'tipo' => $data['tipo'],
            'descripcion' => $data['descripcion'] ?? null,
            'is_active' => $data['is_active'] ?? 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // ✅ ACTUALIZAR CONFIGURACIÓN
    public static function actualizarconfiguracion($id, $data, $rutaValor)
    {
        return DB::table('configuraciones')
            ->where('id', $id)
            ->update([
                'clave' => $data['clave'],
                'valor' => $rutaValor,
                'tipo' => $data['tipo'],
                'descripcion' => $data['descripcion'] ?? null,
                'is_active' => $data['is_active'] ?? 1,
                'updated_at' => now(),
            ]);
    }

    // ✅ CAMBIAR ESTADO
    public static function cambiarConfiguracion($id, $isActive)
    {
        return DB::table('configuraciones')
            ->where('id', $id)
            ->update([
                'is_active' => $isActive,
                'updated_at' => now(),
            ]);
    }

 

    // ✅ CRUD MODULO UBICACIONES
    public static function listarUbicaciones()
    {
        return DB::table('ubicaciones')
            ->orderBy('id', 'asc')
            ->get();
    }

    public static function registrarUbicacion($data)
    {
        return DB::table('ubicaciones')->insertGetId([
            'nombre' => $data['nombre'],
            'is_active' => $data['is_active'] ?? 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public static function actualizarUbicacion($id, $data)
    {
        return DB::table('ubicaciones')
            ->where('id', $id)
            ->update([
                'nombre' => $data['nombre'],
                'is_active' => $data['is_active'] ?? 1,
                'updated_at' => now(),
            ]);
    } 

    public static function cambiarUbicacion($id, $isActive)
    {
        return DB::table('ubicaciones')
            ->where('id', $id)
            ->update([
                'is_active' => $isActive,
                'updated_at' => now(),
            ]);
    }



    // CRUD MODULO BITACORA
    public static function listarbitacora()
    {
        return DB::table('bitacora')
            ->orderBy('id', 'asc')
            ->get();
    }

}
