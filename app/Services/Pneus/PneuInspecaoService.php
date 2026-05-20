<?php

namespace App\Services\Pneus;

use App\Enum\Pneu\LocalPneuEnum;
use App\Enum\Pneu\ResultadoInspecaoPneuEnum;
use App\Enum\Pneu\StatusPneuEnum;
use App\Enum\Pneu\TipoInspecaoPneuEnum;
use App\Models\Pneu;
use App\Models\PneuInspecao;

class PneuInspecaoService
{
    public function syncResultado(PneuInspecao $inspecao): void
    {
        $pneu = $inspecao->pneu;

        if (! $pneu) {
            return;
        }

        if ($inspecao->resultado === ResultadoInspecaoPneuEnum::CONDENADO) {
            $pneu->update([
                'status' => StatusPneuEnum::SUCATA,
                'local' => LocalPneuEnum::SUCATA,
            ]);

            (new PneuCicloService)->closeCurrentCycle($pneu, $inspecao->data_inspecao, $inspecao->km_referencia);

            return;
        }

        if ($inspecao->resultado === ResultadoInspecaoPneuEnum::REPROVADO && $pneu->status !== StatusPneuEnum::SUCATA) {
            $pneu->update([
                'status' => StatusPneuEnum::INDISPONIVEL,
                'local' => LocalPneuEnum::MANUTENCAO,
            ]);

            return;
        }

        if (
            in_array($inspecao->tipo, [TipoInspecaoPneuEnum::RECEBIMENTO, TipoInspecaoPneuEnum::POS_RECAPAGEM], true)
            && in_array($inspecao->resultado, [ResultadoInspecaoPneuEnum::APROVADO, ResultadoInspecaoPneuEnum::APROVADO_COM_RESSALVA], true)
            && $pneu->status !== StatusPneuEnum::EM_USO
            && $pneu->status !== StatusPneuEnum::SUCATA
        ) {
            $pneu->update([
                'status' => StatusPneuEnum::DISPONIVEL,
                'local' => LocalPneuEnum::ESTOQUE_CCO,
            ]);
        }
    }

    public function validarAplicacao(Pneu $pneu): ?string
    {
        $inspecao = $this->getUltimaInspecao($pneu);

        if (! $inspecao) {
            return null;
        }

        if ($inspecao->resultado === ResultadoInspecaoPneuEnum::CONDENADO) {
            return 'O pneu foi condenado na última inspeção e não pode ser aplicado.';
        }

        if ($inspecao->resultado === ResultadoInspecaoPneuEnum::REPROVADO) {
            return 'O pneu foi reprovado na última inspeção e não pode ser aplicado.';
        }

        return null;
    }

    public function validarRecapagem(Pneu $pneu): ?string
    {
        $inspecao = PneuInspecao::query()
            ->where('pneu_id', $pneu->id)
            ->where('pneu_ciclo_id', $pneu->cicloAtual?->id)
            ->where('tipo', TipoInspecaoPneuEnum::PRE_RECAPAGEM)
            ->latest('data_inspecao')
            ->latest('id')
            ->first();

        if (! $inspecao) {
            return 'É necessário registrar uma inspeção do tipo PRE-RECAPAGEM antes de recapá-lo.';
        }

        if ($inspecao->resultado === ResultadoInspecaoPneuEnum::CONDENADO) {
            return 'O pneu foi condenado na inspeção de pré-recapagem.';
        }

        if ($inspecao->resultado === ResultadoInspecaoPneuEnum::REPROVADO || ! $inspecao->apto_recapagem) {
            return 'A última inspeção de pré-recapagem não aprovou o pneu para recapagem.';
        }

        return null;
    }

    public function getUltimaInspecao(Pneu $pneu): ?PneuInspecao
    {
        return PneuInspecao::query()
            ->where('pneu_id', $pneu->id)
            ->latest('data_inspecao')
            ->latest('id')
            ->first();
    }
}
