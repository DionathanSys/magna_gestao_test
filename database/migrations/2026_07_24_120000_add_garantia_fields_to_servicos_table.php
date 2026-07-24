<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servicos', function (Blueprint $table): void {
            $table->unsignedInteger('garantia_km')->nullable()->after('controla_posicao');
            $table->unsignedInteger('garantia_dias')->nullable()->after('garantia_km');
        });
    }

    public function down(): void
    {
        Schema::table('servicos', function (Blueprint $table): void {
            $table->dropColumn(['garantia_km', 'garantia_dias']);
        });
    }
};
