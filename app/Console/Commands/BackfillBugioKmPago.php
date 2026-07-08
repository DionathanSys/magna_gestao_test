<?php

namespace App\Console\Commands;

use App\Enum\ClienteEnum;
use App\Models\Viagem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BackfillBugioKmPago extends Command
{
    protected $signature = 'viagem:backfill-km-pago-bugio {--dry-run : Apenas mostra o que seria atualizado} {--chunk=200 : Quantidade de viagens por lote}';

    protected $description = 'Atualiza o km_pago das viagens Bugio usando o maior km_rota entre os integrados vinculados';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $chunkSize = max(1, (int) $this->option('chunk'));

        $analisadas = 0;
        $atualizadas = 0;
        $semIntegrado = 0;
        $semKmRota = 0;
        $comMaisDeUmIntegrado = 0;

        Viagem::query()
            ->where('cliente', ClienteEnum::BUGIO->value)
            ->with(['cargas.integrado:id,km_rota'])
            ->orderBy('id')
            ->chunkById($chunkSize, function ($viagens) use ($dryRun, &$analisadas, &$atualizadas, &$semIntegrado, &$semKmRota, &$comMaisDeUmIntegrado): void {
                foreach ($viagens as $viagem) {
                    $analisadas++;

                    $integrados = $viagem->cargas
                        ->pluck('integrado')
                        ->filter()
                        ->unique('id')
                        ->values();

                    if ($integrados->isEmpty()) {
                        $semIntegrado++;

                        Log::warning('Viagem Bugio sem integrado vinculado no backfill de km_pago.', [
                            'viagem_id' => $viagem->id,
                            'numero_viagem' => $viagem->numero_viagem,
                        ]);

                        continue;
                    }

                    if ($integrados->count() > 1) {
                        $comMaisDeUmIntegrado++;

                        Log::warning('Viagem Bugio com mais de um integrado no backfill de km_pago.', [
                            'viagem_id' => $viagem->id,
                            'numero_viagem' => $viagem->numero_viagem,
                            'integrado_ids' => $integrados->pluck('id')->all(),
                            'km_rota_por_integrado' => $integrados->mapWithKeys(fn ($integrado) => [
                                $integrado->id => $integrado->km_rota,
                            ])->all(),
                        ]);
                    }

                    $kmRotas = $integrados
                        ->filter(fn ($integrado): bool => $integrado->km_rota !== null)
                        ->map(fn ($integrado): float => (float) $integrado->km_rota)
                        ->values();

                    if ($kmRotas->isEmpty()) {
                        $semKmRota++;

                        Log::warning('Viagem Bugio sem km_rota disponivel nos integrados vinculados no backfill de km_pago.', [
                            'viagem_id' => $viagem->id,
                            'numero_viagem' => $viagem->numero_viagem,
                            'integrado_ids' => $integrados->pluck('id')->all(),
                        ]);

                        continue;
                    }

                    $kmPago = $kmRotas->max();
                    $kmPagoAtual = (float) ($viagem->km_pago ?? 0);

                    if ($kmPagoAtual === $kmPago) {
                        continue;
                    }

                    $atualizadas++;

                    if ($dryRun) {
                        $this->line(sprintf(
                            '[dry-run] viagem_id=%d numero_viagem=%s km_pago_atual=%s km_pago_novo=%s',
                            $viagem->id,
                            $viagem->numero_viagem,
                            $kmPagoAtual,
                            $kmPago,
                        ));

                        continue;
                    }

                    $viagem->update([
                        'km_pago' => $kmPago,
                    ]);
                }
            });

        $mensagem = sprintf(
            'Backfill %s. Analisadas: %d. %s: %d. Sem integrado: %d. Sem km_rota: %d. Com mais de um integrado: %d.',
            $dryRun ? 'simulado' : 'concluido',
            $analisadas,
            $dryRun ? 'Que seriam atualizadas' : 'Atualizadas',
            $atualizadas,
            $semIntegrado,
            $semKmRota,
            $comMaisDeUmIntegrado,
        );

        Log::info($mensagem, [
            'dry_run' => $dryRun,
            'chunk' => $chunkSize,
        ]);

        $this->info($mensagem);

        return self::SUCCESS;
    }
}
