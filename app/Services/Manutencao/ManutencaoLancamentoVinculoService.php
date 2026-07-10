<?php

namespace App\Services\Manutencao;

use App\Models\ManutencaoLancamento;
use App\Models\OrdemSankhya;
use App\Models\OrdemServico;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ManutencaoLancamentoVinculoService
{
    public function conciliarAutomaticamente(ManutencaoLancamento $lancamento): ?OrdemServico
    {
        if ($lancamento->dispensado_vinculo) {
            return null;
        }

        if ($lancamento->ordem_servico_id && $lancamento->tipo_vinculo === 'manual') {
            return $lancamento->ordemServico;
        }

        if (! $this->ehOrigemOs($lancamento->origem) || blank($lancamento->nr_os_nf)) {
            if ($lancamento->tipo_vinculo === 'automatico') {
                $this->desvincular($lancamento);
            }

            return null;
        }

        $ordemServicoId = OrdemSankhya::query()
            ->join('ordens_servico', 'ordens_servico.id', '=', 'ordens_sankhya.ordem_servico_id')
            ->where('ordens_sankhya.ordem_sankhya_id', $lancamento->nr_os_nf)
            ->where('ordens_servico.veiculo_id', $lancamento->veiculo_id)
            ->orderByDesc('ordens_servico.id')
            ->value('ordens_servico.id');

        if (! $ordemServicoId) {
            if ($lancamento->tipo_vinculo === 'automatico') {
                $this->desvincular($lancamento);
            }

            return null;
        }

        $ordemServico = OrdemServico::query()->find($ordemServicoId);

        if (! $ordemServico) {
            return null;
        }

        $this->vincular($lancamento, $ordemServico, 'automatico');

        return $ordemServico;
    }

    public function vincular(ManutencaoLancamento $lancamento, OrdemServico $ordemServico, string $tipoVinculo = 'manual'): void
    {
        $lancamento->forceFill([
            'ordem_servico_id' => $ordemServico->id,
            'tipo_vinculo' => $tipoVinculo,
            'vinculado_em' => now(),
            'vinculado_por' => Auth::id(),
            'dispensado_vinculo' => false,
            'dispensado_em' => null,
            'dispensado_por' => null,
        ])->save();
    }

    public function desvincular(ManutencaoLancamento $lancamento): void
    {
        $lancamento->forceFill([
            'ordem_servico_id' => null,
            'tipo_vinculo' => null,
            'vinculado_em' => null,
            'vinculado_por' => null,
        ])->save();
    }

    public function dispensar(ManutencaoLancamento $lancamento): void
    {
        $lancamento->forceFill([
            'ordem_servico_id' => null,
            'tipo_vinculo' => null,
            'vinculado_em' => null,
            'vinculado_por' => null,
            'dispensado_vinculo' => true,
            'dispensado_em' => now(),
            'dispensado_por' => Auth::id(),
        ])->save();
    }

    public function reabrirPendencia(ManutencaoLancamento $lancamento): void
    {
        $lancamento->forceFill([
            'dispensado_vinculo' => false,
            'dispensado_em' => null,
            'dispensado_por' => null,
        ])->save();
    }

    private function ehOrigemOs(?string $origem): bool
    {
        if (blank($origem)) {
            return false;
        }

        $origemNormalizada = Str::upper(trim($origem));

        if (Str::contains($origemNormalizada, 'NF')) {
            return false;
        }

        return Str::contains($origemNormalizada, ['OS', 'ORDEM']);
    }
}
