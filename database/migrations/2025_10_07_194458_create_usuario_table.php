<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('usuario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('perfil_id')->constrained('perfiles')->onDelete('cascade');
            $table->string('nombre')->nullable();
            $table->string('apellido')->nullable();
            $table->string('razon_social')->nullable();
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->foreignId('tipo_documento_id')->constrained('tipos_documento')->onDelete('cascade');
            $table->string('numero_documento')->unique(); // aquí va DNI, RUC, Carné de extranjería, etc.
            $table->string('telefono')->nullable();
            $table->string('telefono_movil')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuario');
    }
};
