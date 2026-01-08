<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SoporteModel;

class SoporteController extends Controller
{
    /* =========================
       TICKETS DEL USUARIO
    ==========================*/
    public function misTickets($userId)
    {
        return response()->json(
            SoporteModel::getTicketsUsuario($userId)
        );
    }

    /* =========================
       TICKETS ADMIN
    ==========================*/
    public function todosTickets()
    {
        return response()->json(
            SoporteModel::getTodosTickets()
        );
    }

    /* =========================
       MENSAJES DE UN TICKET
    ==========================*/
    public function mensajesTicket($ticketId)
    {
        return response()->json(
            SoporteModel::getMensajesTicket($ticketId)
        );
    }

    /* =========================
       ENVIAR MENSAJE
    ==========================*/
    public function enviarMensaje(Request $request, $ticketId)
    {
        if (!$request->mensaje || !$request->user_id) {
            return response()->json([
                'error' => 'Datos incompletos'
            ], 400);
        }

        // Determinar quién envía
        $enviadoPor = in_array($request->perfil_id, [1, 2])
            ? 'admin'
            : 'usuario';

        SoporteModel::insertarMensaje(
            $ticketId,
            $request->mensaje,
            $request->user_id,
            $enviadoPor
        );

        return response()->json([
            'success' => true
        ]);
    }
}
