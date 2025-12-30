<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ChatModel extends Model
{
    public static function listarMensajes($session)
    {
        return DB::select(
            "SELECT mensaje, emisor 
             FROM chat_conversaciones 
             WHERE session_id = ?
             ORDER BY id ASC",
            [$session]
        );
    }

    public static function enviarMensaje($session, $mensaje)
    {
        // Guardar mensaje del usuario
        DB::insert(
            "INSERT INTO chat_conversaciones (session_id, mensaje, emisor, created_at)
             VALUES (?, ?, 'user', NOW())",
            [$session, $mensaje]
        );

        // 🤖 BOT POR REGLAS
        $respuesta = self::respuestaBot($mensaje);

        // Guardar respuesta del bot
        DB::insert(
            "INSERT INTO chat_conversaciones (session_id, mensaje, emisor, created_at)
             VALUES (?, ?, 'bot', NOW())",
            [$session, $respuesta]
        );

        return $respuesta;
    }

    private static function respuestaBot($mensaje)
    {
        $mensaje = strtolower($mensaje);

        $respuestas = DB::select(
            "SELECT palabras_clave, respuesta
            FROM chat_respuestas
            WHERE is_active = 1
            ORDER BY prioridad ASC"
        );

        foreach ($respuestas as $r) {
            $palabras = explode(',', strtolower($r->palabras_clave));

            foreach ($palabras as $palabra) {
                if (str_contains($mensaje, trim($palabra))) {
                    return $r->respuesta;
                }
            }
        }

        return "Gracias por escribirnos 😊 Realiza tu consulta o contáctanos directamente por WhatsApp 👉 https://wa.link/5dlmgc";

    }

}
 