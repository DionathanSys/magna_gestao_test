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
        Schema::create('viagem_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('cliente');
            $table->unsignedBigInteger('last_number')->default(0);
            $table->timestamps();

            // $table->unique(['cliente']);
        });

        Schema::table('viagens_bugio', function (Blueprint $table) {
            $table->unsignedBigInteger('numero_sequencial')
                ->nullable()
                ->after('nro_notas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('viagens_bugio', function (Blueprint $table) {
            if (Schema::hasColumn('viagens_bugio', 'numero_sequencial')) {
                $table->dropColumn('numero_sequencial');
            }
        });

        Schema::dropIfExists('viagem_sequences');
    }
};
