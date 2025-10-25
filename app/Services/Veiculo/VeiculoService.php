<?php

namespace App\Services\Veiculo;

use App\{Models, Services, Enum};
use App\Services\Veiculo\Queries\GetQuilometragemUltimoMovimento;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class VeiculoService
{

    public function getVeiculoIdByPlaca(string $placa): ?int
    {
        $veiculo = \App\Models\Veiculo::query()
            ->select('id')
            ->where('placa', $placa)
            ->first();

        return $veiculo?->id;
    }

    public function getKmMedio(int $veiculoId): float
    {
        $veiculo = \App\Models\Veiculo::query()
            ->select('km_medio')
            ->findOrFail($veiculoId);
        return $veiculo->km_medio ?? 0.0;
    }

    public function getKmAtualVeiculos(array $veiculos): array
    {
        $veiculos = \App\Models\Veiculo::query()
            ->select('id', 'placa', 'km_medio')
            ->with('kmAtual')
            ->whereIn('id', $veiculos)
            ->get();

        $resultado = [];

        foreach ($veiculos as $veiculo) {
            $resultado[$veiculo->id] = [
                'placa' => $veiculo->placa,
                'km_atual' => $veiculo->kmAtual?->quilometragem ?? 0,
                'km_medio' => $veiculo->km_medio ?? 0,
            ];
        }

        return $resultado;
    }

    public static function getQuilometragemAtualByVeiculoId(int $veiculoId): int
    {
        return Cache::remember('km_atual_veiculo_id_' . $veiculoId, 86400, function () use ($veiculoId) {
            $veiculo = \App\Models\Veiculo::query()
                ->select('id', 'placa')
                ->findOrFail($veiculoId);

            return $veiculo->kmAtual?->quilometragem ?? 0;
        });
    }

    public static function getQuilometragemUltimoMovimento(int $veiculoId): int
    {
        return Cache::remember('km_ultimo_movimento_veiculo_id_' . $veiculoId, 86400, function () use ($veiculoId) {
            return (new GetQuilometragemUltimoMovimento())->handle($veiculoId);
        });
    }

    public static function getQuilometragemLimiteMovimentacao(int $veiculoId): array
    {
        $query = new Queries\GetQuilometragemLimiteMovimentacao();
        $km_limite = $query->handle($veiculoId);

        return $km_limite;
    }

    public function setDataUltimoChecklist(int $veiculoId, string $data): void
    {
        $veiculo = Models\Veiculo::query()
            ->where('id', $veiculoId)
            ->select('placa', 'informacoes_complementares')
            ->first();

        if (!$veiculo) {
            Log::error('Veículo não encontrado ao tentar atualizar data do último checklist', [
                'metodo' => __METHOD__.'@'.__LINE__,
                'veiculo_id' => $veiculoId,
                'data' => $data,
            ]);
            throw new \InvalidArgumentException("Veículo com ID {$veiculoId} não encontrado");
        } else {
            Log::debug('Veículo encontrado para atualizar data do último checklist', [
                'metodo' => __METHOD__.'@'.__LINE__,
                'veiculo_id' => $veiculoId,
                'placa' => $veiculo->placa,
                'informacoes_complementares_antes' => $veiculo->informacoes_complementares,
            ]);
        }

        // Buscar o array atual, modificar e reassinar completamente
        $informacoesComplementares = $veiculo->informacoes_complementares ?? [];
        $informacoesComplementares['data_ultimo_checklist'] = $data;

        // Reassinar o array completo
        $veiculo->informacoes_complementares = $informacoesComplementares;

        Log::debug('Atualizando data do último checklist para o veículo ID: ' . $veiculoId . ' para ' . $data, [
            'veiculo_id' => $veiculoId,
            'data' => $data,
            'informacoes_antes' => $veiculo->getOriginal('informacoes_complementares'),
            'informacoes_depois' => $informacoesComplementares,
        ]);

        $veiculo->save();

        Log::debug('Data do último checklist atualizada com sucesso para o veículo ID: ' . $veiculoId, [
            'data_ultimo_checklist' => $data,
            'veiculo_id' => $veiculoId,
            'informacoes_salvas' => $veiculo->fresh()->informacoes_complementares,
        ]);
    }

    public function hasAgendamentoAberto(int $veiculoId): bool
    {
        Log::debug('Verificando agendamento aberto para o veículo ID: ' . $veiculoId);
        return (new Queries\GetAgendamentoAberto())->handle($veiculoId);
    }
}
