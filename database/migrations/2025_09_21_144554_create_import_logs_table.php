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
        Schema::create('import_logs', function (Blueprint $table) {
            $table->id();

             // Informações do arquivo
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_size')->nullable(); // Tamanho do arquivo em bytes
            $table->string('file_hash')->nullable(); // Hash do arquivo para evitar duplicatas

            // Tipo de importação
            $table->string('import_type'); // Classe do importador
            $table->string('import_description')->nullable(); // Descrição amigável

            // Usuário responsável
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('status');

            // Contadores
            $table->integer('total_rows')->default(0);
            $table->integer('processed_rows')->default(0);
            $table->integer('success_rows')->default(0);
            $table->integer('error_rows')->default(0);
            $table->integer('warning_rows')->default(0);
            $table->integer('skipped_rows')->default(0);

            // Progresso para processamento assíncrono
            $table->integer('total_batches')->default(0);
            $table->integer('processed_batches')->default(0);
            $table->decimal('progress_percentage', 5, 2)->default(0.00);

            // Logs de erros e avisos
            $table->json('errors')->nullable();
            $table->json('warnings')->nullable();
            $table->json('skipped_reasons')->nullable();

            // Configurações da importação
            $table->json('options')->nullable(); // batch_size, use_queue, etc.
            $table->json('mapping')->nullable(); // Mapeamento de colunas se necessário

            // Tempos de execução
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->integer('duration_seconds')->nullable(); // Duração em segundos

            // Informações adicionais
            $table->text('notes')->nullable(); // Observações do usuário
            $table->json('summary')->nullable(); // Resumo dos dados importados

            $table->timestamps();

             // Índices
            $table->index(['user_id', 'status']);
            $table->index(['import_type', 'status']);
            $table->index('created_at');
            $table->index('file_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_logs');
    }
};
