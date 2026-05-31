<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('viagens', function (Blueprint $table) {
            if (! Schema::hasColumn('viagens', 'integrados_nomes_cache')) {
                $table->text('integrados_nomes_cache')->nullable()->after('numero_viagem_interno');
            }

            if (! Schema::hasColumn('viagens', 'documentos_frete_resumo_cache')) {
                $table->text('documentos_frete_resumo_cache')->nullable()->after('integrados_nomes_cache');
            }

            if (! Schema::hasColumn('viagens', 'parceiro_frete_cache')) {
                $table->text('parceiro_frete_cache')->nullable()->after('documentos_frete_resumo_cache');
            }

            if (! Schema::hasColumn('viagens', 'pendencias_resumo')) {
                $table->text('pendencias_resumo')->nullable()->after('possui_pendencia');
            }
        });

        DB::statement('CREATE INDEX viagens_numero_viagem_idx ON viagens (numero_viagem)');
        DB::statement('CREATE INDEX viagens_documento_transporte_data_competencia_idx ON viagens (documento_transporte, data_competencia)');
        DB::statement('CREATE INDEX viagens_possui_pendencia_ignorar_viagem_idx ON viagens (possui_pendencia, ignorar_viagem)');
        DB::statement('CREATE INDEX cargas_viagem_viagem_integrado_idx ON cargas_viagem (viagem_id, integrado_id)');
        DB::statement('CREATE INDEX cargas_viagem_documento_transporte_idx ON cargas_viagem (documento_transporte)');
        DB::statement('CREATE INDEX documentos_frete_viagem_documento_idx ON documentos_frete (viagem_id, documento_transporte)');
        DB::statement('CREATE INDEX documentos_frete_data_emissao_idx ON documentos_frete (data_emissao)');

        DB::statement(<<<'SQL'
            UPDATE viagens v
            LEFT JOIN (
                SELECT cv.viagem_id,
                       GROUP_CONCAT(DISTINCT CONCAT(i.nome, ' - ', i.municipio) SEPARATOR '<br>') AS resumo
                FROM cargas_viagem cv
                JOIN integrados i ON i.id = cv.integrado_id
                GROUP BY cv.viagem_id
            ) c ON c.viagem_id = v.id
            SET v.integrados_nomes_cache = COALESCE(c.resumo, '')
        SQL);

        DB::statement(<<<'SQL'
            UPDATE viagens v
            LEFT JOIN (
                SELECT df.viagem_id,
                       GROUP_CONCAT(CONCAT('Nº ', df.numero_documento, ' - R$', REPLACE(ROUND(df.valor_liquido / 100, 2), '.', ',')) SEPARATOR '<br>') AS resumo
                FROM documentos_frete df
                WHERE df.viagem_id IS NOT NULL
                GROUP BY df.viagem_id
            ) d ON d.viagem_id = v.id
            SET v.documentos_frete_resumo_cache = COALESCE(d.resumo, '')
        SQL);

        DB::statement(<<<'SQL'
            UPDATE viagens v
            LEFT JOIN (
                SELECT df.viagem_id,
                       GROUP_CONCAT(df.parceiro_destino SEPARATOR ';<br>') AS resumo
                FROM documentos_frete df
                WHERE df.viagem_id IS NOT NULL
                GROUP BY df.viagem_id
            ) d ON d.viagem_id = v.id
            SET v.parceiro_frete_cache = COALESCE(d.resumo, '')
        SQL);

        DB::statement(<<<'SQL'
            UPDATE viagens v
            SET v.pendencias_resumo = CASE
                WHEN v.possui_pendencia = 0 THEN 'Sem pendencias'
                ELSE TRIM(BOTH '; ' FROM CONCAT_WS('; ',
                    CASE WHEN COALESCE(v.qtde_destino_viagem, 0) > 1 THEN 'Multiplos destinos' END,
                    CASE WHEN COALESCE(v.km_pago, 0) <= 0 THEN 'Sem km pago' END,
                    CASE WHEN COALESCE(v.km_rodado, 0) <= 0 THEN 'Sem km rodado' END,
                    CASE WHEN EXISTS (SELECT 1 FROM cargas_viagem cv WHERE cv.viagem_id = v.id AND cv.integrado_id IS NULL) THEN 'Carga sem integrado' END,
                    CASE WHEN NOT EXISTS (SELECT 1 FROM cargas_viagem cv WHERE cv.viagem_id = v.id) THEN 'Sem carga' END
                ))
            END
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP INDEX viagens_numero_viagem_idx ON viagens');
        DB::statement('DROP INDEX viagens_documento_transporte_data_competencia_idx ON viagens');
        DB::statement('DROP INDEX viagens_possui_pendencia_ignorar_viagem_idx ON viagens');
        DB::statement('DROP INDEX cargas_viagem_viagem_integrado_idx ON cargas_viagem');
        DB::statement('DROP INDEX cargas_viagem_documento_transporte_idx ON cargas_viagem');
        DB::statement('DROP INDEX documentos_frete_viagem_documento_idx ON documentos_frete');
        DB::statement('DROP INDEX documentos_frete_data_emissao_idx ON documentos_frete');

        Schema::table('viagens', function (Blueprint $table) {
            foreach (['integrados_nomes_cache', 'documentos_frete_resumo_cache', 'parceiro_frete_cache', 'pendencias_resumo'] as $column) {
                if (Schema::hasColumn('viagens', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
