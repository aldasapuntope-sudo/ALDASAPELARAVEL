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
                'descripcion' => 'nullable|string|max:255',
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
                'descripcion' => 'nullable|string|max:255',
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

    public function registrarCaracteristicaCatalogo(Request $request)
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


    //CRUD MODEULO TIPO PROPIEDAD
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
}
