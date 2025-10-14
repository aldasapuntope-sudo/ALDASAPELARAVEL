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
        $rows = [];
        foreach ($caracteristicas as $carac) {
            $rows[] = [
                'propiedad_id' => $propiedadId,
                'caracteristica_id' => $carac['id'],
                'valor' => $carac['valor'] ?? '',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('propiedad_caracteristicas')->insert($rows);
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
    


}
