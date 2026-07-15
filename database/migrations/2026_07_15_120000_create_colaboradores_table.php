<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('colaboradores', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('nome');
            $table->string('tipo')->default('MECANICO')->index();
            $table->boolean('ativo')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('colaboradores');
    }
};
