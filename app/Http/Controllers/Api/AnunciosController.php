<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AnunciosModel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AnunciosController extends Controller
{
    public function tiposPropiedad()
    {
        $resultado = AnunciosModel::tiposPropiedad();
        return response()->json($resultado);
    }

    public function tiposOperacion()
    {
        $resultado = AnunciosModel::tiposOperacion();
        return response()->json($resultado);
    }

    public function tiposUbicaciones()
    {
        $resultado = AnunciosModel::tiposUbicaciones();
        return response()->json($resultado);
    }

    public function registrar(Request $request)
    {
        try {
            // 1️⃣ Validar campos obligatorios
            $validated = $request->validate([
                'tipo_id' => 'required|integer',
                'operacion_id' => 'required|integer',
                'ubicacion_id' => 'required|integer',
                'titulo' => 'required|string|max:255',
                'descripcion' => 'required|string',
                'precio' => 'required|numeric|min:0',
                'dormitorios' => 'nullable|integer|min:0',
                'banos' => 'nullable|integer|min:0',
                'area' => 'required|string|max:50',
                'imagen_principal' => 'nullable|image|max:2048', // 2MB máximo
                'user_id' => 'required|integer',
                
            ]);

            // 2️⃣ Subir imagen si existe
            $rutaImagen = null;
            if ($request->hasFile('imagen_principal')) {
                $archivo = $request->file('imagen_principal');
                $nombre = 'propiedad_' . Str::random(10) . '.' . $archivo->getClientOriginalExtension();

                // 📍 Guardar en el escritorio (ajusta la ruta)
                $directorioEscritorio = 'C:/Users/ALDASA/Desktop/propiedades';
                if (!file_exists($directorioEscritorio)) {
                    mkdir($directorioEscritorio, 0777, true);
                }

                $archivo->move($directorioEscritorio, $nombre);
                $rutaImagen = $directorioEscritorio . DIRECTORY_SEPARATOR . $nombre;
                
            }

            // 3️⃣ Crear anuncio mediante el modelo
            $id = AnunciosModel::crearAnuncio($validated, $rutaImagen);

            // 4️⃣ Respuesta exitosa
            return response()->json([
                'estado' => '1',
                'mensaje' => 'Anuncio registrado correctamente.',
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
                'detalle' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'traza' => $e->getTraceAsString(), // opcional, muestra toda la traza
            ], 500);
        }
    }

}
