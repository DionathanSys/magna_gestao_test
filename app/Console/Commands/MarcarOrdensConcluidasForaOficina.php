<?php

namespace App\Console\Commands;

use App\Enum\OrdemServico\StatusOrdemServicoEnum;
use App\Models\OrdemServico;
use Illuminate\Console\Command;

class MarcarOrdensConcluidasForaOficina extends Command
{
    protected $signature = 'ordem-servico:concluidas-fora-oficina';

    protected $description = 'Marca como fora da oficina todas as ordens de serviço concluídas';

    public function handle(): int
    {
        $atualizadas = OrdemServico::query()
            ->where('status', StatusOrdemServicoEnum::CONCLUIDO)
            ->where('veiculo_na_oficina', true)
            ->update(['veiculo_na_oficina' => false]);

        $this->info("Ordens concluídas marcadas como fora da oficina: {$atualizadas}.");

        return self::SUCCESS;
    }
}
