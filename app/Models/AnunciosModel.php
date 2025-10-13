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
            'area' => $data['area'],
            'dormitorios' => $data['dormitorios'] ?? null,
            'banos' => $data['banos'] ?? null,
            'imagen_principal' => $rutaImagen,
            'user_id' => $data['user_id'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
