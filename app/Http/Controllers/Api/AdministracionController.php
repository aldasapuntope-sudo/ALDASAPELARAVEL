<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AdministracionModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdministracionController extends Controller
{

    public function tiposPropiedad()
    {
        $resultado = AdministracionModel::tiposPropiedad();
        return response()->json($resultado);
    }

    
    //CRUD MODULO PLANES

    public function listarPlanes()
    {
        $resultado = AdministracionModel::listarPlanes();
        return response()->json($resultado);
    }

    public function registrarPlanes(Request $request)
    {
        try {
            // 1️⃣ Validar los campos
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'descripcion' => 'nullable|string|max:255',
                'precio' => 'required|numeric|min:0',
                'duracion_dias' => 'required|integer|min:1',
                'is_active' => 'boolean',
            ]);

            // 2️⃣ Insertar usando el modelo
            $idPlan = AdministracionModel::crearPlan($validated);

            // 3️⃣ Responder con éxito
            return response()->json([
                'estado' => 1,
                'mensaje' => 'Plan registrado correctamente.',
                'id' => $idPlan,
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

    public function actualizarPlanes(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'descripcion' => 'nullable|string|max:255',
                'precio' => 'required|numeric|min:0',
                'duracion_dias' => 'required|integer|min:1',
                'is_active' => 'boolean',
            ]);

            $plan = DB::table('planes')->where('id', $id)->first();

            if (!$plan) {
                return response()->json([
                    'estado' => 0,
                    'mensaje' => 'El plan no existe.'
                ], 404);
            }

            // ✅ Llamar al modelo
            AdministracionModel::actualizarPlan($id, $validated);

            return response()->json([
                'estado' => 1,
                'mensaje' => 'Plan actualizado correctamente.'
            ], 200);

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


    public function cambiarEstadoPlan($id, Request $request)
    {
        try {
            $validated = $request->validate([
                'is_active' => 'required|boolean',
            ]);

            $plan = DB::table('planes')->where('id', $id)->first();

            if (!$plan) {
                return response()->json([
                    'estado' => 0,
                    'mensaje' => 'El plan no existe.',
                ], 404);
            }

            DB::table('planes')
                ->where('id', $id)
                ->update([
                    'is_active' => $validated['is_active'],
                    'updated_at' => now(),
                ]);

            return response()->json([
                'estado' => 1,
                'mensaje' => 'Estado del plan actualizado correctamente.',
            ], 200);

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



    //CRUD MODULO TIPO DOCUMENTO

    public function ltipoDocumento()
    {
        $resultado = AdministracionModel::ltipoDocumento();
        return response()->json($resultado);
    }
    
    public function registrarTipoDocumento(Request $request)
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'is_active' => 'boolean',
            ]);

            // ✅ Llamar al modelo
            AdministracionModel::registrarTipoDocumento($validated);

            return response()->json([
                'estado' => 1,
                'mensaje' => 'Tipo de documento registrado correctamente.'
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

    public function actualizarTipoDocumento(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'is_active' => 'boolean',
            ]);

            $tipo = DB::table('tipos_documento')->where('id', $id)->first();

            if (!$tipo) {
                return response()->json([
                    'estado' => 0,
                    'mensaje' => 'El tipo de documento no existe.'
                ], 404);
            }

            // ✅ Llamar al modelo
            AdministracionModel::actualizarTipoDocumento($id, $validated);

            return response()->json([
                'estado' => 1,
                'mensaje' => 'Tipo de documento actualizado correctamente.'
            ], 200);

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

    public function cambiarEstadoTipoDocumento($id, Request $request)
    {
        try {
            $validated = $request->validate([
                'is_active' => 'required|boolean',
            ]);

            $plan = DB::table('tipos_documento')->where('id', $id)->first();

            if (!$plan) {
                return response()->json([
                    'estado' => 0,
                    'mensaje' => 'El Tipo Documento no existe.',
                ], 404);
            }

            DB::table('tipos_documento')
                ->where('id', $id)
                ->update([
                    'is_active' => $validated['is_active'],
                    'updated_at' => now(),
                ]);

            return response()->json([
                'estado' => 1,
                'mensaje' => 'Estado del Tipo Documento actualizado correctamente.',
            ], 200);

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


    //CRUD MODULO AMENIDADES

    public function listarAmenities()
    {
        $resultado = AdministracionModel::listarAmenities();
        return response()->json($resultado);
    }

    public function registrarAmenity(Request $request)
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'tpropiedad_id' => 'required|integer',
                'is_active' => 'boolean',
                
            ]);

            // 🔹 Enviamos los datos al modelo sin icono
            AdministracionModel::registrarAmenity($validated);

            return response()->json(['message' => 'Amenity registrado correctamente'], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al registrar el amenity: ' . $e->getMessage()], 500);
        }
    }


    public function actualizarAmenity(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'tpropiedad_id' => 'required|integer',
                'is_active' => 'boolean',
            ]);

            // 🔹 Enviamos los datos al modelo sin icono
            AdministracionModel::actualizarAmenity($id, $validated);

            return response()->json(['message' => 'Amenity actualizado correctamente'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al actualizar el amenity: ' . $e->getMessage()], 500);
        }
    }

    public function cambiarEstadoAmenity($id, Request $request)
    {
        try {
            $validated = $request->validate([
                'is_active' => 'required|boolean',
            ]);

            $plan = DB::table('amenities')->where('id', $id)->first();

            if (!$plan) {
                return response()->json([
                    'estado' => 0,
                    'mensaje' => 'El Servicio no existe.',
                ], 404);
            }

            DB::table('amenities')
                ->where('id', $id)
                ->update([
                    'is_active' => $validated['is_active'],
                    'updated_at' => now(),
                ]);

            return response()->json([
                'estado' => 1,
                'mensaje' => 'Estado del Servicio actualizado correctamente.',
            ], 200);

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

    // CRUD MODULO CARACTERÍSTICAS CATALOGO
    public function listarCaracteristicasCatalogo()
    {
        $resultado = AdministracionModel::listarCaracteristicasCatalogo();
        return response()->json($resultado);
    }

    public function registrarCaracteristicaCatalogo(Request $request)
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'unidad' => 'required|string|max:50',
                'tpropiedad_id' => 'required|integer',
                'is_active' => 'boolean',
                'icono' => 'nullable|image|mimes:png,jpg,jpeg,svg|dimensions:width=16,height=16',
            ]);

            if ($request->hasFile('icono')) {
                $path = $request->file('icono')->store('iconos_caracteristicas', 'public');
                $validated['icono'] = $path;
            } else {
                $validated['icono'] = null;
            }

            AdministracionModel::registrarCaracteristicaCatalogo($validated);

            return response()->json(['message' => 'Característica registrada correctamente'], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al registrar la característica: ' . $e->getMessage()], 500);
        }
    }

    public function actualizarCaracteristicaCatalogo(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'unidad' => 'nullable|string|max:50',
                'tpropiedad_id' => 'required|integer',
                'is_active' => 'boolean',
                'icono' => 'nullable',
            ]);

            $rutaIcono = null;
            if ($request->hasFile('icono')) {
                $archivo = $request->file('icono');
                $nombre = 'icono_' . Str::random(10) . '.' . $archivo->getClientOriginalExtension();

                $directorio = 'C:/xampp/htdocs/iconos';
                if (!file_exists($directorio)) {
                    mkdir($directorio, 0777, true);
                }

                $archivo->move($directorio, $nombre);
                $rutaIcono = '' . $nombre;
            } else {
                // Si no se sube un nuevo ícono, mantener el actual
                $rutaIcono = $request->input('icono_actual');
            }
            AdministracionModel::actualizarCaracteristicaCatalogo($id, $validated, $rutaIcono);

            return response()->json(['message' => 'Característica actualizada correctamente'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al actualizar la característica: ' . $e->getMessage()], 500);
        }
    }

    public function cambiarEstadoCaracteristicaCatalogo($id, Request $request)
    {
        try {
            $validated = $request->validate([
                'is_active' => 'required|boolean',
            ]);

            $item = DB::table('caracteristicas_catalogo')->where('id', $id)->first();
            if (!$item) {
                return response()->json(['estado' => 0, 'mensaje' => 'La característica no existe.'], 404);
            }

            DB::table('caracteristicas_catalogo')
                ->where('id', $id)
                ->update([
                    'is_active' => $validated['is_active'],
                    'updated_at' => now(),
                ]);

            return response()->json(['estado' => 1, 'mensaje' => 'Estado actualizado correctamente.'], 200);

        } catch (\Exception $e) {
            return response()->json(['estado' => 0, 'mensaje' => 'Error: ' . $e->getMessage()], 500);
        }
    }


}
