<?php

namespace App\Services\Estoque;

use App\Models\EstoqueMovimento;
use App\Models\EstoqueProduto;
use Carbon\CarbonInterface;

class EstoqueProdutoService
{
    public function atualizarIndicadores(EstoqueProduto $produto): void
    {
        $ultimoMovimento = EstoqueMovimento::query()
            ->where('estoque_produto_id', $produto->id)
            ->max('data_movimento');

        $diasObsolescencia = $ultimoMovimento
            ? now()->startOfDay()->diffInDays($ultimoMovimento)
            : null;

        $saidaUltimos30Dias = (float) EstoqueMovimento::query()
            ->where('estoque_produto_id', $produto->id)
            ->whereDate('data_movimento', '>=', now()->subDays(30)->toDateString())
            ->sum('quantidade_saida');

        $mediaDiariaSaida = $saidaUltimos30Dias / 30;
        $previsaoConsumoDias = $mediaDiariaSaida > 0
            ? (int) ceil((float) $produto->saldo / $mediaDiariaSaida)
            : null;

        $produto->forceFill([
            'ultimo_movimento_em' => $ultimoMovimento,
            'dias_obsolescencia' => $diasObsolescencia,
            'previsao_consumo_dias' => $previsaoConsumoDias,
        ])->save();
    }

    public function registrarMovimento(
        EstoqueProduto $produto,
        CarbonInterface $dataMovimento,
        float $quantidadeEntrada,
        float $quantidadeSaida,
        ?int $importLogId = null,
    ): EstoqueMovimento {
        $movimento = EstoqueMovimento::query()->updateOrCreate([
            'estoque_produto_id' => $produto->id,
            'data_movimento' => $dataMovimento->toDateString(),
            'origem' => 'Relatório diário',
        ], [
            'quantidade_entrada' => $quantidadeEntrada,
            'quantidade_saida' => $quantidadeSaida,
            'saldo_apos_movimento' => $produto->saldo,
            'import_log_id' => $importLogId,
        ]);

        $this->atualizarIndicadores($produto->refresh());

        return $movimento;
    }
}
