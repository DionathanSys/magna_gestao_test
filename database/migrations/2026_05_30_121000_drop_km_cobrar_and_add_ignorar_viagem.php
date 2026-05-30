<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('viagens', function (Blueprint $table) {
            if (Schema::hasColumn('viagens', 'km_cobrar')) {
                $table->dropColumn('km_cobrar');
            }

            if (! Schema::hasColumn('viagens', 'ignorar_viagem')) {
                $table->boolean('ignorar_viagem')->default(false)->after('conferido');
            }
        });

        if (Schema::hasTable('viagem_complementos')) {
            Schema::table('viagem_complementos', function (Blueprint $table) {
                if (Schema::hasColumn('viagem_complementos', 'km_cobrar')) {
                    $table->dropColumn('km_cobrar');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('viagem_complementos')) {
            Schema::table('viagem_complementos', function (Blueprint $table) {
                if (! Schema::hasColumn('viagem_complementos', 'km_cobrar')) {
                    $table->decimal('km_cobrar', 10, 2)->default(0);
                }
            });
        }

        Schema::table('viagens', function (Blueprint $table) {
            if (Schema::hasColumn('viagens', 'ignorar_viagem')) {
                $table->dropColumn('ignorar_viagem');
            }

            if (! Schema::hasColumn('viagens', 'km_cobrar')) {
                $table->decimal('km_cobrar', 10, 2)->default(0);
            }
        });
    }
};
