<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UsuarioController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AnunciosController;
use App\Http\Controllers\Api\PlanesController;
use App\Http\Controllers\Api\AdministracionController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\SoporteController;




Route::prefix('Loginform')->group(function () { 
    Route::post('/auth/google', [AuthController::class, 'loginform']); 
}); 

Route::prefix('Login')->group(function () { 
    Route::post('/auth/google', [AuthController::class, 'googleLogin']); 
});


Route::prefix('usuariosexterno')->group(function () { 
    Route::get('/tipo-usuario', [UsuarioController::class, 'tipoUsuario']); 
    Route::get('/tipo-documento', [UsuarioController::class, 'tipoDocumento']); 
    Route::get('consulta/dni/{numero}', [UsuarioController::class, 'dni']);
    Route::get('consulta/ruc/{numero}', [UsuarioController::class, 'ruc']);
    Route::post('/registrar', [UsuarioController::class, 'registrar']);
});

Route::prefix('miperfil')->group(function () { 
    Route::get('/{codigo}', [UsuarioController::class, 'miperfil']); 
    Route::put('/actualizar/{id}', [UsuarioController::class, 'actualizarperfil']); 
});

//Route::prefix('misanuncios')->middleware('auth:sanctum')->group(function () {
Route::prefix('misanuncios')->group(function () {
    Route::get('/lplanos/{id}', [AnunciosController::class, 'listarplanos']); 
    Route::delete('/eplanos/{id}', [AnunciosController::class, 'eliminarplanos']);
    Route::get('/limagenesecundarias/{id}', [AnunciosController::class, 'listaimgsecundarias']); 
    Route::delete('/eimagenesecundarias/{id}', [AnunciosController::class, 'eliminarimgsecundariasclub']);

    Route::get('/tipos-propiedad', [AnunciosController::class, 'tiposPropiedad']); 
    Route::get('/tipos-operacion', [AnunciosController::class, 'tiposOperacion']); 
    Route::get('/tipos-ubicaciones', [AnunciosController::class, 'tiposUbicaciones']); 
    Route::post('/registrar', [AnunciosController::class, 'registraranuncio']);
    Route::get('/listar/{is_publish}/{id}', [AnunciosController::class, 'listaranuncio']); 
    Route::put('/actualizar/{id}', [AnunciosController::class, 'actualizaranuncio']); 
    Route::get('/caracteristicas-catalogo/{tpropiedad}', [AnunciosController::class, 'categoriasCatalogo']); 
    Route::get('/caracteristicas-catalogoid/{id}', [AnunciosController::class, 'categoriasCatalogoid']); 
    Route::get('/propiedad_amenities/{tpropiedad}', [AnunciosController::class, 'amenities']); 
    Route::get('/propiedad_amenitiesid/{id}', [AnunciosController::class, 'amenitiesid']); 
 
    Route::get('/mensajes-anuncio/{id}', [AnunciosController::class, 'getMensajeanuncio']); 
    Route::get('/mensajes-soporte/{id}', [AnunciosController::class, 'getMensajesoporte']); 
    Route::get('/anuncio-favoritos/{id}', [AnunciosController::class, 'getanunciosFavoritos']); 
    Route::get('/monedas', [AnunciosController::class, 'getmonedas']);

    Route::post('/vendido/{id}', [AnunciosController::class, 'actualizarvendido']); 
    
});

Route::prefix('planes')->group(function () { 
    Route::get('/listar', [PlanesController::class, 'listarPlanes']); 
    Route::get('/usuario/{id}', [PlanesController::class, 'verificarPlanUsuario']); 
    Route::get('/listarclub', [PlanesController::class, 'listarPlanesclub']); 
});


Route::prefix('paginaprincipal')->group(function () { 
    Route::get('/motivosoporteayuda', [AdministracionController::class, 'listarmotivosoporteayuda']); 
    Route::post('/ticketssoporteyayuda/{id}', [AdministracionController::class, 'registrarticketssoprote']);
    Route::post('/actualizar-perfil/{id}', [AdministracionController::class, 'subirperfilusuario']);
    Route::get('/sliders', [AdministracionController::class, 'obtenersliders']);
    Route::get('/obtener-configuraciones', [AdministracionController::class, 'obtenerConfiguraciones']);
    Route::get('/tipos-operacion', [AnunciosController::class, 'tiposOperacion']); 
    Route::get('/tipos-propiedad', [AnunciosController::class, 'tiposPropiedad']);
    Route::get('/propiedades/buscar', [AnunciosController::class, 'buscarpropiedad']);
    Route::post('/propiedades/visita/{id}/{idusuario}', [AnunciosController::class, 'sumarVisita']);
    Route::get('/listaranuncios/{is_publish}', [AnunciosController::class, 'listaranuncioprincipal']); 
    Route::get('/listardetalle/{id}', [AnunciosController::class, 'listardetalleprincipal']); 
    Route::get('/tipo-cambio', function () {
        $data = file_get_contents('https://api.apis.net.pe/v1/tipo-cambio-sunat');
        return response($data)->header('Content-Type', 'application/json');
    });

    Route::post('/rmensajeanunciante', [AnunciosController::class, 'registrarmensajeanunciante']); 

    //FILTROS DE PAGINA PRINCIPAL
    Route::get('/propiedades/relacionadas/{tipo_id}/{id}', [AnunciosController::class, 'getRelacionadas']);
    Route::get('/quienes-somos', [AnunciosController::class, 'getQuienessomos']);
    Route::get('/nosotros', [AnunciosController::class, 'getNosotros']);
    Route::get('/club', [AnunciosController::class, 'getClub']);
    Route::get('/planesanuncio', [AnunciosController::class, 'getPlanes']);
    Route::get('/inversiones', [AnunciosController::class, 'getInversiones']);
    Route::get('/aldasainversiones', [AnunciosController::class, 'getAldasainversiones']);
    
    Route::get('/terminos-condiciones', [AnunciosController::class, 'gettermcondiciones']);
    Route::get('/publica-tu-anuncio', [AnunciosController::class, 'getpublicatuanuncio']);
    Route::get('/politicas-privacidad', [AnunciosController::class, 'getpoliticaprivacidad']);
    Route::get('/lugares-mas-buscados', [AdministracionController::class, 'obtenerLugaresMasBuscados']);
    

    Route::get('/favoritos/existe/{userid}/{anuncioid}', [AnunciosController::class, 'existeFavorito']); 
    Route::post('/favoritos/guardar', [AnunciosController::class, 'registrarfavoritos']); 
    Route::post('/favoritos/eliminar', [AnunciosController::class, 'eliminarfavoritos']); 

    // CRUD MODULO ANUNCIO POPUP
    Route::get('/lpopups', [AdministracionController::class, 'listarPopups']);
    Route::get('/popup-config', [AdministracionController::class, 'getPopupConfig']);
    Route::get('/color', [AdministracionController::class, 'listarcolor']);
    Route::post('/suscripciones', [AnunciosController::class, 'regsuscripciones']); 
    Route::post('/registrarlibroreclamaciones', [AnunciosController::class, 'registrarlibroreclamaciones']); 
});


Route::prefix('administracion')->middleware('auth:sanctum')->group(function () {
//Route::prefix('administracion')->group(function () { 

    Route::get('/lusuarioscombobx', [AdministracionController::class, 'listarusuarioscombox']); 
    Route::get('/lplanescombox', [AdministracionController::class, 'listarplanescombox']); 
     Route::get('/lplanescomboxclub', [AdministracionController::class, 'listarplanescomboxclub']); 
    Route::get('/lperfilescombox', [AdministracionController::class, 'listarperfilescombox']); 
    Route::get('/ltipodocumentoscombox', [AdministracionController::class, 'listardocumentoscombox']); 
    
    //RUTA DE PLANES
    Route::get('/lplanes', [AdministracionController::class, 'listarPlanes']); 
    Route::put('/aplanes/{id}', [AdministracionController::class, 'actualizarPlanes']); 
    Route::post('/rplanes', [AdministracionController::class, 'registrarPlanes']); 
    Route::put('/eplanes/{id}/estado', [AdministracionController::class, 'cambiarEstadoPlan']); 
    //RUTA DE PLANES CLUB
    Route::get('/lplanesclub', [AdministracionController::class, 'listarPlanesclub']); 
    Route::put('/aplanesclub/{id}', [AdministracionController::class, 'actualizarPlanesclub']); 
    Route::post('/rplanesclub', [AdministracionController::class, 'registrarPlanesclub']); 
    Route::put('/eplanesclub/{id}/estado', [AdministracionController::class, 'cambiarEstadoPlanclub']); 

    // RUTA DE PLANES DE USUARIOS
    Route::get('/lplanes_usuario', [AdministracionController::class, 'listarPlanesUsuario']);
    Route::put('/aplanes_usuario/{id}', [AdministracionController::class, 'actualizarPlanesUsuario']);
    Route::post('/rplanes_usuario', [AdministracionController::class, 'registrarPlanesUsuario']);
    Route::put('/eplanes_usuario/{id}/estado', [AdministracionController::class, 'cambiarEstadoPlanesUsuario']);
    // RUTA DE PLANES DE USUARIOS CLUB
    Route::get('/lplanes_usuarioclub', [AdministracionController::class, 'listarPlanesUsuarioclub']);
    Route::put('/aplanes_usuarioclub/{id}', [AdministracionController::class, 'actualizarPlanesUsuarioclub']);
    Route::post('/rplanes_usuarioclub', [AdministracionController::class, 'registrarPlanesUsuarioclub']);
    Route::put('/eplanes_usuarioclub/{id}/estado', [AdministracionController::class, 'cambiarEstadoPlanesUsuarioclub']);


    //RUTA CURD DE TIPO DE DOCUMENTO
    Route::get('/ltipodocumento', [AdministracionController::class, 'ltipoDocumento']); 
    Route::put('/atipodocumento/{id}', [AdministracionController::class, 'actualizarTipoDocumento']); 
    Route::post('/rtipodocumento', [AdministracionController::class, 'registrarTipoDocumento']); 
    Route::put('/etipodocumento/{id}/estado', [AdministracionController::class, 'cambiarEstadoTipoDocumento']); 

    // Amenidades
    Route::get('/lamenities', [AdministracionController::class, 'listarAmenities']);
    Route::post('/ramenities', [AdministracionController::class, 'registrarAmenity']);
    Route::put('/aamenities/{id}', [AdministracionController::class, 'actualizarAmenity']);
    Route::put('/eamenities/{id}/estado', [AdministracionController::class, 'cambiarEstadoAmenity']);
 
    // Amenidades CLUB
    Route::get('/lamenitiesclub', [AdministracionController::class, 'listarAmenitiesclub']);
    Route::post('/ramenitiesclub', [AdministracionController::class, 'registrarAmenityclub']);
    Route::put('/aamenitiesclub/{id}', [AdministracionController::class, 'actualizarAmenityclub']);
    Route::put('/eamenitiesclub/{id}/estado', [AdministracionController::class, 'cambiarEstadoAmenityclub']);

    // Características catálogo
    Route::get('/lcaracteristicas', [AdministracionController::class, 'listarCaracteristicasCatalogo']);
    Route::post('/rcaracteristica', [AdministracionController::class, 'registrarCaracteristicaCatalogo']);
    Route::put('/acaracteristica/{id}', [AdministracionController::class, 'actualizarCaracteristicaCatalogo']);
    Route::put('/ecaracteristica/{id}/estado', [AdministracionController::class, 'cambiarEstadoCaracteristicaCatalogo']);
    Route::get('/tipos-propiedad', [AdministracionController::class, 'tiposPropiedad']);
     // Características catálogo CLUB
    Route::get('/lcaracteristicasclub', [AdministracionController::class, 'listarCaracteristicasCatalogoclub']);
    Route::post('/rcaracteristicaclub', [AdministracionController::class, 'registrarCaracteristicaCatalogoclub']);
    Route::put('/acaracteristicaclub/{id}', [AdministracionController::class, 'actualizarCaracteristicaCatalogoclub']);
    Route::put('/ecaracteristicaclub/{id}/estado', [AdministracionController::class, 'cambiarEstadoCaracteristicaCatalogoclub']);
   
    
    //RUTAS CRUD OPERACIONES
    Route::get('/loperaciones', [AdministracionController::class, 'listarOperaciones']); // Listar todas las operaciones
    Route::post('/roperaciones', [AdministracionController::class, 'registrarOperacion']); // Crear nueva operación
    Route::put('/aoperaciones/{id}', [AdministracionController::class, 'actualizarOperacion']); // Actualizar operación existente
    Route::put('/eoperaciones/{id}/estado', [AdministracionController::class, 'cambiarEstadoOperacion']); // Activar/Inactivar operación

    //RUTAS CRUD TIPO PROPIEDAD
    Route::get('/ltipospropiedad', [AdministracionController::class, 'listarTiposPropiedad']); // Listar todos los tipos de propiedad
    Route::post('/rtipospropiedad', [AdministracionController::class, 'registrarTipoPropiedad']); // Crear tipo de propiedad
    Route::put('/atipospropiedad/{id}', [AdministracionController::class, 'actualizarTipoPropiedad']); // Actualizar tipo de propiedad
    Route::put('/etipospropiedad/{id}/estado', [AdministracionController::class, 'cambiarEstadoTipoPropiedad']); // Activar/Inactivar tipo de propiedad

    //RUTAS CRUD PAGINAS
    Route::get('/lpaginas', [AdministracionController::class, 'listarpaginas']);
    Route::post('/rpaginas', [AdministracionController::class, 'registrarpaginas']);
    Route::put('/apaginas/{id}', [AdministracionController::class, 'actualizarpaginas']);
    Route::put('/epaginas/{id}/estado', [AdministracionController::class, 'cambiarEstadopaginas']);

    //RUTAS CRUD CONFIGURACION
    Route::get('/lconfiguraciones', [AdministracionController::class, 'listarconfiguracion']);
    Route::post('/rconfiguraciones', [AdministracionController::class, 'registrarconfiguracion']);
    Route::put('/aconfiguraciones/{id}', [AdministracionController::class, 'actualizarconfiguracion']);
    Route::put('/econfiguraciones/{id}/estado', [AdministracionController::class, 'cambiarConfiguracion']);


    // ✅ CRUD MODULO UBICACIONES
    Route::get('/lubicaciones', [AdministracionController::class, 'listarUbicaciones']);
    Route::post('/rubicaciones', [AdministracionController::class, 'registrarUbicacion']);
    Route::put('/aubicaciones/{id}', [AdministracionController::class, 'actualizarUbicacion']);
    Route::put('/eubicaciones/{id}/estado', [AdministracionController::class, 'cambiarUbicacion']);



    // CRUD MODULO SLIDER
    Route::get('/sliders', [AdministracionController::class, 'listarSliders']);
    Route::post('/rslider', [AdministracionController::class, 'registrarSlider']);
    Route::put('/aslider/{id}', [AdministracionController::class, 'actualizarSlider']);
    Route::put('/eslider/{id}/estado', [AdministracionController::class, 'cambiarEstadoSlider']);


    Route::get('/lpopups2', [AdministracionController::class, 'listarPopups2']);
    Route::post('/rpopups', [AdministracionController::class, 'registrarPopups']);
    Route::put('/apopups/{id}', [AdministracionController::class, 'actualizarPopups']);
    Route::put('/epopups/{id}/estado', [AdministracionController::class, 'cambiarEstadoPopups']);

    //CRUD MODULO USUARIO
    Route::get('/lusuarios', [AdministracionController::class, 'listarUsuarios']);
    Route::post('/rusuarios', [AdministracionController::class, 'registrarUsuarios']);
    Route::put('/ausuarios/{id}', [AdministracionController::class, 'actualizarUsuarios']);
    Route::put('/eusuarios/{id}/estado', [AdministracionController::class, 'cambiarEstadoUsuarios']);

    //CRUD MODULO CONFIGURACIONES SCRIPTS
    Route::get('/lscripts', [AdministracionController::class, 'listarScripts']);
    Route::post('/rscripts', [AdministracionController::class, 'registrarScripts']);
    Route::put('/ascripts/{id}', [AdministracionController::class, 'actualizarScripts']);
    Route::put('/escripts/{id}/estado', [AdministracionController::class, 'cambiarEstadoScripts']);

    // CRUD MOTIVO SOPORTE
    Route::get('/lsoporte-motivos', [AdministracionController::class, 'listarSoportemotivos']);
    Route::post('/rsoporte-motivo', [AdministracionController::class, 'registrarSoportemotivos']);
    Route::put('/asoporte-motivo/{id}', [AdministracionController::class, 'actualizarSoportemotivos']);
    Route::put('/esoporte-motivo/{id}/estado', [AdministracionController::class, 'cambiarEstadoSoportemotivos']);


    //RUTA BITACORA
    Route::get('/lbitacora', [AdministracionController::class, 'listarbitacora']);

    //CRUD MODULO POPUPS CONFIGURACION
    Route::get('/lpopupconfig', [AdministracionController::class, 'listarPopupConfig']);
    Route::put('/apopupconfig/{id}', [AdministracionController::class, 'actualizarPopupConfig']);


    //CONSULTAS EXTRAS 
    Route::get('/lmensajecontactos/{id}', [AdministracionController::class, 'listarmensajecontactos']);
    Route::get('/lhistorialvisitapropiedad/{id}', [AdministracionController::class, 'listarvisitaspropiedad']);
});
 

Route::prefix('inversiones')->group(function () {
//Route::prefix('inversiones')->middleware('auth:sanctum')->group(function () {
//Route::prefix('misanuncios')->group(function () {
     Route::get('/usuarios', [AdministracionController::class, 'listarUsuarios']);
    Route::get('/mis-proyectos/{id}', [AnunciosController::class, 'getmisproyectosid']); 
    Route::get('/mis-proyectos', [AnunciosController::class, 'getmisproyectos']); 

    Route::get('/proyectos/detalle/{id}', [AnunciosController::class, 'getProyectoDetalle']); 

    // REGISTRAR NUEVO PROYECTO
    Route::post('/registrar', [AnunciosController::class, 'registrarProyecto']);

    // ACTUALIZAR PROYECTO
    Route::post('/actualizar/{id}', [AnunciosController::class, 'actualizarProyecto']);

    
    //ELIMINAR
    Route::delete('/emultimedia/{id}', [AnunciosController::class, 'eliminarmultimedia']);
    Route::delete('/eetapas/{id}', [AnunciosController::class, 'eliminaretapas']);
    Route::delete('/ecaracteristicas/{id}', [AnunciosController::class, 'eliminarcaracteristicas']);
    Route::delete('/einversionistas/{id}', [AnunciosController::class, 'eliminarinversionista']);
    
});


Route::prefix('aldasaclub')->middleware('auth:sanctum')->group(function () {
//Route::prefix('aldasaclub')->group(function () {
    Route::get('/estado-membresia/{id}', [AnunciosController::class, 'getestadomembresia']); 
    Route::get('/listardetalle/{id}', [AnunciosController::class, 'listardetalleprincipalclub']);

    Route::get('/lplanos/{id}', [AnunciosController::class, 'listarplanosclub']); 
    Route::delete('/eplanos/{id}', [AnunciosController::class, 'eliminarplanosclub']);
    Route::get('/limagenesecundarias/{id}', [AnunciosController::class, 'listaimgsecundariasclub']); 
    Route::delete('/eimagenesecundarias/{id}', [AnunciosController::class, 'eliminarimgsecundarias']);

    Route::get('/tipos-propiedad', [AnunciosController::class, 'tiposPropiedad']); 
    Route::get('/tipos-operacion', [AnunciosController::class, 'tiposOperacion']); 
    Route::get('/tipos-ubicaciones', [AnunciosController::class, 'tiposUbicaciones']); 
    Route::post('/registrar', [AnunciosController::class, 'registraranuncioclub']);
    Route::get('/listar/{is_publish}/{id}', [AnunciosController::class, 'listaranuncioaldasaclub']); 
    Route::put('/actualizar/{id}', [AnunciosController::class, 'actualizaranuncioclub']); 
    Route::get('/caracteristicas-catalogo/{tpropiedad}', [AnunciosController::class, 'categoriasCatalogoclub']); 
    Route::get('/caracteristicas-catalogoid/{id}', [AnunciosController::class, 'categoriasCatalogoidclub']); 
    Route::get('/propiedad_amenities/{tpropiedad}', [AnunciosController::class, 'amenitiesclub']); 
    Route::get('/propiedad_amenitiesid/{id}', [AnunciosController::class, 'amenitiesidclub']); 
  
    Route::get('/mensajes-anuncio/{id}', [AnunciosController::class, 'getMensajeanuncio']); 
    Route::get('/anuncio-favoritos/{id}', [AnunciosController::class, 'getanunciosFavoritos']); 
    Route::get('/monedas', [AnunciosController::class, 'getmonedas']);

    Route::post('/vendido/{id}', [AnunciosController::class, 'actualizarvendidoclub']); 
});

Route::prefix('chat')->group(function () { 
    Route::post('/enviar', [ChatController::class, 'enviar']);
    Route::get('/listar/{session}', [ChatController::class, 'listar']);
});

Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink']);
Route::get('/reset-password/{token}', [PasswordResetController::class, 'validateToken']);
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);
Route::post('/verify-reset-token', [PasswordResetController::class, 'verifyToken']);

Route::get('/menus', [MenuController::class, 'index']);
Route::get('/scripts', [AdministracionController::class, 'obtenerscripts']);
Route::get('/soporte/tickets/{userId}', [SoporteController::class, 'misTickets']);
Route::get('/soporte/tickets/{ticketId}/mensajes', [SoporteController::class, 'mensajesTicket']);
Route::post('/soporte/tickets/{ticketId}/mensajes', [SoporteController::class, 'enviarMensaje']);

// Admin
Route::get('/admin/soporte/tickets', [SoporteController::class, 'todosTickets']);