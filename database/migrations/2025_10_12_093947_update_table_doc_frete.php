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
        Schema::table('documentos_frete', function (Blueprint $table) {
            $table->foreignId('viagem_id')->nullable()->constrained('viagens')->nullOnDelete();
            $table->dropConstrainedForeignId('integrado_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documentos_frete', function (Blueprint $table) {
            // Remove a foreign key constraint e a coluna
            $table->dropConstrainedForeignId('viagem_id');

            // Recriar a coluna que foi removida no up()
            $table->foreignId('integrado_id')->nullable()->constrained('integrados');

        });
    }
};
