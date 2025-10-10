<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $table = 'usuario'; // <-- tu tabla
    protected $fillable = [
        'perfil_id',
        'nombre',
        'apellido',
        'razon_social',
        'email',
        'password',
        'tipo_documento_id',
        'numero_documento',
        'telefono',
        'telefono_movil',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Relación con perfil
    public function perfil() {
        return $this->belongsTo(Perfil::class, 'perfil_id');
    }

    // Relación con tipo de documento
    public function tipoDocumento() {
        return $this->belongsTo(TipoDocumento::class, 'tipo_documento_id');
    }
}
