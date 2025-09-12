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
        Schema::create('inspecoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('veiculo_id')
                ->constrained('veiculos');
            $table->date('data_inspecao');
            $table->integer('quilometragem');
            $table->text('observacoes')
                ->nullable();
            $table->string('status');
            $table->foreignId('created_by')
                ->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inspecoes');
    }
};
