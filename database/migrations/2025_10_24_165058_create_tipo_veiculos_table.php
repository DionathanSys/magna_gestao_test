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
        Schema::create('tipo_veiculos', function (Blueprint $table) {
            $table->id();
            $table->string('descricao', 100);
            $table->decimal('meta_media', 10, 2)
                ->comment('Meta de média de consumo em km/l');
            $table->boolean('is_active')
                ->default(true);
            $table->timestamps();
        });

        Schema::table('veiculos', function (Blueprint $table) {
            $table->foreignId('tipo_veiculo_id')
                ->nullable()
                ->constrained('tipo_veiculos')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('veiculos', function (Blueprint $table) {
            $table->dropForeign(['tipo_veiculo_id']);
            $table->dropColumn('tipo_veiculo_id');
        });
        Schema::dropIfExists('tipo_veiculos');
    }
};
