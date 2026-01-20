<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UsuarioModel;

use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    /**
     * Login con correo y clave
     */
    public function loginform(Request $request)
    {
        $email = $request->input('email');
        $clave = $request->input('clave');

        if (!$email || !$clave) {
            return response()->json(['error' => 'Faltan datos para autenticación.'], 400);
        }

        // Buscar usuario en tu tabla principal
        $resultado = UsuarioModel::validarAlumnoPorCorreo($email);
        $usuarioAldasa = $resultado[0] ?? null;

        if (!$usuarioAldasa) {
            return response()->json([
                'success' => false,
                'error' => 'Correo o clave incorrectos.'
            ], 403);
        }

        $claveBD = $usuarioAldasa->password ?? '';

        // Validar contraseña (bcrypt o compatibilidad con MD5)
        $esValida = false;

        if (strlen($claveBD) === 32 && $claveBD === md5($clave)) {
            // Contraseña antigua con MD5 → actualizar a bcrypt
            $nuevoHash = Hash::make($clave);
            UsuarioModel::actualizarPassword($email, $nuevoHash);
            $esValida = true;
        } elseif (Hash::check($clave, $claveBD)) {
            $esValida = true;
        }

        if (!$esValida) {
            return response()->json([
                'success' => false,
                'error' => 'Correo o clave incorrectos.'
            ], 403);
        }

        // Sincroniza con tabla users de Laravel (para Sanctum)
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $usuarioAldasa->nombre_completo ?? 'Usuario',
                'password' => Hash::make($clave)
            ]
        );

        $token = $user->createToken('form-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'name' => $usuarioAldasa->nombre_completo ?? 'Usuario',
            'email' => $email,
            'usuarioaldasa' => $usuarioAldasa,
        ], 200);
    }

    /**
     * Login con Google
     */
    public function googleLogin(Request $request)
    {
        $accessToken = $request->input('token');

        if (!$accessToken) {
            return response()->json(['error' => 'Token no recibido'], 400);
        }

        // Obtener datos del usuario desde Google
        $res = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
        ])->get('https://www.googleapis.com/oauth2/v3/userinfo');

        if ($res->failed()) {
            return response()->json(['error' => 'Token inválido o expirado'], 401);
        }

        $userData = $res->json();

        $email       = $userData['email'];
        $name        = $userData['name'];
        $picture     = $userData['picture'] ?? null;
        $givenName   = $userData['given_name'] ?? null;
        $familyName  = $userData['family_name'] ?? null;

        // Validar si existe en sistema propio
        $resultado = UsuarioModel::validarAlumnoPorCorreo($email);
        $datosAlumno = $resultado[0] ?? null;

        // Crear u obtener usuario Laravel (Sanctum)
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make(uniqid())
            ]
        );

        // Crear token Sanctum
        $token = $user->createToken('google-token')->plainTextToken;

        // Si no existe en sistema propio, crear
        if (empty($resultado)) {
            $data = [
                'success' => true,
                'token' => $token,
                'nombre' => $givenName ?? $name,
                'apellido' => $familyName ?? '',
                'email' => $email,
                'tipoUsuario' => '3',
                'condicionFiscal' => '1',
                'documento' => '',
                'password' => Hash::make(uniqid()),
                'telefono' => null,
                'movil' => null,
                'imagen' => $picture
            ];

            UsuarioModel::crearUsuariogoogle($data);

            $resultado = UsuarioModel::validarAlumnoPorCorreo($email);
            $datosAlumno = $resultado[0] ?? null;
        }

        // 🔎 BUSCAR PLAN ACTIVO (SIN MODELOS)
        $planActivo = DB::table('usuarios_planes as up')
            ->join('planes as p', 'p.id', '=', 'up.plan_id')
            ->where('up.user_id', $user->id)
            ->where('up.is_active', 1)
            ->where('up.estado', 'activo')
            ->where(function ($q) {
                $q->whereNull('up.fecha_fin')
                ->orWhere('up.fecha_fin', '>=', now());
            })
            ->select('p.nombre as plan')
            ->first();

        $nombrePlan = $planActivo ? $planActivo->plan : 0;

        // Respuesta final
        return response()->json([
            'success' => true,
            'token' => $token,
            'name' => $name,
            'email' => $email,
            'imagen' => $picture,
            'givenName' => $givenName,
            'familyName' => $familyName,
            'usuarioaldasa' => $datosAlumno,
            'planActivo' => $nombrePlan
        ], 200);
    }

}
