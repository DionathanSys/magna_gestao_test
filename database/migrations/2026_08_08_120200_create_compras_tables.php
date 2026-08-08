<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compra_pedidos', function (Blueprint $table): void {
            $table->id();
            $table->string('numero')->unique();
            $table->string('status')->default('aberto');
            $table->text('observacoes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });

        Schema::create('compra_pedido_itens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('compra_pedido_id')
                ->constrained('compra_pedidos')
                ->cascadeOnDelete();
            $table->foreignId('estoque_produto_id')
                ->constrained('estoque_produtos')
                ->restrictOnDelete();
            $table->decimal('quantidade_pedida', 14, 4)->default(0);
            $table->decimal('quantidade_recebida', 14, 4)->default(0);
            $table->timestamps();

            $table->unique(['compra_pedido_id', 'estoque_produto_id'], 'pedido_produto_unique');
        });

        Schema::create('compra_ordens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('compra_pedido_id')
                ->constrained('compra_pedidos')
                ->cascadeOnDelete();
            $table->foreignId('parceiro_id')
                ->constrained('parceiros')
                ->restrictOnDelete();
            $table->string('status')->default('aberto');
            $table->date('previsao_entrega_em')->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('previsao_entrega_em');
        });

        Schema::create('compra_ordem_itens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('compra_ordem_id')
                ->constrained('compra_ordens')
                ->cascadeOnDelete();
            $table->foreignId('compra_pedido_item_id')
                ->constrained('compra_pedido_itens')
                ->cascadeOnDelete();
            $table->foreignId('estoque_produto_id')
                ->constrained('estoque_produtos')
                ->restrictOnDelete();
            $table->decimal('quantidade_prevista', 14, 4)->default(0);
            $table->decimal('quantidade_recebida', 14, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('compra_recebimentos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('compra_ordem_id')
                ->constrained('compra_ordens')
                ->cascadeOnDelete();
            $table->dateTime('recebido_em');
            $table->text('observacoes')->nullable();
            $table->timestamps();
        });

        Schema::create('compra_recebimento_itens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('compra_recebimento_id')
                ->constrained('compra_recebimentos')
                ->cascadeOnDelete();
            $table->foreignId('compra_ordem_item_id')
                ->constrained('compra_ordem_itens')
                ->cascadeOnDelete();
            $table->foreignId('estoque_produto_id')
                ->constrained('estoque_produtos')
                ->restrictOnDelete();
            $table->decimal('quantidade_recebida', 14, 4)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compra_recebimento_itens');
        Schema::dropIfExists('compra_recebimentos');
        Schema::dropIfExists('compra_ordem_itens');
        Schema::dropIfExists('compra_ordens');
        Schema::dropIfExists('compra_pedido_itens');
        Schema::dropIfExists('compra_pedidos');
    }
};
