<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AnunciosModel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
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

    public function registraranuncio(Request $request)
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
                'imagen_principal' => 'nullable|image|max:2048', // 2MB máximo
                'user_id' => 'required|integer',
            ]);

            // 2️⃣ Subir imagen (si existe)
            $rutaImagen = null;
            if ($request->hasFile('imagen_principal')) {
                $archivo = $request->file('imagen_principal');
                $nombre = 'propiedad_' . Str::random(10) . '.' . $archivo->getClientOriginalExtension();

                $directorioEscritorio = 'C:/xampp/htdocs/propiedades';
                if (!file_exists($directorioEscritorio)) {
                    mkdir($directorioEscritorio, 0777, true);
                }

                $archivo->move($directorioEscritorio, $nombre);
                $rutaImagen = 'http://localhost/propiedades/' . $nombre;
            }

            // 3️⃣ Crear el anuncio principal (propiedad)
            $idPropiedad = AnunciosModel::crearAnuncio($validated, $rutaImagen);

            // 4️⃣ Guardar características (si existen)
            if ($request->has('caracteristicas')) {
                $caracteristicas = json_decode($request->caracteristicas, true);

                if (is_array($caracteristicas) && count($caracteristicas) > 0) {
                    AnunciosModel::guardarCaracteristicas($idPropiedad, $caracteristicas);
                }
            }

            if ($request->has('caracteristicas_secundarias')) {
                $caracteristicas_secundarias = json_decode($request->caracteristicas_secundarias, true);

                if (is_array($caracteristicas_secundarias) && count($caracteristicas_secundarias) > 0) {
                    AnunciosModel::guardarCaracteristicassecundarias($idPropiedad, $caracteristicas_secundarias);
                }
            }


            // 5️⃣ Respuesta exitosa
            return response()->json([
                'estado' => 1,
                'mensaje' => 'Anuncio registrado correctamente.',
                'id' => $idPropiedad,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'estado' => 0,
                'mensaje' => 'Error de validación.',
                'errores' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'estado' => 0,
                'mensaje' => 'Error interno del servidor.',
                'detalle' => $e->getMessage(),
                'linea' => $e->getLine(),
            ], 500);
        }
    }


    public function listaranuncio($idpublish, $id)
    {
        $resultado = AnunciosModel::listaranuncio($idpublish, $id);
        return response()->json($resultado);
    }

    public function actualizaranuncio(Request $request, $id)
    {
        try {
            // 1️⃣ Validar los campos
            $validated = $request->validate([
                'tipo_id' => 'required|integer',
                'operacion_id' => 'required|integer',
                'ubicacion_id' => 'required|integer',
                'titulo' => 'required|string|max:255',
                'descripcion' => 'required|string',
                'precio' => 'required|numeric|min:0',
                
            ]);

            // 2️⃣ Buscar anuncio existente
            $anuncio = DB::table('propiedades')->where('id', $id)->first();

            if (!$anuncio) {
                return response()->json([
                    'estado' => 0,
                    'mensaje' => 'Anuncio no encontrado.'
                ], 404);
            }

            // 3️⃣ Manejar imagen
            $rutaImagen = $anuncio->imagen_principal; // mantener la anterior si no hay nueva

            if ($request->hasFile('imagen_principal')) {
                $archivo = $request->file('imagen_principal');
                $nombre = 'propiedad_' . Str::random(10) . '.' . $archivo->getClientOriginalExtension();

                $directorioEscritorio = 'C:/xampp/htdocs/propiedades';
                if (!file_exists($directorioEscritorio)) {
                    mkdir($directorioEscritorio, 0777, true);
                }

                $archivo->move($directorioEscritorio, $nombre);
                $rutaImagen = 'http://localhost/propiedades/' . $nombre;
            }

            // 4️⃣ Actualizar el anuncio
            AnunciosModel::actualizarAnuncio($id, $validated, $rutaImagen);

            // 5️⃣ Actualizar características
            if ($request->has('caracteristicas')) {
                $caracteristicas = json_decode($request->caracteristicas, true);

                if (is_array($caracteristicas)) {
                    // eliminar las antiguas
                    
                    // guardar las nuevas
                    AnunciosModel::guardarCaracteristicas($id, $caracteristicas);
                }
            }

            if ($request->has('caracteristicas_secundarias')) {
                $caracteristicas_secundarias = json_decode($request->caracteristicas_secundarias, true);

                if (is_array($caracteristicas_secundarias)) {
                    // eliminar las antiguas
                    
                    // guardar las nuevas
                    AnunciosModel::guardarCaracteristicassecundarias($id, $caracteristicas_secundarias);
                }
            }

            // 6️⃣ Respuesta exitosa
            return response()->json([
                'estado' => 1,
                'mensaje' => 'Anuncio actualizado correctamente.'
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'estado' => 0,
                'mensaje' => 'Error de validación.',
                'errores' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'estado' => 0,
                'mensaje' => 'Error interno del servidor.',
                'detalle' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ], 500);
        }
    }


    public function categoriasCatalogo()
    {
        $resultado = AnunciosModel::categoriasCatalogo();
        return response()->json($resultado);
    }

    public function categoriasCatalogoid($id)
    {
        $resultado = AnunciosModel::categoriasCatalogoid($id);
        return response()->json($resultado);
    }
    
    public function amenities()
    {
        $resultado = AnunciosModel::amenities();
        return response()->json($resultado);
    }

    public function amenitiesid($id)
    {
        $resultado = AnunciosModel::amenitiesid($id);
        return response()->json($resultado);
    }
}
