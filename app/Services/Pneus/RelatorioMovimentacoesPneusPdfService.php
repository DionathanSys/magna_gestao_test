<?php

namespace App\Services\Pneus;

use App\Models\HistoricoMovimentoPneu;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class RelatorioMovimentacoesPneusPdfService
{
    public function getMovimentacoes(string $dataInicial, string $dataFinal): Collection
    {
        return HistoricoMovimentoPneu::query()
            ->with([
                'veiculo:id,placa',
                'pneu:id,numero_fogo',
            ])
            ->whereBetween('created_at', [
                Carbon::parse($dataInicial)->startOfDay(),
                Carbon::parse($dataFinal)->endOfDay(),
            ])
            ->orderBy('veiculo_id')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }

    public function agruparPorVeiculo(Collection $movimentacoes): Collection
    {
        return $movimentacoes
            ->groupBy(fn (HistoricoMovimentoPneu $movimento) => $movimento->veiculo_id ?? 'sem-veiculo')
            ->map(function (Collection $grupo) {
                /** @var HistoricoMovimentoPneu $primeiro */
                $primeiro = $grupo->first();

                return [
                    'veiculo_id' => $primeiro?->veiculo_id,
                    'placa' => $primeiro?->veiculo?->placa ?? 'Sem veículo',
                    'total_movimentacoes' => $grupo->count(),
                    'movimentacoes' => $this->montarOperacoes($grupo),
                ];
            })
            ->sortBy('placa')
            ->values();
    }

    protected function montarOperacoes(Collection $movimentacoes): Collection
    {
        $operacoes = collect();
        $remocoesPendentes = [];

        foreach ($movimentacoes->sortBy(['created_at', 'id'])->values() as $movimento) {
            $chave = $this->getChaveOperacao($movimento);

            if ($movimento->tipo_evento === 'APLICACAO') {
                if (! empty($remocoesPendentes[$chave])) {
                    $indiceOperacao = array_shift($remocoesPendentes[$chave]);
                    $operacao = $operacoes->get($indiceOperacao);

                    $operacao['pneu_aplicado'] = $movimento->pneu?->numero_fogo ?? 'N/A';
                    $operacao['data_aplicacao'] = $movimento->data_inicial;
                    $operacao['km_aplicacao'] = $movimento->km_inicial;
                    $operacao['sulco_aplicacao'] = $movimento->sulco_movimento;
                    $operacao['observacao'] = $operacao['observacao'] ?: $movimento->observacao;
                    $operacao['created_at'] = $operacao['created_at'] ?? $movimento->created_at;

                    $operacoes->put($indiceOperacao, $operacao);

                    continue;
                }

                $operacoes->push($this->criarOperacaoBase($movimento, [
                    'pneu_aplicado' => $movimento->pneu?->numero_fogo ?? 'N/A',
                    'data_aplicacao' => $movimento->data_inicial,
                    'km_aplicacao' => $movimento->km_inicial,
                    'sulco_aplicacao' => $movimento->sulco_movimento,
                ]));

                continue;
            }

            $operacoes->push($this->criarOperacaoBase($movimento, [
                'pneu_removido' => $movimento->pneu?->numero_fogo ?? 'N/A',
                'data_remocao' => $movimento->data_final,
                'km_remocao' => $movimento->km_final,
                'sulco_remocao' => $movimento->sulco_movimento,
            ]));

            $remocoesPendentes[$chave] ??= [];
            $remocoesPendentes[$chave][] = $operacoes->keys()->last();
        }

        return $operacoes->values();
    }

    protected function criarOperacaoBase(HistoricoMovimentoPneu $movimento, array $dados = []): array
    {
        return array_merge([
            'created_at' => $movimento->created_at,
            'motivo' => $movimento->motivo ?? '-',
            'eixo' => $movimento->eixo ?? '-',
            'posicao' => $movimento->posicao ?? '-',
            'pneu_removido' => null,
            'data_remocao' => null,
            'km_remocao' => null,
            'sulco_remocao' => null,
            'pneu_aplicado' => null,
            'data_aplicacao' => null,
            'km_aplicacao' => null,
            'sulco_aplicacao' => null,
            'observacao' => $movimento->observacao,
        ], $dados);
    }

    protected function getChaveOperacao(HistoricoMovimentoPneu $movimento): string
    {
        $dataReferencia = $movimento->tipo_evento === 'APLICACAO'
            ? $movimento->data_inicial
            : $movimento->data_final;

        $kmReferencia = $movimento->tipo_evento === 'APLICACAO'
            ? $movimento->km_inicial
            : $movimento->km_final;

        return implode('|', [
            $movimento->veiculo_id,
            $movimento->pneu_posicao_veiculo_id,
            $movimento->eixo,
            $movimento->posicao,
            $movimento->motivo,
            $dataReferencia,
            $kmReferencia,
        ]);
    }

    public function gerarPdf(string $dataInicial, string $dataFinal): mixed
    {
        $movimentacoes = $this->getMovimentacoes($dataInicial, $dataFinal);
        $veiculos = $this->agruparPorVeiculo($movimentacoes);

        $pdf = Pdf::loadView('pdf.relatorio-movimentacoes-pneus', [
            'veiculos' => $veiculos,
            'dataInicial' => Carbon::parse($dataInicial)->format('d/m/Y'),
            'dataFinal' => Carbon::parse($dataFinal)->format('d/m/Y'),
            'dataGeracao' => now()->format('d/m/Y H:i:s'),
            'totalMovimentacoes' => $movimentacoes->count(),
            'totalOperacoes' => $veiculos->sum(fn (array $veiculo) => count($veiculo['movimentacoes'])),
        ]);

        $pdf->setPaper('a4', 'landscape');

        $fileName = 'relatorio_movimentacoes_pneus_' . now()->format('Y-m-d_His') . '.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $fileName, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
