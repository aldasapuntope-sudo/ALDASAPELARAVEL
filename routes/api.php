<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UsuarioController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AnunciosController;
use App\Http\Controllers\Api\PlanesController;
use App\Http\Controllers\Api\AdministracionController;

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

Route::prefix('misanuncios')->group(function () {
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
});

Route::prefix('planes')->group(function () { 
    Route::get('/listar', [PlanesController::class, 'listarPlanes']); 
    Route::get('/usuario/{id}', [PlanesController::class, 'verificarPlanUsuario']); 
});


Route::prefix('paginaprincipal')->group(function () { 
    Route::get('/listaranuncios/{is_publish}', [AnunciosController::class, 'listaranuncioprincipal']); 
    Route::get('/listardetalle/{id}', [AnunciosController::class, 'listardetalleprincipal']); 
    Route::get('/tipo-cambio', function () {
        $data = file_get_contents('https://api.apis.net.pe/v1/tipo-cambio-sunat');
        return response($data)->header('Content-Type', 'application/json');
    });
});


Route::prefix('administracion')->group(function () { 

    Route::get('/tipo-documento', [AdministracionController::class, 'ltipoDocumento']); 
});