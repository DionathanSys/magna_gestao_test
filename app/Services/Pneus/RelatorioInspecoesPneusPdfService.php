<?php

namespace App\Services\Pneus;

use App\Models\Pneu;
use App\Models\PneuInspecao;
use App\Models\PneuPosicaoVeiculo;
use App\Models\Veiculo;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;

class RelatorioInspecoesPneusPdfService
{
    public function getPosicoesVeiculo(int $veiculoId): Collection
    {
        return PneuPosicaoVeiculo::query()
            ->with([
                'pneu.ultimaInspecao',
                'pneu.marcaCatalogo:id,nome',
                'pneu.modeloCatalogo:id,nome',
                'pneu.medidaCatalogo:id,codigo',
                'veiculo.kmAtual',
            ])
            ->where('veiculo_id', $veiculoId)
            ->orderBy('sequencia')
            ->get();
    }

    public function gerarRelatorioVeiculoAtual(int $veiculoId): mixed
    {
        $veiculo = Veiculo::query()
            ->with('kmAtual')
            ->findOrFail($veiculoId);

        $posicoes = $this->getPosicoesVeiculo($veiculoId);
        $linhas = $this->montarLinhasVeiculo($posicoes);
        $linhasPorEixo = $linhas->groupBy(fn (array $linha) => $linha['eixo'] ?: 'Sem eixo');
        $posicoesComSulco = $linhas->filter(fn (array $linha) => $linha['media_sulcos'] !== null);

        $pdf = Pdf::loadView('pdf.relatorio-inspecoes-veiculo-atual', [
            'veiculo' => $veiculo,
            'linhas' => $linhas,
            'linhasPorEixo' => $linhasPorEixo,
            'dataGeracao' => now()->format('d/m/Y H:i:s'),
            'resumo' => [
                'total_posicoes' => $posicoes->count(),
                'total_aplicados' => $posicoes->whereNotNull('pneu_id')->count(),
                'total_com_inspecao' => $linhas->where('tem_inspecao', true)->count(),
                'media_geral' => $posicoesComSulco->isNotEmpty() ? round((float) $posicoesComSulco->avg('media_sulcos'), 2) : null,
                'menor_sulco' => $posicoesComSulco->min('menor_sulco'),
            ],
        ]);

        $pdf->setPaper('a4', 'landscape');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'relatorio_inspecoes_veiculo_'.$veiculo->placa.'_'.now()->format('Y-m-d_His').'.pdf', [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function getInspecoesPneu(int $pneuId): Collection
    {
        return PneuInspecao::query()
            ->with([
                'veiculo:id,placa',
                'posicaoVeiculo:id,posicao,eixo',
                'ciclo:id,pneu_id,numero',
            ])
            ->where('pneu_id', $pneuId)
            ->orderBy('data_inspecao')
            ->orderBy('id')
            ->get();
    }

    public function gerarRelatorioHistoricoPneu(int $pneuId): mixed
    {
        $pneu = Pneu::query()
            ->with([
                'marcaCatalogo:id,nome',
                'modeloCatalogo:id,nome',
                'medidaCatalogo:id,codigo',
                'posicaoVeiculo.veiculo:id,placa',
                'ultimaInspecao',
            ])
            ->findOrFail($pneuId);

        $inspecoes = $this->getInspecoesPneu($pneuId);
        $linhas = $this->montarLinhasPneu($inspecoes);
        $linhasComSulco = $linhas->filter(fn (array $linha) => $linha['media_sulcos'] !== null);
        $ultimaLinha = $linhas->last();

        $pdf = Pdf::loadView('pdf.relatorio-inspecoes-pneu-historico', [
            'pneu' => $pneu,
            'linhas' => $linhas,
            'dataGeracao' => now()->format('d/m/Y H:i:s'),
            'resumo' => [
                'total_inspecoes' => $inspecoes->count(),
                'primeira_inspecao' => $inspecoes->first()?->data_inspecao?->format('d/m/Y'),
                'ultima_inspecao' => $inspecoes->last()?->data_inspecao?->format('d/m/Y'),
                'media_atual' => is_array($ultimaLinha) && $ultimaLinha['media_sulcos'] !== null ? round((float) $ultimaLinha['media_sulcos'], 2) : null,
                'menor_sulco' => $linhasComSulco->min('menor_sulco'),
            ],
        ]);

        $pdf->setPaper('a4', 'landscape');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'relatorio_inspecoes_pneu_'.$pneu->numero_fogo.'_'.now()->format('Y-m-d_His').'.pdf', [
            'Content-Type' => 'application/pdf',
        ]);
    }

    protected function montarLinhasVeiculo(Collection $posicoes): Collection
    {
        return $posicoes->map(function (PneuPosicaoVeiculo $posicao): array {
            $pneu = $posicao->pneu;
            $inspecao = $pneu?->ultimaInspecao;
            $sulcos = $this->normalizarSulcos($inspecao);

            return [
                'eixo' => $posicao->eixo,
                'posicao' => $posicao->posicao,
                'pneu' => $pneu?->numero_fogo,
                'marca_modelo' => trim(implode(' / ', array_filter([
                    $pneu?->marcaCatalogo?->nome,
                    $pneu?->modeloCatalogo?->nome,
                ]))),
                'medida' => $pneu?->medidaCatalogo?->codigo,
                'data_inspecao' => $inspecao?->data_inspecao?->format('d/m/Y'),
                'resultado' => $inspecao?->resultado?->value,
                'km_referencia' => $inspecao?->km_referencia,
                'sulco_interno' => $sulcos['interno'],
                'sulco_centro' => $sulcos['centro'],
                'sulco_externo' => $sulcos['externo'],
                'media_sulcos' => $sulcos['media'],
                'menor_sulco' => $sulcos['menor'],
                'tem_inspecao' => $inspecao !== null,
            ];
        });
    }

    protected function montarLinhasPneu(Collection $inspecoes): Collection
    {
        return $inspecoes->map(function (PneuInspecao $inspecao): array {
            $sulcos = $this->normalizarSulcos($inspecao);

            return [
                'data_inspecao' => $inspecao->data_inspecao?->format('d/m/Y'),
                'tipo' => $inspecao->tipo?->value,
                'resultado' => $inspecao->resultado?->value,
                'veiculo' => $inspecao->veiculo?->placa,
                'posicao' => trim(implode(' / ', array_filter([
                    $inspecao->posicaoVeiculo?->eixo,
                    $inspecao->posicaoVeiculo?->posicao,
                ]))),
                'ciclo' => $inspecao->ciclo?->numero,
                'km_referencia' => $inspecao->km_referencia,
                'sulco_interno' => $sulcos['interno'],
                'sulco_centro' => $sulcos['centro'],
                'sulco_externo' => $sulcos['externo'],
                'media_sulcos' => $sulcos['media'],
                'menor_sulco' => $sulcos['menor'],
                'apto_recapagem' => $inspecao->apto_recapagem,
                'observacao' => $inspecao->observacao,
            ];
        });
    }

    protected function normalizarSulcos(?PneuInspecao $inspecao): array
    {
        $sulcos = collect([
            'interno' => $inspecao?->sulco_interno,
            'centro' => $inspecao?->sulco_centro,
            'externo' => $inspecao?->sulco_externo,
        ])->map(fn ($valor) => $valor !== null ? (float) $valor : null);

        $preenchidos = $sulcos->filter(fn ($valor) => $valor !== null)->values();

        return [
            'interno' => $sulcos['interno'],
            'centro' => $sulcos['centro'],
            'externo' => $sulcos['externo'],
            'media' => $preenchidos->isNotEmpty() ? round((float) $preenchidos->avg(), 2) : null,
            'menor' => $preenchidos->isNotEmpty() ? $preenchidos->min() : null,
        ];
    }
}
