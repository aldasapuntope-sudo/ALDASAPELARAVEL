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
            SELECT t.*, CONCAT(u.nombre, ' ', u.apellido) AS usuario
            FROM soporte_tickets t
            LEFT JOIN usuario u ON u.id = t.user_id
            ORDER BY t.id DESC
        ");
    }

    /* =========================
       MENSAJES DE TICKET
    ==========================*/
    public static function getMensajesTicket($ticketId)
    {
        return DB::select("
            SELECT spm.id, spm.ticket_id, spm.mensaje, spm.user_id, spm.created_at, CONCAT(usu.nombre, ' ', usu.apellido) as nombrecompleto
            FROM soporte_mensajes spm  inner join usuario usu on spm.user_id = usu.id
            WHERE spm.ticket_id = ?
            ORDER BY spm.id ASC
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