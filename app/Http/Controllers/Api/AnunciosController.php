<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Validator;
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

    public function getmonedas()
    {
        $resultado = AnunciosModel::getmonedas();
        return response()->json($resultado);
    }

    public function getMensajeanuncio($id)
    {
        $resultado = AnunciosModel::getMensajeanuncio($id);
        return response()->json($resultado);
    }

    public function getMensajesoporte($id)
    {
        $resultado = AnunciosModel::getMensajesoporte($id);
        return response()->json($resultado);
    }

    public function getanunciosFavoritos($id)
    {
        $resultado = AnunciosModel::getanunciosFavoritos($id);
        return response()->json($resultado);
    }



    public function sumarVisita($id, $idusuario = null)
    {
        return response()->json(
            AnunciosModel::sumarVisita($id, $idusuario)
        );
    }

    public function listarplanos($id)
    {
        $resultado = AnunciosModel::listarplanos($id);
        return response()->json($resultado);
    }

    public function listaimgsecundarias($id)
    {
        $resultado = AnunciosModel::listaimgsecundarias($id);
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
    

    public function eliminarimgsecundarias($id)
    {
        $resultado = AnunciosModel::eliminarimgsecundarias($id);
        
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
                'moneda_id' => 'required|integer|exists:monedas,id',
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
                $rutaImagen = 'propiedades/' . $nombre;
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

            // 9️⃣ Subir y guardar imágenes secundarias
            if ($request->hasFile('imagenes_secundarias')) {
                foreach ($request->file('imagenes_secundarias') as $imagenSecundaria) {
                    if ($imagenSecundaria->isValid()) {

                        $directorioImagenes = 'C:/xampp/htdocs/propiedades_imagenes';
                        if (!file_exists($directorioImagenes)) {
                            mkdir($directorioImagenes, 0777, true);
                        }

                        $nombreArchivo = 'img_' . Str::random(10) . '.' . $imagenSecundaria->getClientOriginalExtension();
                        $imagenSecundaria->move($directorioImagenes, $nombreArchivo);

                        // ✅ Llamamos al modelo
                        AnunciosModel::guardarImagenes($idPropiedad, null, $nombreArchivo);
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
                'moneda_id' => 'required|integer|exists:monedas,id',
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
                $rutaImagen = 'propiedades/' . $nombre;
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

            // 9️⃣ Subir y guardar imágenes secundarias
            if ($request->hasFile('imagenes_secundarias')) {
                foreach ($request->file('imagenes_secundarias') as $imagenSecundaria) {
                    if ($imagenSecundaria->isValid()) {

                        $directorioImagenes = 'C:/xampp/htdocs/propiedades_imagenes';
                        if (!file_exists($directorioImagenes)) {
                            mkdir($directorioImagenes, 0777, true);
                        }

                        $nombreArchivo = 'img_' . Str::random(10) . '.' . $imagenSecundaria->getClientOriginalExtension();
                        $imagenSecundaria->move($directorioImagenes, $nombreArchivo);

                        // ✅ Llamamos al modelo
                        AnunciosModel::guardarImagenes($id, null, $nombreArchivo);
                    }
                }
            }

            if ($request->filled('video_url')) {
                $video_url = trim($request->input('video_url'));

                if (!empty($video_url)) {
                    // Elimina los videos antiguos si lo deseas
                    DB::table('propiedad_videos')->where('propiedad_id', $id)->delete();

                    // Guarda el nuevo video
                    AnunciosModel::guardarvideourl($id, $video_url);
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
                'dni' => 'required|string|max:11',
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
        $relacionadas = AnunciosModel::join('monedas', 'propiedades.moneda_id', '=', 'monedas.id')
            ->where('propiedades.tipo_id', $tipo_id)
            ->where('propiedades.id', '!=', $idActual)
            ->where('propiedades.is_active_publish', 1)
            ->where('propiedades.is_active', 1)
            ->orderBy('propiedades.visitas', 'desc')
            ->limit(4)
            ->get([
                'propiedades.id',
                'propiedades.titulo',
                'propiedades.precio',
                'propiedades.imagen_principal',
                'propiedades.direccion',
                'propiedades.operacion_id',
                'propiedades.visitas',
                'monedas.nombre as moneda_nombre',
                'monedas.simbolo as moneda_simbolo',
            ]);

        return response()->json([
            'success' => true,
            'data' => $relacionadas
        ]);
    }

    /*public function getRelacionadas($tipo_id, $idActual)
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
    }*/

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
    /* =========================
       🔎 FILTROS
    ========================== */
    $q     = $request->query('q');
    $mode  = $request->query('mode');
    $tipos = $request->query('tipo');

    /* 🔧 Normalizar tipo a ARRAY */
    if (is_string($tipos)) {
        $tipos = [$tipos];
    }

    if (!is_array($tipos)) {
        $tipos = [];
    }

    /* =========================
       🧱 QUERY BASE
    ========================== */
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

    /* 🏠 TIPO DE PROPIEDAD */
    if (!empty($tipos)) {
        $query->whereIn('tp.nombre', $tipos);
    }

    /* 🔍 BÚSQUEDA GENERAL */
    if (!empty($q)) {
        $palabras = array_filter(array_map('trim', explode(',', $q)));

        $query->where(function ($sub) use ($palabras) {
            foreach ($palabras as $palabra) {
                $sub->orWhere('p.titulo', 'like', "%{$palabra}%")
                    ->orWhere('p.direccion', 'like', "%{$palabra}%")
                    ->orWhere('p.descripcion', 'like', "%{$palabra}%")
                    ->orWhere('tp.nombre', 'like', "%{$palabra}%")
                    ->orWhere('u.nombre', 'like', "%{$palabra}%");
            }
        });
    }

    /* 🔁 OPERACIÓN (Venta / Alquiler) */
    if (!empty($mode)) {
        $query->where('o.nombre', 'like', "%{$mode}%");
    }

    /* =========================
       📦 RESULTADOS
    ========================== */
    $anuncios = $query
        ->orderBy('p.created_at', 'desc')
        ->limit(10)
        ->get();

    /* =========================
       📎 DETALLES
    ========================== */
    foreach ($anuncios as $anuncio) {

        $perfil = DB::table('usuario as usu')
            ->join('propiedades as p', 'p.user_id', '=', 'usu.id')
            ->select(
                'usu.id',
                'usu.nombre',
                'usu.apellido',
                'usu.email',
                'usu.telefono',
                'usu.telefono_movil',
                'usu.imagen'
            )
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

        /* 🖼️ IMÁGENES */
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

        /* 📐 PLANOS */
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

        /* 🎥 VIDEOS */
        $anuncio->videos = DB::table('propiedad_videos as pv')
            ->select('pv.id', 'pv.titulo', 'pv.url', 'pv.tipo')
            ->where('pv.propiedad_id', $anuncio->id)
            ->where('pv.is_active', 1)
            ->get();

        /* 🌐 IMAGEN 360 */
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





    public function getQuienessomos()
    {
        $resultado = AnunciosModel::getpagina('nosotros-home');
        return response()->json($resultado);
    }

    public function getNosotros()
    {
        $resultado = AnunciosModel::getpagina('nosotros');
        return response()->json($resultado);
    }

    public function getInversiones()
    {
        $resultado = AnunciosModel::getpagina('inversionesaldasa');
        return response()->json($resultado);
    } 
    
    public function getClub()
    {
        $resultado = AnunciosModel::getpagina('clubaldasa');
        return response()->json($resultado);
    }

    public function getPlanes()
    {
        $resultado = AnunciosModel::getpagina('planesanuncio');
        return response()->json($resultado);
    }

    public function getAldasainversiones()
    {
        $resultado = AnunciosModel::getpagina('aldasainversiones');
        return response()->json($resultado);
    }


    public function gettermcondiciones()
    {
        $resultado = AnunciosModel::getpagina('terminos-condiciones');
        return response()->json($resultado);
    }


    public function getpublicatuanuncio()
    {
        $resultado = AnunciosModel::getpagina('publica-tu-aviso');
        return response()->json($resultado);
    }
    


    public function getpoliticaprivacidad()
    {
        $resultado = AnunciosModel::getpagina('politica-privacidad');
        return response()->json($resultado);
    }


    public function existeFavorito($usuario_id, $anuncio_id)
    {
        $existe = DB::table('favoritos')
            ->where('usuario_id', $usuario_id)
            ->where('anuncio_id', $anuncio_id)
            ->exists();

        return response()->json(['existe' => $existe]);
    }

    public function registrarfavoritos(Request $request)
    {
        try {
            // ✅ Validación de datos
            $validated = $request->validate([
                'usuario_id' => 'required|integer',
                'anuncio_id' => 'required|integer',
            ]);

            // ✅ Llamada al modelo
            $favorito = AnunciosModel::guardarFavorito(
                $validated['usuario_id'],
                $validated['anuncio_id']
            );

            return response()->json([
                'success' => true,
                'message' => 'Agregado a favoritos correctamente',
                'data' => $favorito
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // ❌ Error de validación
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            // ❌ Cualquier otro error
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el favorito',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function eliminarfavoritos(Request $request)
    {
        $validated = $request->validate([
            'usuario_id' => 'required|integer',
            'anuncio_id' => 'required|integer',
        ]);

        DB::table('favoritos')
            ->where('usuario_id', $validated['usuario_id'])
            ->where('anuncio_id', $validated['anuncio_id'])
            ->delete();

        return response()->json(['success' => true, 'message' => 'Eliminado de favoritos']);
    }



    public function actualizarvendido(Request $request, $id)
    {
        try {
            // Validar
            $request->validate([
                'vendido' => 'required|boolean'
            ]);

            // Convertir estado: true = 2 (vendido), false = 1 (activo)
            $nuevoEstado = $request->vendido ? 2 : 1;

            // Llamada al modelo encargado
            $resultado = AnunciosModel::actualizarEstadoVendido($id, $nuevoEstado);

            if (!$resultado) {
                return response()->json(['message' => 'No encontrado'], 404);
            }

            return response()->json(['message' => 'Estado actualizado correctamente']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }



    //MIS PROYECTOS INVERSIONES
    public function getmisproyectos()
    {
        $resultado = AnunciosModel::getMisProyectos();
        return response()->json($resultado);
    }

    public function getmisproyectosid($userId)
    {
        $resultado = AnunciosModel::getProyectoPermitido($userId);
        return response()->json($resultado);
    }


    public function getProyectoDetalle($id)
    {
        return response()->json(AnunciosModel::listarDetalleProyecto($id));
    }


    //ELIMINAR PROYECTOS INVERSIONES

    public function eliminarmultimedia($id)
    {
        $resultado = AnunciosModel::eliminarmultimedia($id);
        
        if ($resultado > 0) {
            return response()->json(['success' => true, 'message' => 'Plano eliminado correctamente']);
        } else {
            return response()->json(['success' => false, 'message' => 'No se pudo eliminar el plano']);
        }
    }

    public function eliminaretapas($id)
    {
        $resultado = AnunciosModel::eliminaretapas($id);
        
        if ($resultado > 0) {
            return response()->json(['success' => true, 'message' => 'Plano eliminado correctamente']);
        } else {
            return response()->json(['success' => false, 'message' => 'No se pudo eliminar el plano']);
        }
    }

    public function eliminarcaracteristicas($id)
    {
        $resultado = AnunciosModel::eliminarcaracteristicas($id);
        
        if ($resultado > 0) {
            return response()->json(['success' => true, 'message' => 'Plano eliminado correctamente']);
        } else {
            return response()->json(['success' => false, 'message' => 'No se pudo eliminar el plano']);
        }
    }

    public function eliminarinversionista($id)
    {
        $resultado = AnunciosModel::eliminarinversionista($id);
        
        if ($resultado > 0) {
            return response()->json(['success' => true, 'message' => 'Plano eliminado correctamente']);
        } else {
            return response()->json(['success' => false, 'message' => 'No se pudo eliminar el plano']);
        }
    }

    


    public function actualizarProyecto(Request $request, $id)
    {
        try {
            // 1️⃣ Validar los campos
            $validated = $request->validate([
                'titulo' => 'required|string|max:255',
                'descripcion' => 'required|string',
                'ubicacion' => 'required|string',
                'imagen_principal' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
                'imagen_principal_actual' => 'nullable|string'
            ]);



            // 2️⃣ Buscar anuncio existente
            $anuncio = DB::table('proyectos_inversion')->where('id', $id)->first();

            if (!$anuncio) {
                return response()->json([
                    'estado' => 0,
                    'mensaje' => 'Proyecto no encontrado.'
                ], 404);
            }

            // 3️⃣ Manejar imagen
            $rutaImagen = $anuncio->imagen_principal;

            if ($request->hasFile('imagen_principal')) {
                $archivo = $request->file('imagen_principal');
                $nombre = 'propiedad_' . Str::random(10) . '.' . $archivo->getClientOriginalExtension();

                $directorioEscritorio = 'C:/xampp/htdocs/proyectos';
                if (!file_exists($directorioEscritorio)) {
                    mkdir($directorioEscritorio, 0777, true);
                }

                $archivo->move($directorioEscritorio, $nombre);
                $rutaImagen = 'proyectos/' . $nombre;
            } else {
                // Si no hay nueva imagen, usamos la actual enviada desde el front
                if ($request->imagen_principal_actual) {
                    $rutaImagen = $request->imagen_principal_actual;
                }
            }


            // 4️⃣ Actualizar el anuncio
            AnunciosModel::actualizarProyecto($id, $validated, $rutaImagen);


            // 5️⃣ Actualizar inversionistas
            if ($request->has('inversionistas')) {
                //$inversionistas = json_decode($request->inversionistas, true);
                $inversionistas = $request->inversionistas;
                if (is_array($inversionistas)) {
                    // eliminar las antiguas
                    
                    // guardar las nuevas
                    AnunciosModel::guardarInversionistas($id, $inversionistas);
                }
            }
            

            if ($request->has('caracteristicas')) {
                //$inversionistas = json_decode($request->inversionistas, true);
                $caracteristicas = $request->caracteristicas;
                if (is_array($caracteristicas)) {
                    // eliminar las antiguas
                    
                    // guardar las nuevas
                    AnunciosModel::guardarCaracteristicasproyecto($id, $caracteristicas);
                }
            }


            if ($request->has('etapas')) {
                //$inversionistas = json_decode($request->inversionistas, true);
                $etapas = $request->etapas;
                if (is_array($etapas)) {
                    // eliminar las antiguas
                    
                    // guardar las nuevas
                    AnunciosModel::guardarEtapasProyecto($id, $etapas);
                }
            }

            // 🔹 GUARDAR MULTIMEDIA
            if ($request->has('multimedia')) {

                // 📌 Datos enviados desde el front
                $multimedia = $request->multimedia;          // id / tipo / archivo
                $files      = $request->file('multimedia') ?? []; // archivos reales (solo imágenes)

                foreach ($multimedia as $i => &$item) {

                    $multimediaId = $item['id'] ?? null;
                    $tipo         = $item['tipo'] ?? null;

                    // 🔹 valor por defecto: lo que venga del front (string)
                    $rutaFinal = $item['archivo'] ?? null;

                    /** 🔸 1. IMAGEN */
                    if ($tipo === 'imagen') {

                        // Si llega un archivo nuevo → reemplazar
                        if (isset($files[$i]['archivo']) && $files[$i]['archivo']->isValid()) {

                            $archivo = $files[$i]['archivo'];

                            // 📁 Carpeta destino
                            $carpeta = 'C:/xampp/htdocs/proyectos_multimedia';

                            if (!file_exists($carpeta)) {
                                mkdir($carpeta, 0777, true);
                            }

                            // 🧾 Nombre único
                            $nombreArchivo = 'media_' . Str::random(12) . '.' . $archivo->getClientOriginalExtension();

                            // ⬆️ Subir archivo
                            $archivo->move($carpeta, $nombreArchivo);

                            // 🧠 Ruta que se guarda en BD
                            $rutaFinal = "proyectos_multimedia/$nombreArchivo";
                        }
                        // Si NO hay archivo nuevo → se mantiene la ruta enviada
                    }

                    /** 🔸 2. VIDEO (solo URL) */
                    if ($tipo === 'video') {
                        // siempre string (YouTube, Drive, etc.)
                        $rutaFinal = $item['archivo'] ?? null;
                    }

                    // 🚨 Seguridad: evitar NULL accidentales
                    if (!$rutaFinal) {
                        continue; // o lanzar excepción si prefieres
                    }

                    // Guardar valor final para el modelo
                    $item['archivo'] = $rutaFinal;
                }

                // 📦 Enviar al modelo
                AnunciosModel::guardarMultimediaProyecto($id, $multimedia);
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



    //CRUD PROPIEDAD ALDASA CLUB

    public function getestadomembresia($id)
    {
        // 1️⃣ Marcar como vencidos los planes expirados
        DB::table('usuarios_planesclub')
            ->where('user_id', $id)
            ->where('estado', 'activo')
            ->whereNotNull('fecha_fin')
            ->where('fecha_fin', '<', now())
            ->update([
                'estado' => 'vencido',
                'is_active' => 0
            ]);

        // 2️⃣ Buscar plan vigente
        $plan = DB::table('usuarios_planesclub')
            ->where('user_id', $id)
            ->where('estado', 'activo')
            ->where('is_active', 1)
            ->whereNotNull('fecha_fin')
            ->where('fecha_fin', '>=', now())
            ->orderByDesc('fecha_fin')
            ->first();

        // 3️⃣ Respuesta
        if ($plan) {
            return response()->json([
                'activo'    => true,
                'plan_id'   => $plan->plan_id,
                'fecha_fin' => $plan->fecha_fin,
                'anuncios_disponibles' => $plan->anuncios_disponibles
            ]);
        }

        return response()->json([
            'activo' => false
        ]);
    }



    public function listardetalleprincipalclub($idpublish)
    {
        $resultado = AnunciosModel::listardetalleprincipalclub($idpublish);
        return response()->json($resultado);
    }

     public function listaranuncioaldasaclub($idpublish, $id)
    {
        $resultado = AnunciosModel::listaranuncioaldasaclub($idpublish, $id);
        return response()->json($resultado);
    }


    public function actualizarvendidoclub(Request $request, $id)
    {
        try {
            // Validar
            $request->validate([
                'vendido' => 'required|boolean'
            ]);

            // Convertir estado: true = 2 (vendido), false = 1 (activo)
            $nuevoEstado = $request->vendido ? 2 : 1;

            // Llamada al modelo encargado
            $resultado = AnunciosModel::actualizarEstadoVendidoclub($id, $nuevoEstado);

            if (!$resultado) {
                return response()->json(['message' => 'No encontrado'], 404);
            }

            return response()->json(['message' => 'Estado actualizado correctamente']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function regsuscripciones(Request $request)
    {
        // 1️⃣ Validar email
        
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:150|unique:suscripciones,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Correo inválido o ya registrado',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            // 2️⃣ Insertar suscripción
            DB::table('suscripciones')->insert([
                'email'      => $request->email,
                'is_active'     => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 3️⃣ Respuesta OK
            return response()->json([
                'success' => true,
                'message' => 'Suscripción registrada correctamente'
            ], 201);

        } catch (\Exception $e) {
            // 4️⃣ Error inesperado
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar la suscripción'
            ], 500);
        }
    }


    public function registrarLibroReclamaciones(Request $request)
    {
        // 1️⃣ Validación
        $validator = Validator::make($request->all(), [
            'nombre'     => 'required|string|max:150',
            'documento'  => 'required|string|max:20',
            'correo'     => 'required|email|max:150',
            'telefono'   => 'nullable|string|max:20',
            'tipo'       => 'required|in:reclamo,queja',
            'detalle'    => 'required|string|min:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'mensaje' => 'Datos inválidos',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            // 2️⃣ Insertar reclamo
            DB::table('libro_reclamaciones')->insert([
                'nombre'     => $request->nombre,
                'documento'  => $request->documento,
                'correo'     => $request->correo,
                'telefono'   => $request->telefono,
                'tipo'       => $request->tipo,
                'detalle'    => $request->detalle,
                'estado'     => 'pendiente',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 3️⃣ Respuesta OK
            return response()->json([
                'success' => true,
                'mensaje' => 'Reclamo registrado correctamente'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'mensaje' => 'Error al registrar el reclamo',
                'error'   => $e->getMessage()
            ], 500);
        }
    }



    public function listarplanosclub($id)
    {
        $resultado = AnunciosModel::listarplanosclub($id);
        return response()->json($resultado);
    }

    public function categoriasCatalogoidclub($id)
    {
        $resultado = AnunciosModel::categoriasCatalogoidclub($id);
        return response()->json($resultado);
    }

    public function amenitiesidclub($id)
    {
        $resultado = AnunciosModel::amenitiesidclub($id);
        return response()->json($resultado);
    }

    public function listaimgsecundariasclub($id)
    {
        $resultado = AnunciosModel::listaimgsecundariasclub($id);
        return response()->json($resultado);
    }

    public function categoriasCatalogoclub($tpropiedad)
    {
        $resultado = AnunciosModel::categoriasCatalogoclub($tpropiedad);
        return response()->json($resultado);
    }

    public function amenitiesclub($tpropiedad)
    {
        $resultado = AnunciosModel::amenitiesclub($tpropiedad);
        return response()->json($resultado);
    }


    public function actualizaranuncioclub(Request $request, $id)
    {
        try {
            // 1️⃣ Validar los campos
            $validated = $request->validate([
                'tipo_id' => 'required|integer',
                'operacion_id' => 'required|integer',
                'ubicacion_id' => 'required|integer',
                'moneda_id' => 'required|integer|exists:monedas,id',
                'titulo' => 'required|string|max:255',
                'descripcion' => 'required|string',
                'precio' => 'required|numeric|min:0',
                'direccion' => 'required|string',
            ]);

            // 2️⃣ Buscar anuncio existente
            $anuncio = DB::table('propiedadesclub')->where('id', $id)->first();

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

                $directorioEscritorio = 'C:/xampp/htdocs/propiedadesclub';
                if (!file_exists($directorioEscritorio)) {
                    mkdir($directorioEscritorio, 0777, true);
                }

                $archivo->move($directorioEscritorio, $nombre);
                $rutaImagen = 'propiedadesclub/' . $nombre;
            }

            // 4️⃣ Actualizar el anuncio
            AnunciosModel::actualizarAnuncioclub($id, $validated, $rutaImagen);

            // 5️⃣ Actualizar características
            if ($request->has('caracteristicas')) {
                $caracteristicas = json_decode($request->caracteristicas, true);

                if (is_array($caracteristicas)) {
                    // eliminar las antiguas
                    
                    // guardar las nuevas
                    AnunciosModel::guardarCaracteristicasclub($id, $caracteristicas);
                }
            }

            if ($request->has('caracteristicas_secundarias')) {
                $caracteristicas_secundarias = json_decode($request->caracteristicas_secundarias, true);

                if (is_array($caracteristicas_secundarias)) {
                    // eliminar las antiguas
                    
                    // guardar las nuevas
                    AnunciosModel::guardarCaracteristicassecundariasclub($id, $caracteristicas_secundarias);
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

                        $directorioPlanos = 'C:/xampp/htdocs/planosclub';
                        if (!file_exists($directorioPlanos)) mkdir($directorioPlanos, 0777, true);

                        $nombrePlano = 'planoclub_' . Str::random(10) . '.' . $archivo->getClientOriginalExtension();
                        $archivo->move($directorioPlanos, $nombrePlano);

                        AnunciosModel::guardarPlanosclub($id, $titulo, $nombrePlano);
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

            // 9️⃣ Subir y guardar imágenes secundarias
            if ($request->hasFile('imagenes_secundarias')) {
                foreach ($request->file('imagenes_secundarias') as $imagenSecundaria) {
                    if ($imagenSecundaria->isValid()) {

                        $directorioImagenes = 'C:/xampp/htdocs/propiedades_imagenesclub';
                        if (!file_exists($directorioImagenes)) {
                            mkdir($directorioImagenes, 0777, true);
                        }

                        $nombreArchivo = 'imgclub_' . Str::random(10) . '.' . $imagenSecundaria->getClientOriginalExtension();
                        $imagenSecundaria->move($directorioImagenes, $nombreArchivo);

                        // ✅ Llamamos al modelo
                        AnunciosModel::guardarImagenesclub($id, null, $nombreArchivo);
                    }
                }
            }

            if ($request->filled('video_url')) {
                $video_url = trim($request->input('video_url'));

                if (!empty($video_url)) {
                    // Elimina los videos antiguos si lo deseas
                    DB::table('propiedad_videos')->where('propiedad_id', $id)->delete();

                    // Guarda el nuevo video
                    AnunciosModel::guardarvideourlclub($id, $video_url);
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


    public function registraranuncioclub(Request $request)
    {
        try {

            
            // 1️⃣ Validar campos obligatorios
            $validated = $request->validate([
                'tipo_id' => 'required|integer',
                'operacion_id' => 'required|integer',
                'ubicacion_id' => 'required|integer',
                'moneda_id' => 'required|integer|exists:monedas,id',
                'titulo' => 'required|string|max:255',
                'descripcion' => 'required|string',
                'precio' => 'required|numeric|min:0',
                'imagen_principal' => 'nullable|image',
                'user_id' => 'required|integer',
                'direccion' => 'required|string',
            ]);

            $userId = $request->user_id;

            // 2️⃣ Verificar plan activo
            $plan = DB::table('usuarios_planesclub')
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
                DB::table('usuarios_planesclub')
                    ->where('id', $plan->id)
                    ->update(['estado' => 'vencido', 'is_active' => 0]);

                return response()->json([
                    'estado' => 0,
                    'mensaje' => 'Tu plan ha vencido. Renueva tu suscripción para continuar publicando.',
                ], 403);
            }

            // 4️⃣ Contar anuncios existentes
            $totalAnuncios = DB::table('propiedadesclub')
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
                $nombre = 'propiedadclub_' . Str::random(10) . '.' . $archivo->getClientOriginalExtension();

                $directorioPropiedades = 'C:/xampp/htdocs/propiedadesclub';
                if (!file_exists($directorioPropiedades)) {
                    mkdir($directorioPropiedades, 0777, true);
                }

                $archivo->move($directorioPropiedades, $nombre);
                $rutaImagen = 'propiedadesclub/' . $nombre;
            }

            // 6️⃣ Crear anuncio principal
            $idPropiedad = AnunciosModel::crearAnuncioclub($validated, $rutaImagen);

            // 7️⃣ Guardar características
            if ($request->has('caracteristicas')) {
                $caracteristicas = json_decode($request->caracteristicas, true);
                if (is_array($caracteristicas) && count($caracteristicas) > 0) {
                    AnunciosModel::guardarCaracteristicasclub($idPropiedad, $caracteristicas);
                }
            }

            if ($request->has('caracteristicas_secundarias')) {
                $caracteristicas_secundarias = json_decode($request->caracteristicas_secundarias, true);
                if (is_array($caracteristicas_secundarias) && count($caracteristicas_secundarias) > 0) {
                    AnunciosModel::guardarCaracteristicassecundariasclub($idPropiedad, $caracteristicas_secundarias);
                }
            }

            // 8️⃣ Subir y guardar planos
            if ($request->has('planos')) {
                $planosData = $request->planos; // array con ['archivo', 'titulo']

                foreach ($planosData as $plano) {
                    if (isset($plano['archivo'])) {
                        $archivo = $plano['archivo'];
                        $titulo = $plano['titulo'] ?? '';

                        $directorioPlanos = 'C:/xampp/htdocs/planosclub';
                        if (!file_exists($directorioPlanos)) mkdir($directorioPlanos, 0777, true);

                        $nombrePlano = 'planoclub_' . Str::random(10) . '.' . $archivo->getClientOriginalExtension();
                        $archivo->move($directorioPlanos, $nombrePlano);

                        AnunciosModel::guardarPlanosclub($idPropiedad, $titulo, $nombrePlano);
                    }
                }
            }

            // 9️⃣ Subir y guardar imágenes secundarias
            if ($request->hasFile('imagenes_secundarias')) {
                foreach ($request->file('imagenes_secundarias') as $imagenSecundaria) {
                    if ($imagenSecundaria->isValid()) {

                        $directorioImagenes = 'C:/xampp/htdocs/propiedades_imagenesclub';
                        if (!file_exists($directorioImagenes)) {
                            mkdir($directorioImagenes, 0777, true);
                        }

                        $nombreArchivo = 'imgclub_' . Str::random(10) . '.' . $imagenSecundaria->getClientOriginalExtension();
                        $imagenSecundaria->move($directorioImagenes, $nombreArchivo);

                        // ✅ Llamamos al modelo
                        AnunciosModel::guardarImagenesclub($idPropiedad, null, $nombreArchivo);
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

    public function eliminarplanosclub($id)
    {
        $resultado = AnunciosModel::eliminarplanosclub($id);
        
        if ($resultado > 0) {
            return response()->json(['success' => true, 'message' => 'Plano eliminado correctamente']);
        } else {
            return response()->json(['success' => false, 'message' => 'No se pudo eliminar el plano']);
        }
    }

    public function eliminarimgsecundariasclub($id)
    {
        $resultado = AnunciosModel::eliminarimgsecundariasclub($id);
        
        if ($resultado > 0) {
            return response()->json(['success' => true, 'message' => 'Plano eliminado correctamente']);
        } else {
            return response()->json(['success' => false, 'message' => 'No se pudo eliminar el plano']);
        }
    }

}
