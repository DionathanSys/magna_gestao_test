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
            $table->string('parceiro_origem')
                ->nullable()
                ->after('veiculo_id');
            $table->string('parceiro_destino')
                ->nullable()
                ->after('parceiro_origem');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documentos_frete', function (Blueprint $table) {
            $table->dropColumn('parceiro_origem');
            $table->dropColumn('parceiro_destino');
        });
    }
};

