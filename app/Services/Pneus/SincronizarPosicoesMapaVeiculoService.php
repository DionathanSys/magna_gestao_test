<?php

namespace App\Services\Pneus;

use App\Models\MapaPneuPosicao;
use App\Models\PneuPosicaoVeiculo;
use App\Models\Veiculo;
use DomainException;
use Illuminate\Support\Collection;

class SincronizarPosicoesMapaVeiculoService
{
    public function handle(Veiculo $veiculo, ?int $previousMapaPneuId = null): array
    {
        $veiculo->loadMissing(['mapaPneu.posicoes', 'pneus']);

        if (! $veiculo->mapa_pneu_id || ! $veiculo->mapaPneu) {
            return [
                'created' => 0,
                'updated' => 0,
                'unchanged' => 0,
            ];
        }

        $mapaFoiAlterado = $previousMapaPneuId !== null && $previousMapaPneuId !== (int) $veiculo->mapa_pneu_id;

        if ($mapaFoiAlterado && $veiculo->pneus->contains(fn (PneuPosicaoVeiculo $posicao) => filled($posicao->pneu_id))) {
            throw new DomainException('Não é permitido alterar o mapa do veiculo enquanto houver pneus aplicados nas posições atuais.');
        }

        $existing = $veiculo->pneus->keyBy('id');
        $availableByMapaPosicao = $existing
            ->filter(fn (PneuPosicaoVeiculo $posicao) => filled($posicao->mapa_pneu_posicao_id))
            ->keyBy(fn (PneuPosicaoVeiculo $posicao) => (int) $posicao->mapa_pneu_posicao_id);
        $availableByCodigo = $existing
            ->filter(fn (PneuPosicaoVeiculo $posicao) => filled($posicao->posicao))
            ->keyBy(fn (PneuPosicaoVeiculo $posicao) => strtoupper((string) $posicao->posicao));
        $availableBySequencia = $existing
            ->groupBy(fn (PneuPosicaoVeiculo $posicao) => (int) $posicao->sequencia);

        $usedIds = [];
        $stats = [
            'created' => 0,
            'updated' => 0,
            'unchanged' => 0,
        ];

        foreach ($veiculo->mapaPneu->posicoes->sortBy('sequencia') as $mapaPosicao) {
            $posicaoVeiculo = $this->resolveExistingPosition(
                $mapaPosicao,
                $availableByMapaPosicao,
                $availableByCodigo,
                $availableBySequencia,
                $usedIds,
            );

            $payload = $this->makePayload($veiculo, $mapaPosicao);

            if (! $posicaoVeiculo) {
                PneuPosicaoVeiculo::query()->create($payload);
                $stats['created']++;
                continue;
            }

            $usedIds[] = $posicaoVeiculo->id;

            if ($this->needsUpdate($posicaoVeiculo, $payload)) {
                $posicaoVeiculo->update($payload);
                $stats['updated']++;
                continue;
            }

            $stats['unchanged']++;
        }

        return $stats;
    }

    protected function resolveExistingPosition(
        MapaPneuPosicao $mapaPosicao,
        Collection $availableByMapaPosicao,
        Collection $availableByCodigo,
        Collection $availableBySequencia,
        array $usedIds,
    ): ?PneuPosicaoVeiculo {
        $match = $availableByMapaPosicao->get($mapaPosicao->id);

        if ($match && ! in_array($match->id, $usedIds, true)) {
            return $match;
        }

        $match = $availableByCodigo->get(strtoupper((string) $mapaPosicao->codigo));

        if ($match && ! in_array($match->id, $usedIds, true)) {
            return $match;
        }

        return $availableBySequencia
            ->get((int) $mapaPosicao->sequencia, collect())
            ->first(fn (PneuPosicaoVeiculo $posicao) => ! in_array($posicao->id, $usedIds, true));
    }

    protected function makePayload(Veiculo $veiculo, MapaPneuPosicao $mapaPosicao): array
    {
        return [
            'veiculo_id' => $veiculo->id,
            'mapa_pneu_posicao_id' => $mapaPosicao->id,
            'sequencia' => $mapaPosicao->sequencia,
            'eixo' => $mapaPosicao->eixo_numero,
            'posicao' => $mapaPosicao->codigo,
        ];
    }

    protected function needsUpdate(PneuPosicaoVeiculo $posicaoVeiculo, array $payload): bool
    {
        return (int) $posicaoVeiculo->veiculo_id !== (int) $payload['veiculo_id']
            || (int) ($posicaoVeiculo->mapa_pneu_posicao_id ?? 0) !== (int) $payload['mapa_pneu_posicao_id']
            || (int) $posicaoVeiculo->sequencia !== (int) $payload['sequencia']
            || (string) $posicaoVeiculo->eixo !== (string) $payload['eixo']
            || (string) $posicaoVeiculo->posicao !== (string) $payload['posicao'];
    }
}
