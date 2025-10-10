<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UsuarioModel;
use Illuminate\Support\Facades\Http;

class UsuarioController extends Controller
{
    public function tipoUsuario()
    {
        $resultado = UsuarioModel::tipoUsuario();
        return response()->json($resultado);
    }

    public function tipoDocumento()
    {
        $resultado = UsuarioModel::tipoDocumento();
        return response()->json($resultado);
    }

    public function dni($numero)
    {
        return $this->consultarDocumento($numero, 'dni');
    }

    // 🔹 Consultar RUC
    public function ruc($numero)
    {
        return $this->consultarDocumento($numero, 'ruc');
    }

    private function consultarDocumento($numero, $tipo)
    {

        // 🔹 2. Si no existe, consultar la API externa
        $token = 'apis-token-1.aTSI1U7KEuT-6bbbCguH-4Y8TI6KS73N';
        $baseUrl = $tipo === 'dni'
            ? 'https://api.apis.net.pe/v1/dni?numero='
            : 'https://api.apis.net.pe/v1/ruc?numero=';

        $response = Http::withHeaders([
            'Referer' => 'https://apis.net.pe/consulta-dni-api',
            'Authorization' => 'Bearer ' . $token,
        ])->get($baseUrl . $numero);

        if ($response->failed()) {
            return response()->json(['error' => 'No se pudo conectar con la API externa'], 500);
        }

        $data = $response->json();

        // 🔹 3. Extraer datos dependiendo del tipo de documento
        if ($tipo === 'dni') {
            $nombre = $data['nombres'] ?? '';
            $apellidos = trim(($data['apellidoPaterno'] ?? '') . ' ' . ($data['apellidoMaterno'] ?? ''));
            $razonSocial = '';
        } else {
            $nombre = $data['nombres'] ?? '';
            $apellidos = $data['nombre'] ?? '';
            $razonSocial = $data['nombre'] ?? '';
        }

        // 🔹 4. Retornar solo la información básica si no existe en BD
        return response()->json([
            'nombre'        => $nombre,
            'apellidos'     => $apellidos,
            'razon_social'  => $razonSocial,
            'estado'        => "2"
        ]);
    }

    public function registrar(Request $request)
    {
        try {
            // Validación básica
            $validated = $request->validate([
                'tipoUsuario' => 'required',
                'condicionFiscal' => 'required',
                'documento' => 'required',
                'nombre' => 'required',
                'apellido' => 'required',
                'email' => 'required|email',
                'password' => 'required|min:6',
                'movil' => 'required',
            ]);

            // Verificar si el usuario ya existe (por número de documento)
            $existe = UsuarioModel::existePorDocumento($request->documento);
            if ($existe) {
                return response()->json([
                    'estado' => '0',
                    'mensaje' => 'El número de documento ya se encuentra registrado.'
                ], 409);
            }

            // Insertar usuario
            $id = UsuarioModel::crearUsuario($request);

            return response()->json([
                'estado' => '1',
                'mensaje' => 'Usuario registrado correctamente.',
                'id' => $id
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'estado' => '0',
                'mensaje' => 'Error de validación.',
                'errores' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'estado' => '0',
                'mensaje' => 'Error interno del servidor.',
                'detalle' => $e->getMessage()
            ], 500);
        }
    }



    public function miperfil($codigo)
    {
        $resultado = UsuarioModel::miperfil($codigo);
        return response()->json($resultado);
    }
    public function actualizarperfil(Request $request, $id)
    {
        try {
            $actualizado = UsuarioModel::actualizarperfil($id, $request);

            if ($actualizado) {
                return response()->json([
                    'exito' => true,
                    'mensaje' => 'Perfil actualizado correctamente'
                ], 200);
            } else {
                return response()->json([
                    'exito' => false,
                    'mensaje' => 'No se pudo actualizar el perfil o no hubo cambios'
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'exito' => false,
                'mensaje' => 'Error interno del servidor',
                'detalle' => $e->getMessage()
            ], 500);
        }
    }



   
}
