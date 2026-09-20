<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('viagens') || Schema::hasColumn('viagens', 'motoristas')) {
            return;
        }

        Schema::table('viagens', function (Blueprint $table): void {
            $table->json('motoristas')->nullable()->after('pendencias');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('viagens') && Schema::hasColumn('viagens', 'motoristas')) {
            Schema::table('viagens', function (Blueprint $table): void {
                $table->dropColumn('motoristas');
            });
        }
    }
};
