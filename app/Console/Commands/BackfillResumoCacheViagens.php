<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillResumoCacheViagens extends Command
{
    protected $signature = 'viagem:backfill-resumo-cache';

    protected $description = 'Recalcula e popula os campos cache de resumo (documentos_frete_resumo_cache, parceiro_frete_cache, integrados_nomes_cache) para todas as viagens';

    public function handle(): void
    {
        $this->info('Atualizando documentos_frete_resumo_cache...');

        DB::statement("
            UPDATE viagens v
            LEFT JOIN (
                SELECT df.viagem_id,
                       GROUP_CONCAT(
                           CONCAT('Nº ', df.numero_documento, ' - R$', REPLACE(ROUND(COALESCE(df.valor_liquido, 0) / 100, 2), '.', ','))
                           SEPARATOR '<br>'
                       ) AS resumo
                FROM documentos_frete df
                WHERE df.viagem_id IS NOT NULL
                GROUP BY df.viagem_id
            ) d ON d.viagem_id = v.id
            SET v.documentos_frete_resumo_cache = COALESCE(d.resumo, '')
        ");

        $this->info('Atualizando parceiro_frete_cache...');

        DB::statement("
            UPDATE viagens v
            LEFT JOIN (
                SELECT df.viagem_id,
                       GROUP_CONCAT(COALESCE(df.parceiro_destino, '') SEPARATOR ';<br>') AS resumo
                FROM documentos_frete df
                WHERE df.viagem_id IS NOT NULL
                GROUP BY df.viagem_id
            ) d ON d.viagem_id = v.id
            SET v.parceiro_frete_cache = COALESCE(d.resumo, '')
        ");

        $this->info('Atualizando integrados_nomes_cache...');

        DB::statement("
            UPDATE viagens v
            LEFT JOIN (
                SELECT cv.viagem_id,
                       GROUP_CONCAT(DISTINCT CONCAT(COALESCE(i.nome, ''), ' - ', COALESCE(i.municipio, '')) SEPARATOR '<br>') AS resumo
                FROM cargas_viagem cv
                JOIN integrados i ON i.id = cv.integrado_id
                GROUP BY cv.viagem_id
            ) c ON c.viagem_id = v.id
            SET v.integrados_nomes_cache = COALESCE(c.resumo, '')
        ");

        $this->info('Cache de resumo atualizado com sucesso!');
    }
}
