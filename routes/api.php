<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UsuarioController;
use App\Http\Controllers\Api\AuthController;

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