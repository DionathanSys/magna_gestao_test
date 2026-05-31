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
            if (! Schema::hasColumn('viagens', 'integrados_json')) {
                $table->json('integrados_json')->nullable()->after('numero_viagem_interno');
            }

            if (! Schema::hasColumn('viagens', 'integrados_nomes_cache')) {
                $table->text('integrados_nomes_cache')->nullable()->after('integrados_json');
            }

            if (! Schema::hasColumn('viagens', 'documentos_frete_resumo_cache')) {
                $table->text('documentos_frete_resumo_cache')->nullable()->after('integrados_nomes_cache');
            }

            if (! Schema::hasColumn('viagens', 'parceiro_frete_cache')) {
                $table->text('parceiro_frete_cache')->nullable()->after('documentos_frete_resumo_cache');
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
                       JSON_ARRAYAGG(
                           JSON_OBJECT(
                               'id', i.id,
                               'codigo', i.codigo,
                               'nome', i.nome,
                               'municipio', i.municipio
                           )
                       ) AS resumo_json,
                       GROUP_CONCAT(DISTINCT CONCAT(i.nome, ' - ', i.municipio) SEPARATOR '<br>') AS resumo
                FROM cargas_viagem cv
                JOIN integrados i ON i.id = cv.integrado_id
                GROUP BY cv.viagem_id
            ) c ON c.viagem_id = v.id
            SET v.integrados_json = c.resumo_json,
                v.integrados_nomes_cache = COALESCE(c.resumo, '')
        SQL);

        DB::statement(<<<'SQL'
            UPDATE viagens v
            SET v.integrados_json = COALESCE(v.integrados_json, JSON_ARRAY())
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

        DB::table('viagens')
            ->select(['id', 'qtde_destino_viagem', 'km_pago', 'km_rodado'])
            ->orderBy('id')
            ->chunkById(500, function ($viagens): void {
                foreach ($viagens as $viagem) {
                    $divergencias = [];

                    if ((int) ($viagem->qtde_destino_viagem ?? 0) > 1) {
                        $divergencias['multiplos_destinos'] = 'Multiplos destinos';
                    }

                    if ((float) ($viagem->km_pago ?? 0) <= 0) {
                        $divergencias['sem_km_pago'] = 'Sem km pago';
                    }

                    if ((float) ($viagem->km_rodado ?? 0) <= 0) {
                        $divergencias['sem_km_rodado'] = 'Sem km rodado';
                    }

                    if (! DB::table('cargas_viagem')->where('viagem_id', $viagem->id)->exists()) {
                        $divergencias['sem_carga'] = 'Sem carga';
                    } elseif (DB::table('cargas_viagem')->where('viagem_id', $viagem->id)->whereNull('integrado_id')->exists()) {
                        $divergencias['carga_sem_integrado'] = 'Carga sem integrado';
                    }

                    DB::table('viagens')
                        ->where('id', $viagem->id)
                        ->update(['divergencias' => json_encode($divergencias)]);
                }
            });
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
            foreach (['integrados_json', 'integrados_nomes_cache', 'documentos_frete_resumo_cache', 'parceiro_frete_cache'] as $column) {
                if (Schema::hasColumn('viagens', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
