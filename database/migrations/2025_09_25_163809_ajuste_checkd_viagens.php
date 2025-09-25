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
        Schema::table('viagens', function (Blueprint $table) {
            // Remover a foreign key incorreta
            $table->dropForeign('viagens_checked_by_foreign');

            // Adicionar a foreign key correta
            $table->foreign('checked_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null')
                  ->name('viagens_checked_by_foreign');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('viagens', function (Blueprint $table) {
            //
        });
    }
};
