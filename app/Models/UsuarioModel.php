<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class UsuarioModel extends Authenticatable
{
    
   public static function validarAlumnoPorCorreo($email)
    {
       return DB::select(
            "SELECT * FROM usuario WHERE email = ? and is_active = 1 ",
            [$email]
        );
    }

    public static function validarPorCredenciales($email, $clave)
    {
        return DB::select(
            "SELECT * FROM usuario WHERE email = ? and password = ? and is_active = 1",
            [$email, md5($clave)]
        );
    }

    public static function tipoUsuario()
    {
        return DB::select("SELECT * FROM perfiles WHERE is_active = 1");
    }

    
    public static function getByUserCp($codigo)
    {
        return DB::select('SELECT * FROM usuario WHERE numero_documento = ? and is_active = 1', [$codigo]);
    }

    public static function tipoDocumento()
    {
        return DB::select('SELECT * FROM tipos_documento WHERE is_active = 1');
    }

    public static function existePorDocumento($codigo)
    {
        return DB::select('SELECT * FROM usuario WHERE numero_documento = ? AND is_active = 1', [$codigo]);
        
    }

    public static function crearUsuario($data)
    {
        return DB::table('usuario')->insertGetId([
            'perfil_id' => $data->tipoUsuario,
            'tipo_documento_id' => $data->condicionFiscal,
            'numero_documento' => $data->documento,
            'nombre' => $data->nombre,
            'apellido' => $data->apellido,
            'email' => $data->email,
            'password' => md5($data->password),
            'telefono' => $data->telefono,
            'telefono_movil' => $data->movil,
            'is_active' => 1,
            
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public static function crearUsuariogoogle($data)
    {
        return DB::table('usuario')->insertGetId([
            'perfil_id' => $data['tipoUsuario'],
            'tipo_documento_id' => $data['condicionFiscal'],
            'numero_documento' => $data['documento'],
            'nombre' => $data['nombre'],
            'apellido' => $data['apellido'],
            'email' => $data['email'],
            'password' => md5($data['password']),
            'telefono' => $data['telefono'],
            'telefono_movil' => $data['movil'],
            'imagen' => $data['imagen'],
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public static function miperfil($codigo)
    {
       return DB::select(
            "SELECT usu.id, p.id as tipoUsuario, p.nombre as perfil, usu.nombre, usu.apellido, usu.razon_social, usu.email, tpd.id as condicionFiscal, tpd.nombre as tipodocumento, usu.numero_documento as documento, usu.telefono, usu.telefono_movil as movil, usu.imagen FROM usuario usu inner join tipos_documento tpd on usu.tipo_documento_id = tpd.id inner join perfiles p on usu.perfil_id = p.id WHERE usu.id = ? ",
            [$codigo]
        );
    }

    public static function actualizarperfil($id, $data)
    {
        return DB::table('usuario')
            ->where('id', $id)
            ->update([
                'perfil_id' => $data->tipoUsuario ?? null,
                'tipo_documento_id' => $data->condicionFiscal ?? null,
                'numero_documento' => $data->documento ?? null,
                'nombre' => $data->nombre ?? null,
                'apellido' => $data->apellido ?? null,
                'email' => $data->email ?? null,
                'telefono' => $data->telefono ?? null,
                'telefono_movil' => $data->movil ?? null,
                'razon_social' => $data->razon_social ?? null,
                'updated_at' => now(),
            ]);
    }

}
