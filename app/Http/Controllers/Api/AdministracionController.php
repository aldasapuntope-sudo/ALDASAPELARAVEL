<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AdministracionModel;
use App\Models\BitacoraModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AdministracionController extends Controller
{
    protected $bitacora;

    public function __construct()
    {
        $this->bitacora = new BitacoraModel();
    }

    public function listarusuarioscombox()
    {
        return response()->json(AdministracionModel::listarusuarioscombox());
    }

    public function obtenersliders()
    {
        return response()->json(AdministracionModel::obtenersliders());
    }


    public function listarperfilescombox()
    {
        return response()->json(AdministracionModel::listarperfilescombox());
    }

    public function listardocumentoscombox()
    {
        return response()->json(AdministracionModel::listardocumentoscombox());
    }

    public function listarmotivosoporteayuda()
    {
        return response()->json(AdministracionModel::listarmotivosoporteayuda());
    }

    
    public function registrarticketssoprote(Request $request, $id)
    {
        $request->validate([
            'soporte_motivo_id' => 'required|exists:soporte_motivos,id',
            'titulo' => 'required|string|max:200',
            'descripcion' => 'required|string',
        ]);

        $resultado = AdministracionModel::registrarticketssoprote($request, $id);

        return response()->json($resultado);
    }
    
    public function listarplanescombox()
    {
        return response()->json(AdministracionModel::listarplanescombox());
    }

    public function listarplanescomboxclub()
    {
        return response()->json(AdministracionModel::listarplanescomboxclub());
    }


    public function obtenerConfiguraciones()
    {
        try {
            $configuraciones = DB::table('configuraciones')
                ->where('is_active', 1)
                ->pluck('valor', 'clave'); // 🔹 Devuelve un objeto tipo { clave: valor }

            return response()->json($configuraciones, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener configuraciones: ' . $e->getMessage()
            ], 500);
        }
    }

    public function obtenerLugaresMasBuscados()
    {
        try {
            $lugares = DB::table('ubicaciones')
                ->join('propiedades', 'propiedades.ubicacion_id', '=', 'ubicaciones.id')
                ->select(
                    'ubicaciones.id',
                    'ubicaciones.nombre',
                    DB::raw('SUM(propiedades.visitas) as total_vistas')
                )
                ->where('ubicaciones.is_active', 1)
                ->where('propiedades.is_active_publish', 1)
                ->groupBy('ubicaciones.id', 'ubicaciones.nombre')
                ->orderByDesc(DB::raw('SUM(propiedades.visitas)')) // 🔹 Ordenar por vistas totales (de mayor a menor)
                ->limit(8) // 🔹 Solo los 8 más vistos
                ->get();

            return response()->json($lugares, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener los lugares más buscados: ' . $e->getMessage()
            ], 500);
        }
    }

    /*public function obtenerLugaresMasBuscados()
    {
        try {
            $lugares = DB::table('ubicaciones')
                ->join('propiedades', 'propiedades.ubicacion_id', '=', 'ubicaciones.id')
                ->select('ubicaciones.id', 'ubicaciones.nombre', DB::raw('COUNT(propiedades.id) as total'))
                ->where('ubicaciones.is_active', 1)
                ->where('propiedades.is_active_publish', 1)
                ->groupBy('ubicaciones.id', 'ubicaciones.nombre')
                ->orderByDesc(DB::raw('COUNT(propiedades.id)')) // 🔹 Asegura que ordene por el total
                ->limit(8) // 🔹 Solo los 8 primeros
                ->get();

            return response()->json($lugares, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener lugares más buscados: ' . $e->getMessage()], 500);
        }
    }*/



    private function registrarBitacora($accion, $tabla, $registro_id, $descripcion = null)
    {
        $user = Auth::user();

        $data = [
            'user_id' => $user ? $user->id : null,
            'accion' => $accion,
            'tabla_afectada' => $tabla,
            'registro_id' => $registro_id,
            'descripcion' => $descripcion,
            'ip' => request()->ip(),
            'created_at' => now(),
            'updated_at' => now()
        ];

        $this->bitacora->insertar($data);
    }

    // ---------------------------------------------------------
    // MODULO TIPOS DE PROPIEDAD
    // ---------------------------------------------------------
    public function tiposPropiedad()
    {
        $resultado = AdministracionModel::tiposPropiedad();
        return response()->json($resultado);
    }

    // ---------------------------------------------------------
    // CRUD PLANES
    // ---------------------------------------------------------
    public function listarPlanes()
    {
        return response()->json(AdministracionModel::listarPlanes());
    }

    public function registrarPlanes(Request $request)
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'descripcion' => 'nullable|string',
                //'descripcion' => 'nullable|string|max:255',
                'precio' => 'required|numeric|min:0',
                'duracion_dias' => 'required|integer|min:1',
                'is_active' => 'boolean',
            ]);

            $idPlan = AdministracionModel::crearPlan($validated);

            // 🔹 Bitácora
            $this->registrarBitacora('Crear', 'planes', $idPlan, 'Se creó el plan: ' . $validated['nombre']);

            return response()->json(['estado' => 1, 'mensaje' => 'Plan registrado correctamente.', 'id' => $idPlan], 201);
        } catch (\Exception $e) {
            return response()->json(['estado' => 0, 'mensaje' => 'Error al registrar el plan', 'detalle' => $e->getMessage()], 500);
        }
    }

    public function actualizarPlanes(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'descripcion' => 'nullable|string',
                //'descripcion' => 'nullable|string|max:255',
                'precio' => 'required|numeric|min:0',
                'duracion_dias' => 'required|integer|min:1',
                'is_active' => 'boolean',
            ]);

            AdministracionModel::actualizarPlan($id, $validated);

            // 🔹 Bitácora
            $this->registrarBitacora('Actualizar', 'planes', $id, 'Se actualizó el plan: ' . $validated['nombre']);

            return response()->json(['estado' => 1, 'mensaje' => 'Plan actualizado correctamente.'], 200);
        } catch (\Exception $e) {
            return response()->json(['estado' => 0, 'mensaje' => 'Error al actualizar el plan', 'detalle' => $e->getMessage()], 500);
        }
    }

    public function cambiarEstadoPlan($id, Request $request)
    {
        try {
            $validated = $request->validate(['is_active' => 'required|boolean']);

            DB::table('planes')->where('id', $id)->update([
                'is_active' => $validated['is_active'],
                'updated_at' => now(),
            ]);

            // 🔹 Bitácora
            $this->registrarBitacora('Actualizar', 'planes', $id, 'Se cambió el estado del plan.');

            return response()->json(['estado' => 1, 'mensaje' => 'Estado actualizado correctamente.'], 200);
        } catch (\Exception $e) {
            return response()->json(['estado' => 0, 'mensaje' => 'Error al cambiar estado del plan', 'detalle' => $e->getMessage()], 500);
        }
    }





    // ================================
    // PLANES DE USUARIOS
    // ================================

    public function listarPlanesUsuario()
    {
        return response()->json(AdministracionModel::listarPlanesUsuario());
    }

    public function registrarPlanesUsuario(Request $request)
    {
        try {
            $validated = $request->validate([
                'usuario_id' => 'required|integer|exists:usuarios,id',
                'plan_id' => 'required|integer|exists:planes,id',
                'fecha_inicio' => 'required|date',
                'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
                'anuncios_disponibles' => 'nullable|integer|min:0',
                'estado' => 'nullable|string|in:activo,inactivo',
            ]);

            $idPlanUsuario = AdministracionModel::crearPlanUsuario($validated);

            // 🔹 Bitácora
            $this->registrarBitacora('Crear', 'planes_usuario', $idPlanUsuario, 'Se asignó un plan a un usuario.');

            return response()->json(['estado' => 1, 'mensaje' => 'Plan de usuario registrado correctamente.', 'id' => $idPlanUsuario], 201);
        } catch (\Exception $e) {
            return response()->json(['estado' => 0, 'mensaje' => 'Error al registrar el plan de usuario', 'detalle' => $e->getMessage()], 500);
        }
    }

    public function actualizarPlanesUsuario(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'plan_id' => 'required|integer|exists:planes,id',
                'fecha_inicio' => 'required|date',
                'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
                'anuncios_disponibles' => 'nullable|integer|min:0',
                'estado' => 'required|in:activo,vencido',
            ]);

            AdministracionModel::actualizarPlanUsuario($id, $validated);

            // 🔹 Bitácora
            $this->registrarBitacora('Actualizar', 'planes_usuario', $id, 'Se actualizó el plan de usuario.');

            return response()->json(['estado' => 1, 'mensaje' => 'Plan de usuario actualizado correctamente.'], 200);
        } catch (\Exception $e) {
            return response()->json(['estado' => 0, 'mensaje' => 'Error al actualizar el plan de usuario', 'detalle' => $e->getMessage()], 500);
        }
    }

    public function cambiarEstadoPlanesUsuario($id, Request $request)
    {
        try {
            $validated = $request->validate(['estado' => 'required|string|in:activo,inactivo']);

            DB::table('usuarios_planes')->where('id', $id)->update([
                'estado' => $validated['estado'],
                'updated_at' => now(),
            ]);

            // 🔹 Bitácora
            $this->registrarBitacora('Actualizar', 'planes_usuario', $id, 'Se cambió el estado del plan de usuario.');

            return response()->json(['estado' => 1, 'mensaje' => 'Estado del plan de usuario actualizado correctamente.'], 200);
        } catch (\Exception $e) {
            return response()->json(['estado' => 0, 'mensaje' => 'Error al cambiar estado del plan de usuario', 'detalle' => $e->getMessage()], 500);
        }
    }


    // ---------------------------------------------------------
    // CRUD TIPO DOCUMENTO
    // ---------------------------------------------------------
    public function ltipoDocumento()
    {
        return response()->json(AdministracionModel::ltipoDocumento());
    }

    public function registrarTipoDocumento(Request $request)
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'is_active' => 'boolean',
            ]);

            $id = AdministracionModel::registrarTipoDocumento($validated);

            // 🔹 Bitácora
            $this->registrarBitacora('Crear', 'tipos_documento', $id, 'Se creó tipo documento: ' . $validated['nombre']);

            return response()->json(['estado' => 1, 'mensaje' => 'Tipo documento registrado correctamente.'], 201);
        } catch (\Exception $e) {
            return response()->json(['estado' => 0, 'mensaje' => 'Error al registrar tipo documento', 'detalle' => $e->getMessage()], 500);
        }
    }

    public function actualizarTipoDocumento(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'is_active' => 'boolean',
            ]);

            AdministracionModel::actualizarTipoDocumento($id, $validated);

            // 🔹 Bitácora
            $this->registrarBitacora('Actualizar', 'tipos_documento', $id, 'Se actualizó tipo documento: ' . $validated['nombre']);

            return response()->json(['estado' => 1, 'mensaje' => 'Tipo documento actualizado correctamente.'], 200);
        } catch (\Exception $e) {
            return response()->json(['estado' => 0, 'mensaje' => 'Error al actualizar tipo documento', 'detalle' => $e->getMessage()], 500);
        }
    }

    public function cambiarEstadoTipoDocumento($id, Request $request)
    {
        try {
            $validated = $request->validate(['is_active' => 'required|boolean']);

            DB::table('tipos_documento')->where('id', $id)->update([
                'is_active' => $validated['is_active'],
                'updated_at' => now(),
            ]);

            // 🔹 Bitácora
            $this->registrarBitacora('Actualizar', 'tipos_documento', $id, 'Se cambió el estado del tipo documento.');

            return response()->json(['estado' => 1, 'mensaje' => 'Estado actualizado correctamente.'], 200);
        } catch (\Exception $e) {
            return response()->json(['estado' => 0, 'mensaje' => 'Error al cambiar estado', 'detalle' => $e->getMessage()], 500);
        }
    }

    // ---------------------------------------------------------
    // CRUD AMENITIES
    // ---------------------------------------------------------
    public function listarAmenities()
    {
        return response()->json(AdministracionModel::listarAmenities());
    }

    public function registrarAmenity(Request $request)
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'tpropiedad_id' => 'required|integer',
                'is_active' => 'boolean',
            ]);

            $id = AdministracionModel::registrarAmenity($validated);

            // 🔹 Bitácora
            $this->registrarBitacora('Crear', 'amenities', $id, 'Se registró amenity: ' . $validated['nombre']);

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

            AdministracionModel::actualizarAmenity($id, $validated);

            // 🔹 Bitácora
            $this->registrarBitacora('Actualizar', 'amenities', $id, 'Se actualizó amenity: ' . $validated['nombre']);

            return response()->json(['message' => 'Amenity actualizado correctamente'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al actualizar el amenity: ' . $e->getMessage()], 500);
        }
    }

    public function cambiarEstadoAmenity($id, Request $request)
    {
        try {
            $validated = $request->validate(['is_active' => 'required|boolean']);

            DB::table('amenities')->where('id', $id)->update([
                'is_active' => $validated['is_active'],
                'updated_at' => now(),
            ]);

            // 🔹 Bitácora
            $this->registrarBitacora('Actualizar', 'amenities', $id, 'Se cambió el estado del amenity.');

            return response()->json(['estado' => 1, 'mensaje' => 'Estado del servicio actualizado correctamente.'], 200);
        } catch (\Exception $e) {
            return response()->json(['estado' => 0, 'mensaje' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // ---------------------------------------------------------
    // CRUD CARACTERÍSTICAS CATALOGO
    // ---------------------------------------------------------
    public function listarCaracteristicasCatalogo()
    {
        return response()->json(AdministracionModel::listarCaracteristicasCatalogo());
    }

    /*public function registrarCaracteristicaCatalogo(Request $request)
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'unidad' => 'required|string|max:50',
                'tpropiedad_id' => 'required|integer',
                'is_active' => 'boolean',
                'icono' => 'nullable|image|mimes:png,jpg,jpeg,svg',
            ]);

            if ($request->hasFile('icono')) {
                $path = $request->file('icono')->store('iconos_caracteristicas', 'public');
                $validated['icono'] = $path;
            }

            $id = AdministracionModel::registrarCaracteristicaCatalogo($validated);

            // 🔹 Bitácora
            $this->registrarBitacora('Crear', 'caracteristicas_catalogo', $id, 'Se registró característica: ' . $validated['nombre']);

            return response()->json(['message' => 'Característica registrada correctamente'], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al registrar característica: ' . $e->getMessage()], 500);
        }
    }*/

    public function registrarCaracteristicaCatalogo(Request $request)
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'unidad' => 'required|string|max:50',
                'tpropiedad_id' => 'required|integer',
                'is_active' => 'boolean',
                'icono' => 'nullable|file|mimes:png,jpg,jpeg,svg',
            ]);

            $rutaIcono = null;

            // ✅ Si se envía un icono, lo guardamos físicamente
            if ($request->hasFile('icono')) {
                $archivo = $request->file('icono');
                $nombre = 'icono_' . Str::random(10) . '.' . $archivo->getClientOriginalExtension();

                // 🔹 Directorio donde se guardarán los iconos
                $directorio = 'C:/xampp/htdocs/iconos';

                if (!file_exists($directorio)) {
                    mkdir($directorio, 0777, true);
                }

                // 🔹 Mover archivo físico
                $archivo->move($directorio, $nombre);

                // 🔹 Ruta que se guarda en la BD (relativa)
                $rutaIcono = 'iconos/' . $nombre;
            }

            $validated['icono'] = $rutaIcono;

            // ✅ Guardamos la característica en la BD
            $id = AdministracionModel::registrarCaracteristicaCatalogo($validated);

            // 🔹 Bitácora
            $this->registrarBitacora('Crear', 'caracteristicas_catalogo', $id, 'Se registró característica: ' . $validated['nombre']);

            return response()->json(['message' => 'Característica registrada correctamente'], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al registrar característica: ' . $e->getMessage()], 500);
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
                if (!file_exists($directorio)) mkdir($directorio, 0777, true);
                $archivo->move($directorio, $nombre);
                $rutaIcono = $nombre;
            } else {
                $rutaIcono = $request->input('icono_actual');
            }

            AdministracionModel::actualizarCaracteristicaCatalogo($id, $validated, $rutaIcono);

            // 🔹 Bitácora
            $this->registrarBitacora('Actualizar', 'caracteristicas_catalogo', $id, 'Se actualizó característica: ' . $validated['nombre']);

            return response()->json(['message' => 'Característica actualizada correctamente'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al actualizar característica: ' . $e->getMessage()], 500);
        }
    }

    public function cambiarEstadoCaracteristicaCatalogo($id, Request $request)
    {
        try {
            $validated = $request->validate(['is_active' => 'required|boolean']);

            DB::table('caracteristicas_catalogo')
                ->where('id', $id)
                ->update(['is_active' => $validated['is_active'], 'updated_at' => now()]);

            // 🔹 Bitácora
            $this->registrarBitacora('Actualizar', 'caracteristicas_catalogo', $id, 'Se cambió el estado de la característica.');

            return response()->json(['estado' => 1, 'mensaje' => 'Estado actualizado correctamente.'], 200);
        } catch (\Exception $e) {
            return response()->json(['estado' => 0, 'mensaje' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    //CRUD MODULO OPERACIONES
    public function listarOperaciones()
    {
        return response()->json(AdministracionModel::listarOperaciones());
    }

    public function registrarOperacion(Request $request)
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'is_active' => 'boolean',
            ]);

            $id = AdministracionModel::registrarOperacion($validated);

            // 🔹 Bitácora
            $this->registrarBitacora('Crear', 'operaciones', $id, 'Se creó operación: ' . $validated['nombre']);

            return response()->json(['estado' => 1, 'mensaje' => 'Operación registrada correctamente.'], 201);
        } catch (\Exception $e) {
            return response()->json(['estado' => 0, 'mensaje' => 'Error al registrar operación', 'detalle' => $e->getMessage()], 500);
        }
    }

    public function actualizarOperacion(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'is_active' => 'boolean',
            ]);

            AdministracionModel::actualizarOperacion($id, $validated);

            // 🔹 Bitácora
            $this->registrarBitacora('Actualizar', 'operaciones', $id, 'Se actualizó operación: ' . $validated['nombre']);

            return response()->json(['estado' => 1, 'mensaje' => 'Operación actualizada correctamente.'], 200);
        } catch (\Exception $e) {
            return response()->json(['estado' => 0, 'mensaje' => 'Error al actualizar operación', 'detalle' => $e->getMessage()], 500);
        }
    }

    public function cambiarEstadoOperacion($id, Request $request)
    {
        try {
            $validated = $request->validate(['is_active' => 'required|boolean']);

            DB::table('operaciones')->where('id', $id)->update([
                'is_active' => $validated['is_active'],
                'updated_at' => now(),
            ]);

            // 🔹 Bitácora
            $this->registrarBitacora('Actualizar', 'operaciones', $id, 'Se cambió el estado de la operación.');

            return response()->json(['estado' => 1, 'mensaje' => 'Estado actualizado correctamente.'], 200);
        } catch (\Exception $e) {
            return response()->json(['estado' => 0, 'mensaje' => 'Error al cambiar estado', 'detalle' => $e->getMessage()], 500);
        }
    }


    //CRUD MODULO TIPO PROPIEDAD
    public function listarTiposPropiedad()
    {
        return response()->json(AdministracionModel::listarTiposPropiedad());
    }

    public function registrarTipoPropiedad(Request $request)
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'is_active' => 'boolean',
            ]);

            $id = AdministracionModel::registrarTipoPropiedad($validated);

            // 🔹 Bitácora
            $this->registrarBitacora('Crear', 'tipos_propiedad', $id, 'Se creó tipo propiedad: ' . $validated['nombre']);

            return response()->json(['estado' => 1, 'mensaje' => 'Tipo de propiedad registrado correctamente.'], 201);
        } catch (\Exception $e) {
            return response()->json(['estado' => 0, 'mensaje' => 'Error al registrar tipo de propiedad', 'detalle' => $e->getMessage()], 500);
        }
    }

    public function actualizarTipoPropiedad(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'is_active' => 'boolean',
            ]);

            AdministracionModel::actualizarTipoPropiedad($id, $validated);

            // 🔹 Bitácora
            $this->registrarBitacora('Actualizar', 'tipos_propiedad', $id, 'Se actualizó tipo propiedad: ' . $validated['nombre']);

            return response()->json(['estado' => 1, 'mensaje' => 'Tipo de propiedad actualizado correctamente.'], 200);
        } catch (\Exception $e) {
            return response()->json(['estado' => 0, 'mensaje' => 'Error al actualizar tipo de propiedad', 'detalle' => $e->getMessage()], 500);
        }
    }

    public function cambiarEstadoTipoPropiedad($id, Request $request)
    {
        try {
            $validated = $request->validate(['is_active' => 'required|boolean']);

            DB::table('tipos_propiedad')->where('id', $id)->update([
                'is_active' => $validated['is_active'],
                'updated_at' => now(),
            ]);

            // 🔹 Bitácora
            $this->registrarBitacora('Actualizar', 'tipos_propiedad', $id, 'Se cambió el estado del tipo de propiedad.');

            return response()->json(['estado' => 1, 'mensaje' => 'Estado actualizado correctamente.'], 200);
        } catch (\Exception $e) {
            return response()->json(['estado' => 0, 'mensaje' => 'Error al cambiar estado', 'detalle' => $e->getMessage()], 500);
        }
    }

    // CRUD MODULO PAGINAS
    public function listarpaginas()
    {
        return response()->json(AdministracionModel::listarpaginas());
    }

    public function registrarpaginas(Request $request)
    {
        try {
            $validated = $request->validate([
                'slug' => 'required|string|max:150',
                'titulo' => 'required|string|max:255',
                'contenido' => 'nullable|string',
                'meta_titulo' => 'nullable|string|max:255',
                'meta_descripcion' => 'nullable|string|max:255',
                'imagen_destacada' => 'nullable|file|mimes:jpg,jpeg,png,webp',
                'is_active' => 'boolean',
            ]);

            $rutaImagen = null;

            // ✅ Si se envía una imagen, la guardamos físicamente
            if ($request->hasFile('imagen_destacada')) {
                $archivo = $request->file('imagen_destacada');
                $nombre = 'pagina_' . Str::random(10) . '.' . $archivo->getClientOriginalExtension();

                // 🔹 Ruta absoluta en tu servidor local
                $directorio = 'C:/xampp/htdocs/imagenes_paginas';

                if (!file_exists($directorio)) {
                    mkdir($directorio, 0777, true);
                }

                // 🔹 Mover archivo físico
                $archivo->move($directorio, $nombre);

                // 🔹 Ruta que se guarda en la BD (relativa)
                $rutaImagen = 'imagenes_paginas/' . $nombre;
            }

            // ✅ Guardamos la página en la base de datos
            $id = AdministracionModel::registrarpaginas($validated, $rutaImagen);

            // 🔹 Bitácora
            $this->registrarBitacora('Crear', 'paginas', $id, 'Se creó la página: ' . $validated['titulo']);

            return response()->json(['message' => 'Página registrada correctamente'], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al registrar la página: ' . $e->getMessage()
            ], 500);
        }
    }



    public function actualizarpaginas(Request $request, $id)
    {
        try {
            
            $validated = $request->validate([
                'slug' => 'required|string|max:150',
                'titulo' => 'required|string|max:255',
                'contenido' => 'nullable|string',
                'meta_titulo' => 'nullable|string|max:255',
                'meta_descripcion' => 'nullable|string|max:255',
                'imagen_destacada' => 'nullable|file|mimes:jpg,jpeg,png,webp',
                'is_active' => 'boolean',
            ]);

            // ✅ Mantiene la imagen actual si no se envía una nueva
            $rutaImagen = $request->input('imagen_actual');

            // ✅ Si se sube una nueva imagen, la guardamos
            if ($request->hasFile('imagen_destacada')) {
                $archivo = $request->file('imagen_destacada');
                $nombre = 'pagina_' . Str::random(10) . '.' . $archivo->getClientOriginalExtension();

                // 🔹 Ruta absoluta (tu carpeta real)
                $directorio = 'C:/xampp/htdocs/imagenes_paginas';

                if (!file_exists($directorio)) {
                    mkdir($directorio, 0777, true);
                }

                // 🔹 Guardamos físicamente la imagen
                $archivo->move($directorio, $nombre);

                // 🔹 Guardamos solo la ruta relativa en la BD
                $rutaImagen = 'imagenes_paginas/' . $nombre;
            }

            // ✅ Llamamos al modelo para actualizar
            AdministracionModel::actualizarpaginas($id, $validated, $rutaImagen);

            // 🔹 Bitácora
            $this->registrarBitacora('Actualizar', 'paginas', $id, 'Se actualizó la página: ' . $validated['titulo']);

            return response()->json(['message' => 'Página actualizada correctamente'], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al actualizar la página: ' . $e->getMessage()
            ], 500);
        }
    }



    public function cambiarEstadopaginas($id, Request $request)
    {
        try {
            $validated = $request->validate([
                'is_active' => 'required|boolean'
            ]);

            DB::table('paginas')
                ->where('id', $id)
                ->update([
                    'is_active' => $validated['is_active'],
                    'updated_at' => now(),
                ]);

            // 🔹 Bitácora
            $this->registrarBitacora('Actualizar', 'paginas', $id, 'Se cambió el estado de la página.');

            return response()->json([
                'estado' => 1,
                'mensaje' => 'Estado actualizado correctamente.'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'estado' => 0,
                'mensaje' => 'Error al cambiar el estado.',
                'detalle' => $e->getMessage()
            ], 500);
        }
    }


    // ✅ CRUD MODULO CONFIGURACIONES
    public function listarconfiguracion()
    {
        return response()->json(AdministracionModel::listarconfiguracion());
    }

    public function registrarconfiguracion(Request $request)
    {
        try {
            $rules = [
                'clave' => 'required|string|max:150|unique:configuraciones,clave',
                'tipo' => 'required|in:texto,imagen,color,numero,booleano',
                'descripcion' => 'nullable|string|max:255',
                'is_active' => 'boolean'
            ];

            // Si el tipo es imagen, valor puede ser file
            if ($request->input('tipo') === 'imagen') {
                $rules['valor'] = 'nullable|file|mimes:jpg,jpeg,png,webp|max:4096';
            } else {
                $rules['valor'] = 'nullable|string';
            }

            $validated = $request->validate($rules);


            $rutaValor = $validated['valor'] ?? null;

            // 📸 Si se envía imagen
            if ($request->hasFile('valor') && $validated['tipo'] === 'imagen') {
                $archivo = $request->file('valor');
                $nombre = 'config_' . Str::random(10) . '.' . $archivo->getClientOriginalExtension();

                $directorio = 'C:/xampp/htdocs/imagenes_configuraciones';
                if (!file_exists($directorio)) {
                    mkdir($directorio, 0777, true);
                }

                $archivo->move($directorio, $nombre);
                $rutaValor = 'imagenes_configuraciones/' . $nombre;
            }

            // ✅ Insertar por modelo
            $id = AdministracionModel::registrarconfiguracion($validated, $rutaValor);

            $this->registrarBitacora('Crear', 'configuraciones', $id, 'Se registró la configuración: ' . $validated['clave']);

            return response()->json(['message' => 'Configuración registrada correctamente.'], 201);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al registrar la configuración: ' . $e->getMessage()], 500);
        }
    }


    public function actualizarconfiguracion(Request $request, $id)
    {
        try {
            $rules = [
                'clave' => 'required|string|max:150|unique:configuraciones,clave,' . $id,
                'tipo' => 'required|in:texto,imagen,color,numero,booleano',
                'descripcion' => 'nullable|string|max:255',
                'is_active' => 'boolean'
            ];

            // Si el tipo es imagen, valor puede ser file
            if ($request->input('tipo') === 'imagen') {
                $rules['valor'] = 'nullable|file|mimes:jpg,jpeg,png,webp|max:4096';
            } else {
                $rules['valor'] = 'nullable|string';
            }

            $validated = $request->validate($rules);


            $rutaValor = $validated['valor'] ?? $request->input('valor_actual');

            // 📸 Si sube nueva imagen
            if ($request->hasFile('valor') && $validated['tipo'] === 'imagen') {
                $archivo = $request->file('valor');
                $nombre = 'config_' . Str::random(10) . '.' . $archivo->getClientOriginalExtension();

                $directorio = 'C:/xampp/htdocs/imagenes_configuraciones';
                if (!file_exists($directorio)) {
                    mkdir($directorio, 0777, true);
                }

                $archivo->move($directorio, $nombre);
                $rutaValor = 'imagenes_configuraciones/' . $nombre;
            }

            AdministracionModel::actualizarconfiguracion($id, $validated, $rutaValor);

            $this->registrarBitacora('Actualizar', 'configuraciones', $id, 'Se actualizó la configuración: ' . $validated['clave']);

            return response()->json(['message' => 'Configuración actualizada correctamente.'], 200);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al actualizar la configuración: ' . $e->getMessage()], 500);
        }
    }


    public function cambiarConfiguracion($id, Request $request)
    {
        try {
            $validated = $request->validate(['is_active' => 'required|boolean']);

            AdministracionModel::cambiarConfiguracion($id, $validated['is_active']);

            $this->registrarBitacora('Actualizar', 'configuraciones', $id, 'Se cambió el estado de la configuración.');

            return response()->json([
                'estado' => 1,
                'mensaje' => 'Estado actualizado correctamente.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'estado' => 0,
                'mensaje' => 'Error al cambiar el estado.',
                'detalle' => $e->getMessage()
            ], 500);
        }
    }


    // ✅ CRUD MODULO UBICACIONES
    public function listarUbicaciones()
    {
        return response()->json(AdministracionModel::listarUbicaciones());
    }

    public function registrarUbicacion(Request $request)
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:255|unique:ubicaciones,nombre',
                'is_active' => 'boolean'
            ]);

            $id = AdministracionModel::registrarUbicacion($validated);

            $this->registrarBitacora('Crear', 'ubicaciones', $id, 'Se registró la ubicación: ' . $validated['nombre']);

            return response()->json(['message' => 'Ubicación registrada correctamente.'], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al registrar la ubicación: ' . $e->getMessage()], 500);
        }
    }

    public function actualizarUbicacion(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:255|unique:ubicaciones,nombre,' . $id,
                'is_active' => 'boolean'
            ]);

            AdministracionModel::actualizarUbicacion($id, $validated);

            $this->registrarBitacora('Actualizar', 'ubicaciones', $id, 'Se actualizó la ubicación: ' . $validated['nombre']);

            return response()->json(['message' => 'Ubicación actualizada correctamente.'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al actualizar la ubicación: ' . $e->getMessage()], 500);
        }
    }
  
    public function cambiarUbicacion($id, Request $request)
    {
        try {
            $validated = $request->validate(['is_active' => 'required|boolean']);

            AdministracionModel::cambiarUbicacion($id, $validated['is_active']);

            $this->registrarBitacora('Actualizar', 'ubicaciones', $id, 'Se cambió el estado de la ubicación.');

            return response()->json([
                'estado' => 1,
                'mensaje' => 'Estado actualizado correctamente.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'estado' => 0,
                'mensaje' => 'Error al cambiar el estado.',
                'detalle' => $e->getMessage()
            ], 500);
        }
    }


    // ✅ CRUD MODULO BITACORA
    public function listarbitacora()
    {
        return response()->json(AdministracionModel::listarbitacora());
    }



    public function listarSliders()
    {
        return response()->json(AdministracionModel::listarSliders());
    }

    public function registrarSlider(Request $request)
    {
        try {
            $validated = $request->validate([
                'titulo' => 'required|string|max:255',
                'descripcion' => 'nullable|string',
                'orden' => 'nullable|integer|min:0',
                'is_active' => 'boolean',
                'imagen_url' => 'required|file|mimes:jpg,jpeg,png,webp',
            ]);

            // Procesar imagen
            $archivo = $request->file('imagen_url');
            $nombre = 'slider_' . Str::random(10) . '.' . $archivo->getClientOriginalExtension();

            $directorio = 'C:/xampp/htdocs/sliders';
            if (!file_exists($directorio)) mkdir($directorio, 0777, true);

            $archivo->move($directorio, $nombre);

            // Ruta almacenada en BD
            $rutaImagen = 'sliders/' . $nombre;

            // Agregar ruta al array validado
            $validated['imagen_url'] = $rutaImagen;

            // Registrar slider
            $id = AdministracionModel::registrarSlider($validated);

            // Bitácora
            $this->registrarBitacora('Crear', 'sliders', $id, 'Se registró slider: ' . $validated['titulo']);

            return response()->json(['message' => 'Slider registrado correctamente'], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al registrar slider: ' . $e->getMessage()
            ], 500);
        }
    }


    public function actualizarSlider(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'titulo' => 'required|string|max:255',
                'descripcion' => 'nullable|string',
                'orden' => 'nullable|integer|min:0',
                'is_active' => 'boolean',
                'imagen_url' => 'nullable|file|mimes:jpg,jpeg,png,webp'
            ]);

            // Obtener slider actual
            $sliderActual = AdministracionModel::obtenerSliderPorId($id);

            if (!$sliderActual) {
                return response()->json(['error' => 'Slider no encontrado'], 404);
            }

            // Si viene una nueva imagen → procesar
            if ($request->hasFile('imagen_url')) {

                $archivo = $request->file('imagen_url');
                $nombre = 'slider_' . Str::random(10) . '.' . $archivo->getClientOriginalExtension();

                $directorio = 'C:/xampp/htdocs/sliders';
                if (!file_exists($directorio)) mkdir($directorio, 0777, true);

                $archivo->move($directorio, $nombre);

                $rutaImagen = 'sliders/' . $nombre;

            } else {

                // ❗ NO actualizar la imagen → mantener la actual
                $rutaImagen = $sliderActual->imagen_url;
            }

            AdministracionModel::actualizarSlider($id, $validated, $rutaImagen);

            $this->registrarBitacora(
                'Actualizar',
                'sliders',
                $id,
                'Se actualizó el slider: ' . $validated['titulo']
            );

            return response()->json(['message' => 'Slider actualizado correctamente'], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al actualizar slider: ' . $e->getMessage()
            ], 500);
        }
    }



    public function cambiarEstadoSlider($id, Request $request)
    {
        try {
            $validated = $request->validate(['is_active' => 'required|boolean']);

            DB::table('sliders')
                ->where('id', $id)
                ->update(['is_active' => $validated['is_active'], 'updated_at' => now()]);

            $this->registrarBitacora('Actualizar', 'sliders', $id, 'Se cambió el estado del slider.');

            return response()->json(['estado' => 1, 'mensaje' => 'Estado actualizado correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['estado' => 0, 'mensaje' => 'Error: ' . $e->getMessage()], 500);
        }
    }


    //CRUD MODULO POPPUS
    public function listarPopups()
    {
        return response()->json(AdministracionModel::listarPopups());
    }

    public function listarcolor()
    {
        return response()->json(AdministracionModel::listarcolor());
    }


    public function getPopupConfig()
    {
        return response()->json(AdministracionModel::getPopupConfig());
    }

    public function listarPopups2()
    {
        return response()->json(AdministracionModel::listarPopups2());
    }

    

    public function registrarPopups(Request $request)
    {
        try {
            $validated = $request->validate([
                'titulo' => 'required|string|max:255',
                'descripcion' => 'nullable|string',
                'orden' => 'nullable|integer|min:0',
                'is_active' => 'boolean',
                'imagen_url' => 'required|file|mimes:jpg,jpeg,png,webp',
            ]);

            // Procesar imagen
            $archivo = $request->file('imagen_url');
            $nombre = 'slider_' . Str::random(10) . '.' . $archivo->getClientOriginalExtension();

            $directorio = 'C:/xampp/htdocs/popups';
            if (!file_exists($directorio)) mkdir($directorio, 0777, true);

            $archivo->move($directorio, $nombre);

            // Ruta almacenada en BD
            $rutaImagen = 'popups/' . $nombre;

            // Agregar ruta al array validado
            $validated['imagen_url'] = $rutaImagen;

            // Registrar slider
            $id = AdministracionModel::registrarPopups($validated);

            // Bitácora
            $this->registrarBitacora('Crear', 'popups', $id, 'Se registró slider: ' . $validated['titulo']);

            return response()->json(['message' => 'Slider registrado correctamente'], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al registrar slider: ' . $e->getMessage()
            ], 500);
        }
    }


    public function actualizarPopups(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'titulo' => 'required|string|max:255',
                'descripcion' => 'nullable|string',
                'orden' => 'nullable|integer|min:0',
                'is_active' => 'boolean',
                'imagen_url' => 'nullable|file|mimes:jpg,jpeg,png,webp'
            ]);

            // Obtener slider actual
            $sliderActual = AdministracionModel::obtenerPopupsPorId($id);

            if (!$sliderActual) {
                return response()->json(['error' => 'Slider no encontrado'], 404);
            }

            // Si viene una nueva imagen → procesar
            if ($request->hasFile('imagen_url')) {

                $archivo = $request->file('imagen_url');
                $nombre = 'slider_' . Str::random(10) . '.' . $archivo->getClientOriginalExtension();

                $directorio = 'C:/xampp/htdocs/popups';
                if (!file_exists($directorio)) mkdir($directorio, 0777, true);

                $archivo->move($directorio, $nombre);

                $rutaImagen = 'popups/' . $nombre;

            } else {

                // ❗ NO actualizar la imagen → mantener la actual
                $rutaImagen = $sliderActual->imagen_url;
            }

            AdministracionModel::actualizarPopups($id, $validated, $rutaImagen);

            $this->registrarBitacora(
                'Actualizar',
                'sliders',
                $id,
                'Se actualizó el slider: ' . $validated['titulo']
            );

            return response()->json(['message' => 'Slider actualizado correctamente'], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al actualizar slider: ' . $e->getMessage()
            ], 500);
        }
    }



    public function cambiarEstadoPopups($id, Request $request)
    {
        try {
            $validated = $request->validate(['is_active' => 'required|boolean']);

            DB::table('popups')
                ->where('id', $id)
                ->update(['is_active' => $validated['is_active'], 'updated_at' => now()]);

            $this->registrarBitacora('Actualizar', 'sliders', $id, 'Se cambió el estado del slider.');

            return response()->json(['estado' => 1, 'mensaje' => 'Estado actualizado correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['estado' => 0, 'mensaje' => 'Error: ' . $e->getMessage()], 500);
        }
    }



    //CRUD MODULO USUARIOS
    public function listarUsuarios()
    {
        return response()->json(AdministracionModel::listarUsuarios());
    }

    public function registrarUsuarios(Request $request)
    {
        try {
            $validated = $request->validate([
                'perfil_id' => 'required|integer',
                'nombre' => 'required|string|max:255',
                'apellido' => 'nullable|string|max:255',
                'razon_social' => 'nullable|string|max:255',
                'email' => 'required|email|unique:usuario,email',
                'password' => 'nullable|string|min:6',
                'tipo_documento_id' => 'required|integer',
                'numero_documento' => 'required|string|unique:usuario,numero_documento',
                'telefono' => 'nullable|string|max:255',
                'telefono_movil' => 'nullable|string|max:255',
                'is_active' => 'boolean',
            ]);

            // Si no envía contraseña → generar una por defecto
            $validated['password'] = !empty($validated['password'])
                ? bcrypt($validated['password'])
                : bcrypt('123456');

            $id = AdministracionModel::registrarUsuarios($validated);

            return response()->json(['message' => 'Usuario registrado correctamente'], 201);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error: ' . $e->getMessage()], 500);
        }
    }


    public function actualizarUsuarios(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'perfil_id' => 'required|integer',
                'nombre' => 'required|string|max:255',
                'apellido' => 'nullable|string|max:255',
                'razon_social' => 'nullable|string|max:255',
                'email' => 'required|email|unique:usuario,email,' . $id,
                'password' => 'nullable|string|min:6',
                'tipo_documento_id' => 'required|integer',
                'numero_documento' => 'required|string|unique:usuario,numero_documento,' . $id,
                'telefono' => 'nullable|string|max:255',
                'telefono_movil' => 'nullable|string|max:255',
                'is_active' => 'boolean',
            ]);

            $usuarioActual = AdministracionModel::obtenerUsuarioPorId($id);

            if (!$usuarioActual) {
                return response()->json(['error' => 'Usuario no encontrado'], 404);
            }

            // Mantener la contraseña si viene vacía
            $validated['password'] = !empty($validated['password'])
                ? bcrypt($validated['password'])
                : $usuarioActual->password;

            AdministracionModel::actualizarUsuarios($id, $validated);

            return response()->json(['message' => 'Usuario actualizado correctamente'], 200);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error: ' . $e->getMessage()], 500);
        }
    }


    public function cambiarEstadoUsuarios($id, Request $request)
    {
        try {
            $validated = $request->validate(['is_active' => 'required|boolean']);

            DB::table('usuario')
                ->where('id', $id)
                ->update([
                    'is_active' => $validated['is_active'],
                    'updated_at' => now()
                ]);

            $this->registrarBitacora('Actualizar', 'usuarios', $id, 'Se cambió el estado del usuario.');

            return response()->json(['estado' => 1, 'mensaje' => 'Estado actualizado correctamente.']);

        } catch (\Exception $e) {
            return response()->json(['estado' => 0, 'mensaje' => 'Error: ' . $e->getMessage()], 500);
        }
    }


    public function subirperfilusuario(Request $request, $id)
    {
        try {
            // Validar solo imagen
            $validated = $request->validate([
                'imagen' => 'nullable|file|mimes:jpg,jpeg,png,webp'
            ]);

            // Obtener usuario actual
            $usuario = AdministracionModel::obtenerUsuarioPorId($id);

            if (!$usuario) {
                return response()->json(['error' => 'Usuario no encontrado'], 404);
            }

            // Si viene nueva imagen → procesar
            if ($request->hasFile('imagen')) {

                $archivo = $request->file('imagen');
                $nombre = 'perfil_' . Str::random(10) . '.' . $archivo->getClientOriginalExtension();

                $directorio = 'C:/xampp/htdocs/perfiles';
                if (!file_exists($directorio)) mkdir($directorio, 0777, true);

                $archivo->move($directorio, $nombre);

                $rutaImagen = 'perfiles/' . $nombre;

            } else {
                // Mantener imagen actual
                $rutaImagen = $usuario->imagen;
            }

            // Actualizar solo la imagen en BD
            AdministracionModel::actualizarImagenUsuario($id, $rutaImagen);

            return response()->json([
                'exito' => true,
                'message' => 'Imagen de perfil actualizada correctamente',
                'imagen' => $rutaImagen
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'exito' => false,
                'error' => 'Error al actualizar la imagen: ' . $e->getMessage()
            ], 500);
        }
    }


    //CRUD MODELO CONFIGURACION SCRITS
    public function obtenerscripts()
    {
        return response()->json(AdministracionModel::obtenerScripts());
    }

    public function listarScripts()
    {
        return response()->json(
            AdministracionModel::listarScripts()
        );
    }

    public function registrarScripts(Request $request)
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'script_head' => 'nullable|string',
                'script_body' => 'nullable|string',
                'is_active' => 'required|integer'
            ]);
            AdministracionModel::registrarScripts($validated);

            return response()->json(['message' => 'Script registrado correctamente'], 200);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function actualizarScripts(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'script_head' => 'nullable|string',
                'script_body' => 'nullable|string',
                'is_active' => 'required|integer'
            ]);

            AdministracionModel::actualizarScripts($id, $validated);

            return response()->json(['message' => 'Script actualizado correctamente'], 200);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function cambiarEstadoScripts(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'is_active' => 'required|integer'
            ]);

            AdministracionModel::cambiarEstadoScripts($id, $validated['is_active']);

            return response()->json(['message' => 'Estado actualizado correctamente'], 200);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    //CRUD MODULO POPUPS CONFIGURACIONES
    public function listarPopupConfig()
    {
        return DB::table('popup_config')->first();
    }

    public function actualizarPopupConfig(Request $request, $id)
    {
        $request->validate([
            'tiempo_inicio_seg' => 'required|integer|min:1',
        ]);

        DB::table('popup_config')->where('id', $id)->update([
            'tiempo_inicio_seg' => $request->tiempo_inicio_seg,
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Configuración actualizada']);
    }




     // ---------------------------------------------------------
    // CRUD PLANES CLUB
    // ---------------------------------------------------------
    public function listarPlanesclub()
    {
        return response()->json(AdministracionModel::listarPlanesclub());
    }

    public function registrarPlanesclub(Request $request)
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'descripcion' => 'nullable|string',
                //'descripcion' => 'nullable|string|max:255',
                'precio' => 'required|numeric|min:0',
                'duracion_dias' => 'required|integer|min:1',
                'is_active' => 'boolean',
            ]);

            $idPlan = AdministracionModel::crearPlanclub($validated);

            // 🔹 Bitácora
            $this->registrarBitacora('Crear', 'planesclub', $idPlan, 'Se creó el plan: ' . $validated['nombre']);

            return response()->json(['estado' => 1, 'mensaje' => 'Plan registrado correctamente.', 'id' => $idPlan], 201);
        } catch (\Exception $e) {
            return response()->json(['estado' => 0, 'mensaje' => 'Error al registrar el plan', 'detalle' => $e->getMessage()], 500);
        }
    }

    public function actualizarPlanesclub(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                //'descripcion' => 'nullable|string|max:255',
                'descripcion' => 'nullable|string',
                'precio' => 'required|numeric|min:0',
                'duracion_dias' => 'required|integer|min:1',
                'is_active' => 'boolean',
            ]);

            AdministracionModel::actualizarPlanclub($id, $validated);

            // 🔹 Bitácora
            $this->registrarBitacora('Actualizar', 'planesclub', $id, 'Se actualizó el plan: ' . $validated['nombre']);

            return response()->json(['estado' => 1, 'mensaje' => 'Plan actualizado correctamente.'], 200);
        } catch (\Exception $e) {
            return response()->json(['estado' => 0, 'mensaje' => 'Error al actualizar el plan', 'detalle' => $e->getMessage()], 500);
        }
    }

    public function cambiarEstadoPlanclub($id, Request $request)
    {
        try {
            $validated = $request->validate(['is_active' => 'required|boolean']);

            DB::table('planesclub')->where('id', $id)->update([
                'is_active' => $validated['is_active'],
                'updated_at' => now(),
            ]);

            // 🔹 Bitácora
            $this->registrarBitacora('Actualizar', 'planesclub', $id, 'Se cambió el estado del plan.');

            return response()->json(['estado' => 1, 'mensaje' => 'Estado actualizado correctamente.'], 200);
        } catch (\Exception $e) {
            return response()->json(['estado' => 0, 'mensaje' => 'Error al cambiar estado del plan', 'detalle' => $e->getMessage()], 500);
        }
    }

    // ================================
    // PLANES DE USUARIOS CLUB
    // ================================

    public function listarPlanesUsuarioclub()
    {
        return response()->json(AdministracionModel::listarPlanesUsuarioclub());
    }

    public function registrarPlanesUsuarioclub(Request $request)
    {
        try {
            $validated = $request->validate([
                'usuario_id' => 'required|integer|exists:usuarios,id',
                'plan_id' => 'required|integer|exists:planes,id',
                'fecha_inicio' => 'required|date',
                'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
                'anuncios_disponibles' => 'nullable|integer|min:0',
                'estado' => 'nullable|string|in:activo,inactivo',
            ]);

            $idPlanUsuario = AdministracionModel::crearPlanUsuarioclub($validated);

            // 🔹 Bitácora
            $this->registrarBitacora('Crear', 'planes_usuarioclub', $idPlanUsuario, 'Se asignó un plan a un usuario.');

            return response()->json(['estado' => 1, 'mensaje' => 'Plan de usuario registrado correctamente.', 'id' => $idPlanUsuario], 201);
        } catch (\Exception $e) {
            return response()->json(['estado' => 0, 'mensaje' => 'Error al registrar el plan de usuario', 'detalle' => $e->getMessage()], 500);
        }
    }

    public function actualizarPlanesUsuarioclub(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'plan_id' => 'required|integer|exists:planes,id',
                'fecha_inicio' => 'required|date',
                'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
                'anuncios_disponibles' => 'nullable|integer|min:0',
                'estado' => 'required|in:activo,vencido',
            ]);

            AdministracionModel::actualizarPlanUsuarioclub($id, $validated);

            // 🔹 Bitácora
            $this->registrarBitacora('Actualizar', 'planes_usuarioclub', $id, 'Se actualizó el plan de usuario.');

            return response()->json(['estado' => 1, 'mensaje' => 'Plan de usuario actualizado correctamente.'], 200);
        } catch (\Exception $e) {
            return response()->json(['estado' => 0, 'mensaje' => 'Error al actualizar el plan de usuario', 'detalle' => $e->getMessage()], 500);
        }
    }

    public function cambiarEstadoPlanesUsuarioclub($id, Request $request)
    {
        try {
            $validated = $request->validate(['estado' => 'required|string|in:activo,inactivo']);

            DB::table('usuarios_planesclub')->where('id', $id)->update([
                'estado' => $validated['estado'],
                'updated_at' => now(),
            ]);

            // 🔹 Bitácora
            $this->registrarBitacora('Actualizar', 'planes_usuarioclub', $id, 'Se cambió el estado del plan de usuario.');

            return response()->json(['estado' => 1, 'mensaje' => 'Estado del plan de usuario actualizado correctamente.'], 200);
        } catch (\Exception $e) {
            return response()->json(['estado' => 0, 'mensaje' => 'Error al cambiar estado del plan de usuario', 'detalle' => $e->getMessage()], 500);
        }
    }


     // ---------------------------------------------------------
    // CRUD CARACTERÍSTICAS CATALOGO CLUB
    // ---------------------------------------------------------
    public function listarCaracteristicasCatalogoclub()
    {
        return response()->json(AdministracionModel::listarCaracteristicasCatalogoclub());
    }

    /*public function registrarCaracteristicaCatalogo(Request $request)
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'unidad' => 'required|string|max:50',
                'tpropiedad_id' => 'required|integer',
                'is_active' => 'boolean',
                'icono' => 'nullable|image|mimes:png,jpg,jpeg,svg',
            ]);

            if ($request->hasFile('icono')) {
                $path = $request->file('icono')->store('iconos_caracteristicas', 'public');
                $validated['icono'] = $path;
            }

            $id = AdministracionModel::registrarCaracteristicaCatalogo($validated);

            // 🔹 Bitácora
            $this->registrarBitacora('Crear', 'caracteristicas_catalogo', $id, 'Se registró característica: ' . $validated['nombre']);

            return response()->json(['message' => 'Característica registrada correctamente'], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al registrar característica: ' . $e->getMessage()], 500);
        }
    }*/

    public function registrarCaracteristicaCatalogoclub(Request $request)
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'unidad' => 'required|string|max:50',
                'tpropiedad_id' => 'required|integer',
                'is_active' => 'boolean',
                'icono' => 'nullable|file|mimes:png,jpg,jpeg,svg',
            ]);

            $rutaIcono = null;

            // ✅ Si se envía un icono, lo guardamos físicamente
            if ($request->hasFile('icono')) {
                $archivo = $request->file('icono');
                $nombre = 'icono_' . Str::random(10) . '.' . $archivo->getClientOriginalExtension();

                // 🔹 Directorio donde se guardarán los iconos
                $directorio = 'C:/xampp/htdocs/iconosclub';

                if (!file_exists($directorio)) {
                    mkdir($directorio, 0777, true);
                }

                // 🔹 Mover archivo físico
                $archivo->move($directorio, $nombre);

                // 🔹 Ruta que se guarda en la BD (relativa)
                $rutaIcono = 'iconosclub/' . $nombre;
            }

            $validated['icono'] = $rutaIcono;

            // ✅ Guardamos la característica en la BD
            $id = AdministracionModel::registrarCaracteristicaCatalogoclub($validated);

            // 🔹 Bitácora
            $this->registrarBitacora('Crear', 'caracteristicas_catalogo', $id, 'Se registró característica: ' . $validated['nombre']);

            return response()->json(['message' => 'Característica registrada correctamente'], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al registrar característica: ' . $e->getMessage()], 500);
        }
    }


    public function actualizarCaracteristicaCatalogoclub(Request $request, $id)
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
                $directorio = 'C:/xampp/htdocs/iconosclub';
                if (!file_exists($directorio)) mkdir($directorio, 0777, true);
                $archivo->move($directorio, $nombre);
                $rutaIcono = $nombre;
            } else {
                $rutaIcono = $request->input('icono_actual');
            }

            AdministracionModel::actualizarCaracteristicaCatalogoclub($id, $validated, $rutaIcono);

            // 🔹 Bitácora
            $this->registrarBitacora('Actualizar', 'caracteristicas_catalogo', $id, 'Se actualizó característica: ' . $validated['nombre']);

            return response()->json(['message' => 'Característica actualizada correctamente'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al actualizar característica: ' . $e->getMessage()], 500);
        }
    }

    public function cambiarEstadoCaracteristicaCatalogoclub($id, Request $request)
    {
        try {
            $validated = $request->validate(['is_active' => 'required|boolean']);

            DB::table('caracteristicas_catalogoclub')
                ->where('id', $id)
                ->update(['is_active' => $validated['is_active'], 'updated_at' => now()]);

            // 🔹 Bitácora
            $this->registrarBitacora('Actualizar', 'caracteristicas_catalogoclub', $id, 'Se cambió el estado de la característica.');

            return response()->json(['estado' => 1, 'mensaje' => 'Estado actualizado correctamente.'], 200);
        } catch (\Exception $e) {
            return response()->json(['estado' => 0, 'mensaje' => 'Error: ' . $e->getMessage()], 500);
        }
    }


     // ---------------------------------------------------------
    // CRUD AMENITIES
    // ---------------------------------------------------------
    public function listarAmenitiesclub()
    {
        return response()->json(AdministracionModel::listarAmenitiesclub());
    }

    public function registrarAmenityclub(Request $request)
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'tpropiedad_id' => 'required|integer',
                'is_active' => 'boolean',
            ]);

            $id = AdministracionModel::registrarAmenityclub($validated);

            // 🔹 Bitácora
            $this->registrarBitacora('Crear', 'amenitiesclub', $id, 'Se registró amenity club: ' . $validated['nombre']);

            return response()->json(['message' => 'Amenity club registrado correctamente'], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al registrar el amenity: ' . $e->getMessage()], 500);
        }
    }

    public function actualizarAmenityclub(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'tpropiedad_id' => 'required|integer',
                'is_active' => 'boolean',
            ]);

            AdministracionModel::actualizarAmenityclub($id, $validated);

            // 🔹 Bitácora
            $this->registrarBitacora('Actualizar', 'amenities club', $id, 'Se actualizó amenity: ' . $validated['nombre']);

            return response()->json(['message' => 'Amenity actualizado correctamente'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al actualizar el amenity: ' . $e->getMessage()], 500);
        }
    }

    public function cambiarEstadoAmenityclub($id, Request $request)
    {
        try {
            $validated = $request->validate(['is_active' => 'required|boolean']);

            DB::table('amenitiesclub')->where('id', $id)->update([
                'is_active' => $validated['is_active'],
                'updated_at' => now(),
            ]);

            // 🔹 Bitácora
            $this->registrarBitacora('Actualizar', 'amenities club', $id, 'Se cambió el estado del amenity.');

            return response()->json(['estado' => 1, 'mensaje' => 'Estado del servicio actualizado correctamente.'], 200);
        } catch (\Exception $e) {
            return response()->json(['estado' => 0, 'mensaje' => 'Error: ' . $e->getMessage()], 500);
        }
    }




    
    public function listarmensajecontactos($id)
    {
        return response()->json(AdministracionModel::listarmensajecontactos($id));
    }
    
    public function listarvisitaspropiedad($id)
    {
        return response()->json(AdministracionModel::listarvisitaspropiedad($id));
    }
} 
 