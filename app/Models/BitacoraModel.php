<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;

class BitacoraModel
{
    protected $table = 'bitacora';

    /**
     * Inserta un nuevo registro en la bitácora.
     *
     * @param array $data
     * @return bool
     */
    public function insertar(array $data)
    {
        try {
            return DB::table($this->table)->insert($data);
        } catch (\Exception $e) {
            // Puedes registrar el error en el log de Laravel para depuración
            \Log::error('Error al insertar en bitácora: ' . $e->getMessage(), [
                'tabla' => $this->table,
                'data' => $data
            ]);
            return false;
        }
    }

    /**
     * Lista los registros más recientes de la bitácora (opcional).
     */
    public static function listar($limit = 50)
    {
        return DB::table('bitacora')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
