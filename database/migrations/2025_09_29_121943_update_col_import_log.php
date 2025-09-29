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
        Schema::table('import_logs', function (Blueprint $table) {
            if (Schema::hasColumn('import_logs', 'progress_percentage')) {
                $table->dropColumn('progress_percentage');
            }

             // Remover processed_rows se existir (para recriar como virtual)
            if (Schema::hasColumn('import_logs', 'processed_rows')) {
                $table->dropColumn('processed_rows');
            }

            // Adicionar processed_rows como coluna virtual
            $table->integer('processed_rows')
                  ->virtualAs('(success_rows + error_rows + warning_rows + skipped_rows)')
                  ->after('total_rows');

            // Adicionar coluna virtual
            $table->decimal('progress_percentage', 5, 2)
                  ->virtualAs('CASE WHEN total_batches > 0 THEN ROUND((processed_batches / total_batches) * 100, 2) ELSE 0 END')
                  ->after('processed_batches');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('import_logs', function (Blueprint $table) {
            // Remover colunas virtuais
            $table->dropColumn(['progress_percentage', 'processed_rows']);

            // Recriar as colunas físicas
            $table->integer('processed_rows')->default(0)->after('total_rows');
            $table->decimal('progress_percentage', 5, 2)->default(0.0)->after('processed_rows');
        });
    }
};
