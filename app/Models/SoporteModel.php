<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SoporteModel
{
    /* =========================
       TICKETS USUARIO
    ==========================*/
    public static function getTicketsUsuario($userId)
    {
        return DB::select("
            SELECT *
            FROM soporte_tickets
            WHERE user_id = ?
            ORDER BY id DESC
        ", [$userId]);
    }

    /* =========================
       TICKETS ADMIN
    ==========================*/
    public static function getTodosTickets()
    {
        return DB::select("
            SELECT t.*, u.name AS usuario
            FROM soporte_tickets t
            LEFT JOIN users u ON u.id = t.user_id
            ORDER BY t.id DESC
        ");
    }

    /* =========================
       MENSAJES DE TICKET
    ==========================*/
    public static function getMensajesTicket($ticketId)
    {
        return DB::select("
            SELECT *
            FROM soporte_mensajes
            WHERE ticket_id = ?
            ORDER BY id ASC
        ", [$ticketId]);
    }

    /* =========================
       INSERTAR MENSAJE
    ==========================*/
    public static function insertarMensaje($ticketId, $mensaje, $userId, $enviadoPor)
    {
        DB::insert("
            INSERT INTO soporte_mensajes
            (ticket_id, user_id, mensaje, enviado_por)
            VALUES (?, ?, ?, ?)
        ", [$ticketId, $userId, $mensaje, $enviadoPor]);
    }
}