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
        $informacoesComplementares = Models\Veiculo::query()
            ->where('id', $veiculoId)
            ->select('informacoes_complementares')
            ->first();

        $informacoesComplementares['data_ultimo_checklist'] = $data;

        Log::debug('Atualizando data do último checklist para o veículo ID: ' . $veiculoId . ' para ' . $data, [
            'informacoes_complementares' => $informacoesComplementares,
        ]);

        $veiculo = Models\Veiculo::query()
            ->where('id', $veiculoId)
            ->update(['informacoes_complementares' => $informacoesComplementares]);
    
        Log::debug('Data do último checklist atualizada com sucesso para o veículo ID: ' . $veiculoId, [
            'data_ultimo_checklist' => $data,
            'veiculo' => $veiculo,
        ]);    
    }

    public function hasAgendamentoAberto(int $veiculoId): bool
    {
        Log::debug('Verificando agendamento aberto para o veículo ID: ' . $veiculoId);
        return (new Queries\GetAgendamentoAberto())->handle($veiculoId);
    }

}
