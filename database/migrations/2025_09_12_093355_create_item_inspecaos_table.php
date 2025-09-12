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
        Schema::create('item_inspecao', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspecao_id')
                ->constrained('inspecoes')
                ->cascadeOnDelete();
            $table->morphs('inspecionavel');
            $table->string('observacao')
                ->nullable();
            $table->string('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_inspecao');
    }
};
