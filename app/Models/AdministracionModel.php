<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AdministracionModel extends Model
{
    

    public static function listarperfilescombox()
    {
        return DB::select("SELECT * FROM perfiles");
    }

    public static function listardocumentoscombox()
    {
        return DB::select("SELECT * FROM tipos_documento");
    }

    public static function listarmotivosoporteayuda()
    {
        return DB::select("SELECT * FROM soporte_motivos WHERE is_active = 1");
    }

    public static function registrarticketssoprote($request, $user_id)
    {
        try {
            $id = DB::table('soporte_tickets')->insertGetId([
                'user_id' => $user_id,
                'soporte_motivo_id' => $request->soporte_motivo_id,
                'titulo' => $request->titulo,
                'descripcion' => $request->descripcion,
                'estado' => 'pendiente',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return [
                'success' => true,
                'message' => 'Ticket registrado correctamente',
                'ticket_id' => $id
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al registrar el ticket',
                'error' => $e->getMessage()
            ];
        }
    }


    public static function listarusuarioscombox()
    {
        return DB::select("SELECT id, CONCAT(nombre, ' ', apellido) AS nombre_completo FROM usuario");
    }


    public static function obtenersliders()
    {
        return DB::select("SELECT * FROM sliders");
    }
    

    

    public static function listarplanescombox()
    {
        return DB::select('SELECT * FROM planes');
    }

    public static function listarplanescomboxclub()
    {
        return DB::select('SELECT * FROM planesclub');
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


    public static function listarSliders()
    {
        return DB::table('sliders')
            ->orderBy('id', 'desc')
            ->get();
    }

    public static function registrarSlider($data)
    {
        return DB::table('sliders')->insertGetId([
            'titulo' => $data['titulo'],
            'descripcion' => $data['descripcion'] ?? null,
            'imagen_url' => $data['imagen_url'],
            'orden' => $data['orden'] ?? 0,
            'is_active' => $data['is_active'] ?? 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public static function obtenerSliderPorId($id)
    {
        return DB::table('sliders')->where('id', $id)->first();
    }

    public static function actualizarSlider($id, $data, $rutaImagen)
    {
        return DB::table('sliders')
            ->where('id', $id)
            ->update([
                'titulo' => $data['titulo'],
                'descripcion' => $data['descripcion'] ?? null,
                'imagen_url' => $rutaImagen,
                'orden' => $data['orden'],
                'is_active' => $data['is_active'] ?? 1,
                'updated_at' => now(),
            ]);
    }



    //CRUD MODULO POPUPS

    public static function listarPopups()
    {
        return DB::table('popups')
            ->where('is_active', 1)
            ->orderBy('id', 'desc')
            ->get();
    }

    public static function listarcolor()
    {
        return DB::table('configuraciones')
            ->where('clave', 'colorprimario')
            ->where('is_active', 1)
            ->get();
    }

    public static function listarPopups2()
    {
        return DB::table('popups')
            ->orderBy('id', 'desc')
            ->get();
    }

    public static function getPopupConfig()
    {
        return DB::table('popup_config')
            ->orderBy('id', 'desc')
            ->get();
    }

    public static function registrarPopups($data)
    {
        return DB::table('popups')->insertGetId([
            'titulo' => $data['titulo'],
            'descripcion' => $data['descripcion'] ?? null,
            'imagen_url' => $data['imagen_url'],
            'orden' => $data['orden'] ?? 0,
            'is_active' => $data['is_active'] ?? 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public static function obtenerPopupsPorId($id)
    {
        return DB::table('popups')->where('id', $id)->first();
    }

    public static function actualizarPopups($id, $data, $rutaImagen)
    {
        return DB::table('popups')
            ->where('id', $id)
            ->update([
                'titulo' => $data['titulo'],
                'descripcion' => $data['descripcion'] ?? null,
                'imagen_url' => $rutaImagen,
                'orden' => $data['orden'],
                'is_active' => $data['is_active'] ?? 1,
                'updated_at' => now(),
            ]);
    }


    //CRUD MODULO USUARIOS
    public static function listarUsuarios()
    {
        return DB::table('usuario')
            ->orderBy('id', 'desc')
            ->get();
    }

    public static function registrarUsuarios($data)
    {
        return DB::table('usuario')->insertGetId([
            'perfil_id' => $data['perfil_id'],
            'nombre' => $data['nombre'],
            'apellido' => $data['apellido'],
            'razon_social' => $data['razon_social'],
            'email' => $data['email'],
            'password' => $data['password'],
            'tipo_documento_id' => $data['tipo_documento_id'],
            'numero_documento' => $data['numero_documento'],
            'telefono' => $data['telefono'],
            'telefono_movil' => $data['telefono_movil'],
            'is_active' => $data['is_active'] ?? 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }


    public static function obtenerUsuarioPorId($id)
    {
        return DB::table('usuario')->where('id', $id)->first();
    }

    public static function actualizarUsuarios($id, $data)
    {
        return DB::table('usuario')
            ->where('id', $id)
            ->update([
                'perfil_id' => $data['perfil_id'],
                'nombre' => $data['nombre'],
                'apellido' => $data['apellido'],
                'razon_social' => $data['razon_social'],
                'email' => $data['email'],
                'password' => $data['password'],
                'tipo_documento_id' => $data['tipo_documento_id'],
                'numero_documento' => $data['numero_documento'],
                'telefono' => $data['telefono'],
                'telefono_movil' => $data['telefono_movil'],
                'is_active' => $data['is_active'] ?? 1,
                'updated_at' => now(),
            ]);
    }


    public static function actualizarImagenUsuario($id, $rutaImagen)
    {
        return DB::table('usuario')
            ->where('id', $id)
            ->update([
                'imagen' => $rutaImagen
            ]);
    }


    //CRUD MODELO CONFIGURACION SCRITS
    public static function obtenerScripts()
    {
        return DB::table('config_scripts')
            ->where('is_active', 1)
            ->first();
    }

    public static function listarScripts()
    {
        return DB::table('config_scripts')
            ->orderBy('id', 'desc')
            ->get();
    }

    public static function registrarScripts($data)
    {
        return DB::table('config_scripts')->insert([
            'nombre' => $data['nombre'],
            'script_head' => $data['script_head'],
            'script_body' => $data['script_body'],
            'is_active' => $data['is_active'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public static function actualizarScripts($id, $data)
    {
        return DB::table('config_scripts')
            ->where('id', $id)
            ->update([
                'nombre' => $data['nombre'],
                'script_head' => $data['script_head'],
                'script_body' => $data['script_body'],
                'is_active' => $data['is_active'],
                'updated_at' => now(),
            ]);
    }

    public static function cambiarEstadoScripts($id, $estado)
    {
        return DB::table('config_scripts')
            ->where('id', $id)
            ->update([
                'is_active' => $estado,
                'updated_at' => now(),
            ]);
    }




    //CRUD MODULO PLANES CLUB

    public static function listarPlanesclub()
    {
        return DB::select('SELECT * FROM planesclub');
    }

    public static function crearPlanclub($data)
    {
        return DB::table('planesclub')->insertGetId([
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? '',
            'precio' => $data['precio'],
            'duracion_dias' => $data['duracion_dias'],
            'is_active' => $data['is_active'] ?? 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public static function actualizarPlanclub($id, $data)
    {
        DB::table('planesclub')
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
    

    public static function eliminarPlanclub($id, $data)
    {
        DB::table('planesclub')
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
    // PLANES DE USUARIOS CLUB
    // ================================

    public static function listarPlanesUsuarioclub()
    {
        return DB::table('usuarios_planesclub AS up')
            ->join('usuario AS u', 'up.user_id', '=', 'u.id')
            ->join('planesclub AS p', 'up.plan_id', '=', 'p.id')
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

    public static function crearPlanUsuarioclub($data)
    {
        return DB::table('usuarios_planesclub')->insertGetId([
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

    public static function actualizarPlanUsuarioclub($id, $data)
    {
        return DB::table('usuarios_planesclub')->where('id', $id)->update([
            'plan_id' => $data['plan_id'],
            'fecha_inicio' => date('Y-m-d H:i:s', strtotime($data['fecha_inicio'])),
            'fecha_fin' => date('Y-m-d H:i:s', strtotime($data['fecha_fin'])),
            'anuncios_disponibles' => $data['anuncios_disponibles'] ?? 0,
            'estado' => $data['estado'] ?? 'activo',
            'updated_at' => now(),
        ]);
    }


    // CRUD para CARACTERÍSTICAS CATALOGO CLUB
    public static function listarCaracteristicasCatalogoclub()
    {
        return DB::select('
            SELECT c.*, p.nombre AS propiedad_titulo
            FROM caracteristicas_catalogoclub c
            INNER JOIN tipos_propiedad p ON c.tpropiedad_id = p.id
        ');
    }

    public static function registrarCaracteristicaCatalogoclub($data)
    {
        DB::table('caracteristicas_catalogoclub')->insert([
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

    public static function actualizarCaracteristicaCatalogoclub($id, $data, $rutaIcono)
    {
        // Obtener el registro actual
        $caracteristica = DB::table('caracteristicas_catalogoclub')->where('id', $id)->first();

        // Si no se envía icono, mantener el valor actual
        $icono = $data['icono'] ?? $caracteristica->icono;

        DB::table('caracteristicas_catalogoclub')
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


    //CURD MODULO AMENIDADES
    public static function listarAmenitiesclub()
    {
        return DB::select(' SELECT 
            a.*, 
            p.nombre AS propiedad_titulo
        FROM amenitiesclub a
        INNER JOIN  tipos_propiedad p ON a.tpropiedad_id = p.id');
    }

    public static function registrarAmenityclub($data)
    {
        DB::table('amenitiesclub')->insert([
            'nombre' => $data['nombre'],
            'tpropiedad_id' => $data['tpropiedad_id'],
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    public static function actualizarAmenityclub($id, $data)
    {
        DB::table('amenitiesclub')
            ->where('id', $id)
            ->update([
                'nombre' => $data['nombre'],
                'tpropiedad_id' => $data['tpropiedad_id'],
                'is_active' => $data['is_active'] ?? 1,
                'updated_at' => now()
            ]);
    }

    public static function listarmensajecontactos($id)
    {
        return DB::select("
            SELECT msgc.id, CONCAT(usu.nombre, ' ', usu.apellido) as nombrecompleto, p.titulo, p.descripcion, p.id as idpropiedad, p.direccion, usu.telefono_movil, usu.telefono, p.imagen_principal FROM mensajes_contacto msgc inner join propiedades p on msgc.anuncio_id = p.id inner join usuario usu on p.user_id = usu.id WHERE msgc.dni = ? ORDER BY msgc.id DESC;
        ", [$id]);
    }
 

}
