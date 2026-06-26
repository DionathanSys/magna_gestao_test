<?php

namespace App\Console\Commands;

use App\Services\Pneus\MapaPneuBackfillService;
use Illuminate\Console\Command;

class BackfillMapaPneuData extends Command
{
    protected $signature = 'pneus:backfill-mapas {--dry-run : Apenas mostra o que seria atualizado sem gravar}';

    protected $description = 'Cria mapas padrao de pneus e vincula veiculos/posicoes existentes ao novo modelo';

    public function handle(MapaPneuBackfillService $service): int
    {
        $result = $service->run((bool) $this->option('dry-run'));

        $this->info('Backfill de mapas de pneu concluido.');
        $this->line('Dry-run: '.($result['dry_run'] ? 'sim' : 'nao'));
        $this->line('Veiculos atualizados: '.$result['veiculos_atualizados']);
        $this->line('Posicoes atualizadas: '.$result['posicoes_atualizadas']);
        $this->line('Veiculos sem mapa: '.count($result['veiculos_sem_mapa']));
        $this->line('Posicoes sem correspondencia: '.count($result['posicoes_sem_correspondencia']));

        if ($result['veiculos_sem_mapa'] !== []) {
            $this->newLine();
            $this->warn('Veiculos sem mapa resolvido:');

            foreach (array_slice($result['veiculos_sem_mapa'], 0, 20) as $item) {
                $linha = sprintf('- veiculo_id=%s placa=%s', $item['veiculo_id'], $item['placa'] ?? 'N/A');

                if (! empty($item['motivo'])) {
                    $linha .= ' motivo='.$item['motivo'];
                }

                $this->line($linha);
            }
        }

        if ($result['posicoes_sem_correspondencia'] !== []) {
            $this->newLine();
            $this->warn('Posicoes sem correspondencia no mapa:');

            foreach (array_slice($result['posicoes_sem_correspondencia'], 0, 20) as $item) {
                $this->line(sprintf(
                    '- id=%s veiculo_id=%s placa=%s posicao=%s sequencia=%s',
                    $item['pneu_posicao_veiculo_id'],
                    $item['veiculo_id'],
                    $item['placa'] ?? 'N/A',
                    $item['posicao'] ?? 'N/A',
                    $item['sequencia'] ?? 'N/A',
                ));
            }
        }

        return self::SUCCESS;
    }
}
