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
            $table->boolean('considerar_relatorio')
                ->default(true);
        });

        Schema::table('cargas_viagem', function (Blueprint $table) {
            $table->decimal('km_dispersao', 10, 2)
                ->default(0);
            $table->boolean('km_dispersao_rateio')
                ->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('viagens', function (Blueprint $table) {
            $table->dropColumn('considerar_relatorio');
        });

        Schema::table('cargas_viagem', function (Blueprint $table) {
            $table->dropColumn('km_dispersao');
            $table->dropColumn('km_dispersao_rateio');
        });
    }
};
