<?php

namespace App\Services\Pneus;

use App\Enum\Pneu\StatusCicloPneuEnum;
use App\Models;

class PneuCicloService
{
    public function ensureCurrentCycle(Models\Pneu $pneu): Models\PneuCiclo
    {
        $numero = max(0, (int) $pneu->ciclo_vida);

        return Models\PneuCiclo::query()->firstOrCreate(
            [
                'pneu_id' => $pneu->id,
                'numero' => $numero,
            ],
            [
                'desenho_pneu_id' => $this->resolveCurrentDesenhoId($pneu),
                'status' => StatusCicloPneuEnum::ABERTO->value,
                'data_abertura' => $this->resolveCurrentCycleStartDate($pneu, $numero),
            ]
        );
    }

    public function openCycleFromRecapagem(Models\Pneu $pneu, Models\Recapagem $recapagem): Models\PneuCiclo
    {
        $this->closePreviousOpenCycle($pneu, $recapagem->data_recapagem);

        return Models\PneuCiclo::query()->updateOrCreate(
            [
                'pneu_id' => $pneu->id,
                'numero' => (int) $recapagem->ciclo_vida,
            ],
            [
                'desenho_pneu_id' => $recapagem->desenho_pneu_id,
                'status' => StatusCicloPneuEnum::ABERTO->value,
                'data_abertura' => $recapagem->data_recapagem,
            ]
        );
    }

    public function closeCurrentCycle(Models\Pneu $pneu, mixed $dataFechamento = null, mixed $kmFinal = null): void
    {
        $ciclo = $this->getCurrentCycle($pneu);

        if (! $ciclo) {
            return;
        }

        $ciclo->update([
            'status' => StatusCicloPneuEnum::ENCERRADO->value,
            'data_fechamento' => $dataFechamento,
            'km_final' => $kmFinal,
        ]);
    }

    public function getCurrentCycle(?Models\Pneu $pneu): ?Models\PneuCiclo
    {
        if (! $pneu) {
            return null;
        }

        return Models\PneuCiclo::query()
            ->where('pneu_id', $pneu->id)
            ->where('numero', (int) $pneu->ciclo_vida)
            ->first();
    }

    private function closePreviousOpenCycle(Models\Pneu $pneu, mixed $dataFechamento): void
    {
        Models\PneuCiclo::query()
            ->where('pneu_id', $pneu->id)
            ->where('numero', '<', (int) $pneu->ciclo_vida)
            ->where('status', StatusCicloPneuEnum::ABERTO->value)
            ->update([
                'status' => StatusCicloPneuEnum::ENCERRADO->value,
                'data_fechamento' => $dataFechamento,
            ]);
    }

    private function resolveCurrentDesenhoId(Models\Pneu $pneu): ?int
    {
        $recapagem = $pneu->recapagens()
            ->where('ciclo_vida', (string) $pneu->ciclo_vida)
            ->latest('data_recapagem')
            ->first();

        if ($recapagem?->desenho_pneu_id) {
            return $recapagem->desenho_pneu_id;
        }

        return is_numeric((string) $pneu->desenho_pneu_id)
            ? (int) $pneu->desenho_pneu_id
            : null;
    }

    private function resolveCurrentCycleStartDate(Models\Pneu $pneu, int $numero): mixed
    {
        if ($numero === 0) {
            return $pneu->data_aquisicao;
        }

        return $pneu->recapagens()
            ->where('ciclo_vida', (string) $numero)
            ->latest('data_recapagem')
            ->value('data_recapagem')
            ?? $pneu->data_aquisicao;
    }
}
