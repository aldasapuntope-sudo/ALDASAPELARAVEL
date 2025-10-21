<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AdministracionModel extends Model
{
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

}
