<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ChatModel;

class ChatController extends Controller
{
    public function enviar(Request $request) 
    {
        $session = $request->session_id;
        $mensaje = $request->mensaje;

        $respuesta = ChatModel::enviarMensaje($session, $mensaje);

        return response()->json([
            'respuesta' => $respuesta
        ]);
    }

    public function listar($session)
    {
        $mensajes = ChatModel::listarMensajes($session);
        return response()->json($mensajes);
    }
}
