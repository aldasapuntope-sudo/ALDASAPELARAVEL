<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AnunciosModel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
//use App\Helpers\BitacoraHelper;

class AnunciosController extends Controller
{

    
    public function sumarVisita($id)
    {
        $resultado = AnunciosModel::sumarVisita($id);
        return response()->json($resultado);
    }

    public function listarplanos($id)
    {
        $resultado = AnunciosModel::listarplanos($id);
        return response()->json($resultado);
    }

    public function eliminarplanos($id)
    {
        $resultado = AnunciosModel::eliminarplanos($id);
        
        if ($resultado > 0) {
            return response()->json(['success' => true, 'message' => 'Plano eliminado correctamente']);
        } else {
            return response()->json(['success' => false, 'message' => 'No se pudo eliminar el plano']);
        }
    }
    

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
                'imagen_principal' => 'nullable|image',
                'user_id' => 'required|integer',
                'direccion' => 'required|string',
            ]);

            $userId = $request->user_id;

            // 2️⃣ Verificar plan activo
            $plan = DB::table('usuarios_planes')
                ->where('user_id', $userId)
                ->where('is_active', 1)
                ->first();

            if (!$plan) {
                return response()->json([
                    'estado' => 0,
                    'mensaje' => 'No tienes un plan activo para publicar anuncios.',
                ], 403);
            }

            // 3️⃣ Verificar vencimiento del plan
            if (Carbon::now()->gt(Carbon::parse($plan->fecha_fin))) {
                DB::table('usuarios_planes')
                    ->where('id', $plan->id)
                    ->update(['estado' => 'vencido', 'is_active' => 0]);

                return response()->json([
                    'estado' => 0,
                    'mensaje' => 'Tu plan ha vencido. Renueva tu suscripción para continuar publicando.',
                ], 403);
            }

            // 4️⃣ Contar anuncios existentes
            $totalAnuncios = DB::table('propiedades')
                ->where('user_id', $userId)
                ->where('is_active', 1)
                ->count();

            if ($totalAnuncios >= $plan->anuncios_disponibles) {
                return response()->json([
                    'estado' => 0,
                    'mensaje' => 'Has alcanzado el límite de anuncios disponibles en tu plan.',
                ], 403);
            }

            // 5️⃣ Subir imagen principal
            $rutaImagen = null;
            if ($request->hasFile('imagen_principal')) {
                $archivo = $request->file('imagen_principal');
                $nombre = 'propiedad_' . Str::random(10) . '.' . $archivo->getClientOriginalExtension();

                $directorioPropiedades = 'C:/xampp/htdocs/propiedades';
                if (!file_exists($directorioPropiedades)) {
                    mkdir($directorioPropiedades, 0777, true);
                }

                $archivo->move($directorioPropiedades, $nombre);
                $rutaImagen = 'http://localhost/propiedades/' . $nombre;
            }

            // 6️⃣ Crear anuncio principal
            $idPropiedad = AnunciosModel::crearAnuncio($validated, $rutaImagen);

            // 7️⃣ Guardar características
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

            // 8️⃣ Subir y guardar planos
            if ($request->has('planos')) {
                $planosData = $request->planos; // array con ['archivo', 'titulo']

                foreach ($planosData as $plano) {
                    if (isset($plano['archivo'])) {
                        $archivo = $plano['archivo'];
                        $titulo = $plano['titulo'] ?? '';

                        $directorioPlanos = 'C:/xampp/htdocs/planos';
                        if (!file_exists($directorioPlanos)) mkdir($directorioPlanos, 0777, true);

                        $nombrePlano = 'plano_' . Str::random(10) . '.' . $archivo->getClientOriginalExtension();
                        $archivo->move($directorioPlanos, $nombrePlano);

                        AnunciosModel::guardarPlanos($idPropiedad, $titulo, $nombrePlano);
                    }
                }
            }

            // 9️⃣ Respuesta exitosa
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




    /*public function registraranuncio(Request $request)
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
                'direccion' => 'required|string',
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
    }*/


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
                'direccion' => 'required|string',
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

            // 7️⃣ Actualizar planos (nuevos)
           if ($request->has('planos')) {
                $planosData = $request->planos; // array con ['archivo', 'titulo'] por cada índice

                // Eliminar planos anteriores si quieres reemplazarlos
                //DB::table('propiedad_planos')->where('propiedad_id', $id)->delete();

                foreach ($planosData as $plano) {
                    if (isset($plano['archivo'])) {
                        $archivo = $plano['archivo']; // esto ya es un UploadedFile
                        $titulo = $plano['titulo'] ?? '';

                        $directorioPlanos = 'C:/xampp/htdocs/planos';
                        if (!file_exists($directorioPlanos)) mkdir($directorioPlanos, 0777, true);

                        $nombrePlano = 'plano_' . Str::random(10) . '.' . $archivo->getClientOriginalExtension();
                        $archivo->move($directorioPlanos, $nombrePlano);

                        AnunciosModel::guardarPlanos($id, $titulo, $nombrePlano);
                        /*DB::table('propiedad_planos')->insert([
                            'propiedad_id' => $id,
                            'titulo' => $titulo,
                            'imagen' => 'planos/' . $nombrePlano,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);*/
                    }
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



    public function categoriasCatalogo($tpropiedad)
    {
        $resultado = AnunciosModel::categoriasCatalogo($tpropiedad);
        return response()->json($resultado);
    }

    public function categoriasCatalogoid($id)
    {
        $resultado = AnunciosModel::categoriasCatalogoid($id);
        return response()->json($resultado);
    }
    
    public function amenities($tpropiedad)
    {
        $resultado = AnunciosModel::amenities($tpropiedad);
        return response()->json($resultado);
    }

    public function amenitiesid($id)
    {
        $resultado = AnunciosModel::amenitiesid($id);
        return response()->json($resultado);
    }


    //PAGINA PRINCIPAL
    public function listaranuncioprincipal($idpublish)
    {
        $resultado = AnunciosModel::listaranuncioprincipal($idpublish);
        return response()->json($resultado);
    }

    public function listardetalleprincipal($idpublish)
    {
        $resultado = AnunciosModel::listardetalleprincipal($idpublish);
        return response()->json($resultado);
    }



    //PARTE DE MENSAES DE LA PAGINA PRINCIPAL DETALLE AL ANUNCIANTE
    public function registrarmensajeanunciante(Request $request)
    {
        try {
            // Validación directa (lanza excepción si falla)
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'telefono' => 'required|string|max:10',
                'dni' => 'required|string|max:10',
                'mensaje' => 'required|string',
                'anuncioid' => 'required|integer',
                
            ]);

            // Crear mensaje
            $mensaje = AnunciosModel::guardarMensaje(
                $validated['nombre'],
                $validated['email'],
                $validated['telefono'],
                $validated['dni'],
                $validated['mensaje'],
                $validated['anuncioid']
            );

            return response()->json([
                'success' => true,
                'message' => 'Mensaje enviado correctamente',
                'data' => $mensaje
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Si la validación falla, devuelve errores JSON
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            // Cualquier otro error
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el mensaje',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    //FILTROS PAGINA PRINCIPAL
    public function getRelacionadas($tipo_id, $idActual)
    {
        $relacionadas = AnunciosModel::where('tipo_id', $tipo_id)
            ->where('id', '!=', $idActual) // Excluye la actual
            ->where('is_active_publish', 1)
            ->where('is_active', 1)
            ->orderBy('visitas', 'desc') // 👈 Ordenar por más visitas
            ->limit(4)
            ->get(['id', 'titulo', 'precio', 'imagen_principal', 'direccion', 'operacion_id', 'visitas']);

        return response()->json([
            'success' => true,
            'data' => $relacionadas
        ]);
    }

    public function buscar(Request $request)
    {
        $query = AnunciosModel::query()
            ->where('is_active_publish', 1)
            ->where('is_active', 1);

        if ($request->filled('tipo')) {
            $query->where('tipo_id', $request->tipo);
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('titulo', 'like', "%$q%")
                    ->orWhere('direccion', 'like', "%$q%");
            });
        }

        if ($request->filled('mode')) {
            if ($request->mode === 'comprar') {
                $query->where('operacion_id', 1);
            } elseif ($request->mode === 'alquilar') {
                $query->where('operacion_id', 2);
            }
        }

        $resultados = $query
            ->select('id', 'titulo', 'precio', 'imagen_principal', 'direccion', 'operacion_id')
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        return response()->json(['success' => true, 'data' => $resultados]);
    }

    public function buscarPropiedad(Request $request)
    {
        $q = $request->query('q');
        $tipo = $request->query('tipo');

        $query = DB::table('propiedades as p')
            ->join('ubicaciones as u', 'p.ubicacion_id', '=', 'u.id')
            ->join('tipos_propiedad as tp', 'p.tipo_id', '=', 'tp.id')
            ->join('operaciones as o', 'p.operacion_id', '=', 'o.id')
            ->select(
                'p.id', 
                'u.id as id_ubicacion', 
                'u.nombre as ubicacion', 
                'tp.id as id_tipopropiedad', 
                'tp.nombre as tipo_propiedad', 
                'o.id as id_operacion', 
                'o.nombre as operaciones', 
                'p.titulo', 
                'p.descripcion', 
                'p.precio', 
                'p.direccion', 
                'p.imagen_principal', 
                'p.is_active_publish',
                'p.visitas',
                'p.created_at'
            )
            ->where('p.is_active', 1)
            ->where('p.is_active_publish', 1);

        if ($tipo) {
            $query->where('p.tipo_id', $tipo);
        }

        if ($q) {
            $query->where(function($sub) use ($q) {
                $sub->where('p.titulo', 'like', "%{$q}%")
                    ->orWhere('p.direccion', 'like', "%{$q}%")
                    ->orWhere('p.descripcion', 'like', "%{$q}%")
                    ->orWhere('tp.nombre', 'like', "%{$q}%") // 🔍 tipo de propiedad (Casa, Departamento, etc.)
                    ->orWhere('u.nombre', 'like', "%{$q}%"); // 📍 ubicación (Chiclayo, Lambayeque, etc.)
            });
            /*$query->where(function($sub) use ($q) {
                $sub->where('p.titulo', 'like', "%{$q}%")
                    ->orWhere('p.direccion', 'like', "%{$q}%")
                    ->orWhere('p.descripcion', 'like', "%{$q}%");
            });*/
        }

        $anuncios = $query->orderBy('p.created_at', 'desc')
            ->limit(10)
            ->get();

        // Agregamos todos los detalles como en listardetalleprincipal
        foreach ($anuncios as $anuncio) {

            $perfil = DB::table('usuario as usu')
                ->join('propiedades as p', 'p.user_id', '=', 'usu.id')
                ->select('usu.id', 'usu.nombre', 'usu.apellido', 'usu.email', 'usu.telefono', 'usu.telefono_movil', 'usu.imagen')
                ->where('p.id', $anuncio->id)
                ->where('p.is_active', 1)
                ->first();

            if ($perfil) {
                $perfil->idanunciante = $perfil->id;
                unset($perfil->id);
            }

            $anuncio->perfilanunciante = $perfil;

            $anuncio->caracteristicas = DB::table('propiedad_caracteristicas as pc')
                ->join('caracteristicas_catalogo as cc', 'pc.caracteristica_id', '=', 'cc.id')
                ->select('cc.nombre', 'cc.icono', 'cc.unidad', 'pc.valor')
                ->where('pc.propiedad_id', $anuncio->id)
                ->get();

            $anuncio->amenities = DB::table('propiedad_amenities as pa')
                ->join('amenities as ac', 'pa.amenity_id', '=', 'ac.id')
                ->select('ac.nombre', 'ac.icon_url')
                ->where('pa.propiedad_id', $anuncio->id)
                ->where('pa.is_active', 1)
                ->get();

            $imagenPrincipal = collect();
            if (!empty($anuncio->imagen_principal)) {
                $imagenPrincipal->push((object)[
                    'id' => 0,
                    'titulo' => 'Imagen principal',
                    'imagen' => $anuncio->imagen_principal,
                ]);
            }

            $imagenesSecundarias = DB::table('propiedad_imagenes as img')
                ->select('img.id', 'img.titulo', 'img.imagen')
                ->where('img.propiedad_id', $anuncio->id)
                ->where('img.is_active', 1)
                ->get();

            $anuncio->imagenes = $imagenPrincipal->merge($imagenesSecundarias);

            $anuncio->planos = DB::table('propiedad_planos as pp')
                ->select('pp.id', 'pp.titulo', 'pp.imagen')
                ->where('pp.propiedad_id', $anuncio->id)
                ->where('pp.is_active', 1)
                ->get()
                ->map(function ($plano) {
                    $plano->caracteristicas = DB::table('plano_caracteristicas as pc')
                        ->select('pc.nombre', 'pc.valor', 'pc.icono')
                        ->where('pc.plano_id', $plano->id)
                        ->where('pc.is_active', 1)
                        ->get();
                    return $plano;
                });

            $anuncio->videos = DB::table('propiedad_videos as pv')
                ->select('pv.id', 'pv.titulo', 'pv.url', 'pv.tipo')
                ->where('pv.propiedad_id', $anuncio->id)
                ->where('pv.is_active', 1)
                ->get();

            $anuncio->imagen360 = DB::table('propiedad_imagenes360 as pimg')
                ->select('pimg.id', 'pimg.titulo', 'pimg.imagen')
                ->where('pimg.propiedad_id', $anuncio->id)
                ->where('pimg.is_active', 1)
                ->get();
        }

        return response()->json([
            'success' => true,
            'data' => $anuncios
        ]);
    }






}
