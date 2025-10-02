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
        Schema::table('checklists', function (Blueprint $table) {
            $table->date('periodo')->nullable()->after('data_referencia');
            $table->decimal('quilometragem', 10, 0)->default(0)->after('periodo');
            $table->json('itens_corrigidos')->nullable()->after('itens');
            $table->json('anexos')->nullable();
            $table->renameColumn('itens', 'itens_verificados');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('checklists', function (Blueprint $table) {
            $table->dropColumn(['periodo', 'quilometragem', 'itens_corrigidos', 'anexos', 'created_by', 'parceiro_id']);
            $table->renameColumn('itens_verificados', 'itens');
        });
    }
};
