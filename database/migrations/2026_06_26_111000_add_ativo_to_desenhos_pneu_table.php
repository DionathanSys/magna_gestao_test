<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('desenhos_pneu', function (Blueprint $table) {
            if (! Schema::hasColumn('desenhos_pneu', 'ativo')) {
                $table->boolean('ativo')->default(true)->after('estado_pneu');
            }
        });
    }

    public function down(): void
    {
        Schema::table('desenhos_pneu', function (Blueprint $table) {
            if (Schema::hasColumn('desenhos_pneu', 'ativo')) {
                $table->dropColumn('ativo');
            }
        });
    }
};
