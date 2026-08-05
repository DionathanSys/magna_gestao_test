<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordens_servico', function (Blueprint $table): void {
            $table->boolean('veiculo_na_oficina')->default(true)->after('veiculo_id');
        });
    }

    public function down(): void
    {
        Schema::table('ordens_servico', function (Blueprint $table): void {
            $table->dropColumn('veiculo_na_oficina');
        });
    }
};
