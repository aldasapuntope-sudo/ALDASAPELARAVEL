<?php


namespace App\Http\Controllers\Api;
use Google\Client as GoogleClient;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Laravel\Sanctum\HasApiTokens;

use App\Models\UsuarioModel;

class AuthController extends Controller
{ 
    public function loginform(Request $request){
        $email = $request->input('email');
        $clave = $request->input('clave');
        if ($email && $clave) {
            // Realiza la validación con tu procedimiento almacenado
            $resultado = UsuarioModel::validarPorCredenciales($email, $clave);

            if (empty($resultado)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Correo o clave incorrectos.'
                ], 403);
            }

            $datosAlumno = $resultado[0];

            // Busca o crea el usuario en la tabla users
            $user = User::firstOrCreate(
                ['email' => $email],
                ['name' => $datosAlumno->nombre_completo ?? 'Usuario', 'password' => bcrypt($clave)]
            );

            // Genera el token
            $token = $user->createToken('form-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'token' => $token,
                'name' => $datosAlumno->nombre_completo ?? 'Usuario',
                'email' => $email,
                'usuarioaldasa' => $datosAlumno,
            ], 200);
        }

        return response()->json(['error' => 'Faltan datos para autenticación.'], 400);
    }
    
    
    public function googleLogin(Request $request)
    {
        $accessToken = $request->input('token'); // access_token desde React

        if (!$accessToken) {
            return response()->json(['error' => 'Token no recibido'], 400);
        }

        // Llamada a la API de Google para obtener info del usuario
        $res = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
        ])->get('https://www.googleapis.com/oauth2/v3/userinfo');

        if ($res->failed()) {
            return response()->json(['error' => 'Token inválido o expirado'], 401);
        }

        $userData = $res->json();
        
        $email = $userData['email'];
        $name = $userData['name'];
        $picture = $userData['picture'] ?? null;
        $givenName = $userData['given_name'] ?? null;
        $familyName = $userData['family_name'] ?? null;

        // Validar si el correo pertenece a un alumno registrado
        $resultado = UsuarioModel::validarAlumnoPorCorreo($email);
        $datosAlumno = $resultado[0] ?? null;

        


        // Busca o crea al usuario en la base de datos
        $user = User::firstOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => bcrypt(uniqid())]
        );

        // Genera token de Sanctum
        $token = $user->createToken('google-token')->plainTextToken;

        if (empty($resultado)) {

            

            $data = [
                'nombre' => $givenName ?? $name,
                'apellido' => $familyName ?? '',
                'email' => $email,
                'tipoUsuario' => '1',         // Usuario normal
                'condicionFiscal' => '1',     // DNI
                'documento' => '',
                'password' => uniqid(),     // Genera una contraseña temporal
                'telefono' => null,
                'movil' => null,
                'imagen' => $picture
            ];

            // 🔹 Crea el usuario con el método del modelo
            $nuevoUsuarioId = UsuarioModel::crearUsuariogoogle($data);
            $resultado = UsuarioModel::validarAlumnoPorCorreo($email);
            $datosAlumno = $resultado[0] ?? null;
           
        }

            return response()->json([
                'success' => true,
                'token' => $token,
                'name' => $name,
                'email' => $email,
                'imagen' => $picture,
                'givenName' => $givenName,
                'familyName' => $familyName,
                'usuarioaldasa' => $datosAlumno,
            ], 200);
    }



    
}