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
        Schema::create('propiedades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('tipo_id')->constrained('tipos_propiedad')->onDelete('cascade');
            $table->foreignId('operacion_id')->constrained('operaciones')->onDelete('cascade');
            $table->foreignId('ubicacion_id')->constrained('ubicaciones')->onDelete('cascade');
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->decimal('precio', 12, 2);
            $table->integer('dormitorios')->nullable();
            $table->integer('banos')->nullable();
            $table->string('area')->nullable();
            $table->string('imagen_principal')->nullable();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('propiedades');
    }
};
