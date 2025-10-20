<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AdministracionModel extends Model
{
   public static function ltipoDocumento()
    {
        return DB::select('SELECT * FROM tipos_documento WHERE is_active = 1');
    }
}
