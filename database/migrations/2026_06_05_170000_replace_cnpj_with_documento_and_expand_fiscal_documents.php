<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('integrados', 'documento')) {
            Schema::table('integrados', function (Blueprint $table) {
                $table->string('documento', 20)->nullable()->after('nome');
                $table->index('documento');
            });
        }

        if (Schema::hasColumn('integrados', 'cnpj')) {
            DB::statement('UPDATE integrados SET documento = cnpj WHERE documento IS NULL AND cnpj IS NOT NULL');
        }

        Schema::table('received_fiscal_documents', function (Blueprint $table) {
            if (! Schema::hasColumn('received_fiscal_documents', 'emitente_nome')) {
                $table->string('emitente_nome')->nullable()->after('emitido_em');
            }

            if (! Schema::hasColumn('received_fiscal_documents', 'emitente_documento')) {
                $table->string('emitente_documento', 20)->nullable()->after('emitente_nome');
            }

            if (! Schema::hasColumn('received_fiscal_documents', 'destinatario_documento')) {
                $table->string('destinatario_documento', 20)->nullable()->after('destinatario_nome');
            }

            if (! Schema::hasColumn('received_fiscal_documents', 'transportador_documento')) {
                $table->string('transportador_documento', 20)->nullable()->after('transportador_nome');
            }
        });

        if (Schema::hasColumn('received_fiscal_documents', 'destinatario_cnpj')) {
            DB::statement('UPDATE received_fiscal_documents SET destinatario_documento = destinatario_cnpj WHERE destinatario_documento IS NULL AND destinatario_cnpj IS NOT NULL');
        }

        if (Schema::hasColumn('received_fiscal_documents', 'transportador_cnpj')) {
            DB::statement('UPDATE received_fiscal_documents SET transportador_documento = transportador_cnpj WHERE transportador_documento IS NULL AND transportador_cnpj IS NOT NULL');
        }
    }

    public function down(): void
    {
        Schema::table('received_fiscal_documents', function (Blueprint $table) {
            foreach (['emitente_nome', 'emitente_documento', 'destinatario_documento', 'transportador_documento'] as $column) {
                if (Schema::hasColumn('received_fiscal_documents', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        if (Schema::hasColumn('integrados', 'documento')) {
            Schema::table('integrados', function (Blueprint $table) {
                $table->dropIndex(['documento']);
                $table->dropColumn('documento');
            });
        }
    }
};
