<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resultado_periodos', function (Blueprint $table) {
            $table->bigInteger('folha_pagamento_centavos')->default(0)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('resultado_periodos', function (Blueprint $table) {
            $table->dropColumn('folha_pagamento_centavos');
        });
    }
};
