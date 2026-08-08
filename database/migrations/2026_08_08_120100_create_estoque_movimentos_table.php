<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estoque_movimentos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('estoque_produto_id')
                ->constrained('estoque_produtos')
                ->cascadeOnDelete();
            $table->date('data_movimento');
            $table->decimal('quantidade_entrada', 14, 4)->default(0);
            $table->decimal('quantidade_saida', 14, 4)->default(0);
            $table->decimal('saldo_apos_movimento', 14, 4)->nullable();
            $table->string('origem')->default('Relatório diário');
            $table->foreignId('import_log_id')
                ->nullable()
                ->constrained('import_logs')
                ->nullOnDelete();
            $table->timestamps();

            $table->index('data_movimento');
            $table->index(['estoque_produto_id', 'data_movimento']);
            $table->index('import_log_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estoque_movimentos');
    }
};
