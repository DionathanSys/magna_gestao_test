<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manutencao_lancamentos', function (Blueprint $table) {
            $table->boolean('dispensado_vinculo')->default(false)->after('vinculado_por');
            $table->timestamp('dispensado_em')->nullable()->after('dispensado_vinculo');
            $table->foreignId('dispensado_por')
                ->nullable()
                ->after('dispensado_em')
                ->constrained('users')
                ->nullOnDelete();

            $table->index('dispensado_vinculo');
        });
    }

    public function down(): void
    {
        Schema::table('manutencao_lancamentos', function (Blueprint $table) {
            $table->dropForeign(['dispensado_por']);
            $table->dropIndex(['dispensado_vinculo']);
            $table->dropColumn(['dispensado_vinculo', 'dispensado_em', 'dispensado_por']);
        });
    }
};
