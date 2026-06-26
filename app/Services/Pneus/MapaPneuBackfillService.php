<?php

namespace App\Services\Pneus;

use App\Models\MapaPneu;
use App\Models\Veiculo;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MapaPneuBackfillService
{
    public function run(bool $dryRun = false): array
    {
        $definitions = $this->defaultMapDefinitions();

        if (! $dryRun) {
            $this->ensureDefaultMaps($definitions);
        }

        $mapsByCode = MapaPneu::query()
            ->with('posicoes:id,mapa_pneu_id,codigo,sequencia')
            ->get()
            ->keyBy(fn (MapaPneu $mapa) => $this->normalizeMapCode($mapa->codigo));

        $result = [
            'dry_run' => $dryRun,
            'mapas_criados' => 0,
            'veiculos_atualizados' => 0,
            'veiculos_sem_mapa' => [],
            'posicoes_atualizadas' => 0,
            'posicoes_sem_correspondencia' => [],
        ];

        if (! $dryRun) {
            $result['mapas_criados'] = $mapsByCode
                ->filter(fn (MapaPneu $mapa) => in_array($this->normalizeMapCode($mapa->codigo), array_keys($definitions), true))
                ->count();
        }

        Veiculo::query()
            ->with(['tipoVeiculo:id,configuracao_pneus,descricao', 'pneus'])
            ->orderBy('id')
            ->chunkById(100, function (Collection $veiculos) use (&$result, $dryRun, $mapsByCode, $definitions): void {
                foreach ($veiculos as $veiculo) {
                    $mapCode = $this->resolveMapCodeForVehicle($veiculo, $definitions);

                    if (! $mapCode) {
                        $result['veiculos_sem_mapa'][] = [
                            'veiculo_id' => $veiculo->id,
                            'placa' => $veiculo->placa,
                        ];

                        continue;
                    }

                    $mapa = $mapsByCode->get($mapCode);

                    if (! $mapa) {
                        $result['veiculos_sem_mapa'][] = [
                            'veiculo_id' => $veiculo->id,
                            'placa' => $veiculo->placa,
                            'motivo' => 'Mapa nao encontrado para codigo '.$mapCode,
                        ];

                        continue;
                    }

                    if ((int) $veiculo->mapa_pneu_id !== (int) $mapa->id) {
                        $result['veiculos_atualizados']++;

                        if (! $dryRun) {
                            $veiculo->forceFill(['mapa_pneu_id' => $mapa->id])->save();
                        }
                    }

                    $positionsByCode = $mapa->posicoes->keyBy(fn ($posicao) => strtoupper((string) $posicao->codigo));
                    $positionsBySequence = $mapa->posicoes->keyBy(fn ($posicao) => (int) $posicao->sequencia);

                    foreach ($veiculo->pneus as $posicaoVeiculo) {
                        $mapaPosicao = $positionsByCode->get(strtoupper((string) $posicaoVeiculo->posicao));

                        if (! $mapaPosicao) {
                            $mapaPosicao = $positionsBySequence->get((int) $posicaoVeiculo->sequencia);
                        }

                        if (! $mapaPosicao) {
                            $result['posicoes_sem_correspondencia'][] = [
                                'pneu_posicao_veiculo_id' => $posicaoVeiculo->id,
                                'veiculo_id' => $veiculo->id,
                                'placa' => $veiculo->placa,
                                'posicao' => $posicaoVeiculo->posicao,
                                'sequencia' => $posicaoVeiculo->sequencia,
                            ];

                            continue;
                        }

                        if ((int) $posicaoVeiculo->mapa_pneu_posicao_id !== (int) $mapaPosicao->id) {
                            $result['posicoes_atualizadas']++;

                            if (! $dryRun) {
                                $posicaoVeiculo->forceFill(['mapa_pneu_posicao_id' => $mapaPosicao->id])->save();
                            }
                        }
                    }
                }
            });

        return $result;
    }

    protected function resolveMapCodeForVehicle(Veiculo $veiculo, array $definitions): ?string
    {
        $fromType = $this->normalizeMapCode($veiculo->tipoVeiculo?->configuracao_pneus);

        if ($fromType && array_key_exists($fromType, $definitions)) {
            return $fromType;
        }

        $positionCodes = $veiculo->pneus
            ->pluck('posicao')
            ->filter()
            ->map(fn ($value) => strtoupper((string) $value))
            ->unique()
            ->sort()
            ->values()
            ->all();

        foreach ($definitions as $mapCode => $definition) {
            $expected = collect($definition['posicoes'])
                ->pluck('codigo')
                ->map(fn ($value) => strtoupper((string) $value))
                ->sort()
                ->values()
                ->all();

            if ($positionCodes === $expected) {
                return $mapCode;
            }
        }

        return match ($veiculo->pneus->count()) {
            10 => '6X2',
            12 => '8X2',
            default => null,
        };
    }

    protected function ensureDefaultMaps(array $definitions): void
    {
        DB::transaction(function () use ($definitions): void {
            foreach ($definitions as $mapCode => $definition) {
                $mapa = MapaPneu::query()->firstOrCreate(
                    ['codigo' => $definition['codigo']],
                    Arr::only($definition, ['codigo', 'nome', 'descricao', 'quantidade_posicoes', 'ativo'])
                );

                $mapa->fill(Arr::only($definition, ['nome', 'descricao', 'quantidade_posicoes', 'ativo']));
                $mapa->save();

                foreach ($definition['posicoes'] as $posicao) {
                    $mapa->posicoes()->updateOrCreate(
                        ['codigo' => $posicao['codigo']],
                        $posicao
                    );
                }
            }
        });
    }

    protected function defaultMapDefinitions(): array
    {
        return [
            '6X2' => [
                'codigo' => '6X2',
                'nome' => 'Caminhao 6x2',
                'descricao' => 'Mapa padrao de 10 posicoes herdado da estrutura atual.',
                'quantidade_posicoes' => 10,
                'ativo' => true,
                'posicoes' => [
                    ['codigo' => 'DD', 'nome' => 'Dianteiro Direito', 'sequencia' => 1, 'eixo_numero' => 1, 'lado' => 'DIREITO', 'conjunto' => 'SIMPLES', 'tipo_posicao' => 'DIRECIONAL', 'aceita_pneu_reserva' => false, 'ativo' => true],
                    ['codigo' => 'DE', 'nome' => 'Dianteiro Esquerdo', 'sequencia' => 2, 'eixo_numero' => 1, 'lado' => 'ESQUERDO', 'conjunto' => 'SIMPLES', 'tipo_posicao' => 'DIRECIONAL', 'aceita_pneu_reserva' => false, 'ativo' => true],
                    ['codigo' => 'TDE', 'nome' => 'Tracao Direito Externo', 'sequencia' => 5, 'eixo_numero' => 3, 'lado' => 'DIREITO', 'conjunto' => 'EXTERNO', 'tipo_posicao' => 'TRACAO', 'aceita_pneu_reserva' => false, 'ativo' => true],
                    ['codigo' => 'TDI', 'nome' => 'Tracao Direito Interno', 'sequencia' => 6, 'eixo_numero' => 3, 'lado' => 'DIREITO', 'conjunto' => 'INTERNO', 'tipo_posicao' => 'TRACAO', 'aceita_pneu_reserva' => false, 'ativo' => true],
                    ['codigo' => 'TEI', 'nome' => 'Tracao Esquerdo Interno', 'sequencia' => 7, 'eixo_numero' => 3, 'lado' => 'ESQUERDO', 'conjunto' => 'INTERNO', 'tipo_posicao' => 'TRACAO', 'aceita_pneu_reserva' => false, 'ativo' => true],
                    ['codigo' => 'TEE', 'nome' => 'Tracao Esquerdo Externo', 'sequencia' => 8, 'eixo_numero' => 3, 'lado' => 'ESQUERDO', 'conjunto' => 'EXTERNO', 'tipo_posicao' => 'TRACAO', 'aceita_pneu_reserva' => false, 'ativo' => true],
                    ['codigo' => '2TDE', 'nome' => '4o Eixo Direito Externo', 'sequencia' => 9, 'eixo_numero' => 4, 'lado' => 'DIREITO', 'conjunto' => 'EXTERNO', 'tipo_posicao' => 'TRACAO', 'aceita_pneu_reserva' => false, 'ativo' => true],
                    ['codigo' => '2TDI', 'nome' => '4o Eixo Direito Interno', 'sequencia' => 10, 'eixo_numero' => 4, 'lado' => 'DIREITO', 'conjunto' => 'INTERNO', 'tipo_posicao' => 'TRACAO', 'aceita_pneu_reserva' => false, 'ativo' => true],
                    ['codigo' => '2TEI', 'nome' => '4o Eixo Esquerdo Interno', 'sequencia' => 11, 'eixo_numero' => 4, 'lado' => 'ESQUERDO', 'conjunto' => 'INTERNO', 'tipo_posicao' => 'TRACAO', 'aceita_pneu_reserva' => false, 'ativo' => true],
                    ['codigo' => '2TEE', 'nome' => '4o Eixo Esquerdo Externo', 'sequencia' => 12, 'eixo_numero' => 4, 'lado' => 'ESQUERDO', 'conjunto' => 'EXTERNO', 'tipo_posicao' => 'TRACAO', 'aceita_pneu_reserva' => false, 'ativo' => true],
                ],
            ],
            '8X2' => [
                'codigo' => '8X2',
                'nome' => 'Caminhao 8x2',
                'descricao' => 'Mapa padrao de 12 posicoes herdado da estrutura atual.',
                'quantidade_posicoes' => 12,
                'ativo' => true,
                'posicoes' => [
                    ['codigo' => 'DD', 'nome' => 'Dianteiro Direito', 'sequencia' => 1, 'eixo_numero' => 1, 'lado' => 'DIREITO', 'conjunto' => 'SIMPLES', 'tipo_posicao' => 'DIRECIONAL', 'aceita_pneu_reserva' => false, 'ativo' => true],
                    ['codigo' => 'DE', 'nome' => 'Dianteiro Esquerdo', 'sequencia' => 2, 'eixo_numero' => 1, 'lado' => 'ESQUERDO', 'conjunto' => 'SIMPLES', 'tipo_posicao' => 'DIRECIONAL', 'aceita_pneu_reserva' => false, 'ativo' => true],
                    ['codigo' => '2DD', 'nome' => '2o Eixo Direito', 'sequencia' => 3, 'eixo_numero' => 2, 'lado' => 'DIREITO', 'conjunto' => 'SIMPLES', 'tipo_posicao' => 'LIVRE', 'aceita_pneu_reserva' => false, 'ativo' => true],
                    ['codigo' => '2DE', 'nome' => '2o Eixo Esquerdo', 'sequencia' => 4, 'eixo_numero' => 2, 'lado' => 'ESQUERDO', 'conjunto' => 'SIMPLES', 'tipo_posicao' => 'LIVRE', 'aceita_pneu_reserva' => false, 'ativo' => true],
                    ['codigo' => 'TDE', 'nome' => 'Tracao Direito Externo', 'sequencia' => 5, 'eixo_numero' => 3, 'lado' => 'DIREITO', 'conjunto' => 'EXTERNO', 'tipo_posicao' => 'TRACAO', 'aceita_pneu_reserva' => false, 'ativo' => true],
                    ['codigo' => 'TDI', 'nome' => 'Tracao Direito Interno', 'sequencia' => 6, 'eixo_numero' => 3, 'lado' => 'DIREITO', 'conjunto' => 'INTERNO', 'tipo_posicao' => 'TRACAO', 'aceita_pneu_reserva' => false, 'ativo' => true],
                    ['codigo' => 'TEI', 'nome' => 'Tracao Esquerdo Interno', 'sequencia' => 7, 'eixo_numero' => 3, 'lado' => 'ESQUERDO', 'conjunto' => 'INTERNO', 'tipo_posicao' => 'TRACAO', 'aceita_pneu_reserva' => false, 'ativo' => true],
                    ['codigo' => 'TEE', 'nome' => 'Tracao Esquerdo Externo', 'sequencia' => 8, 'eixo_numero' => 3, 'lado' => 'ESQUERDO', 'conjunto' => 'EXTERNO', 'tipo_posicao' => 'TRACAO', 'aceita_pneu_reserva' => false, 'ativo' => true],
                    ['codigo' => '2TDE', 'nome' => '4o Eixo Direito Externo', 'sequencia' => 9, 'eixo_numero' => 4, 'lado' => 'DIREITO', 'conjunto' => 'EXTERNO', 'tipo_posicao' => 'TRACAO', 'aceita_pneu_reserva' => false, 'ativo' => true],
                    ['codigo' => '2TDI', 'nome' => '4o Eixo Direito Interno', 'sequencia' => 10, 'eixo_numero' => 4, 'lado' => 'DIREITO', 'conjunto' => 'INTERNO', 'tipo_posicao' => 'TRACAO', 'aceita_pneu_reserva' => false, 'ativo' => true],
                    ['codigo' => '2TEI', 'nome' => '4o Eixo Esquerdo Interno', 'sequencia' => 11, 'eixo_numero' => 4, 'lado' => 'ESQUERDO', 'conjunto' => 'INTERNO', 'tipo_posicao' => 'TRACAO', 'aceita_pneu_reserva' => false, 'ativo' => true],
                    ['codigo' => '2TEE', 'nome' => '4o Eixo Esquerdo Externo', 'sequencia' => 12, 'eixo_numero' => 4, 'lado' => 'ESQUERDO', 'conjunto' => 'EXTERNO', 'tipo_posicao' => 'TRACAO', 'aceita_pneu_reserva' => false, 'ativo' => true],
                ],
            ],
        ];
    }

    protected function normalizeMapCode(mixed $value): ?string
    {
        $value = strtoupper(trim((string) $value));

        return match ($value) {
            '6X2', '6x2' => '6X2',
            '8X2', '8x2' => '8X2',
            default => $value !== '' ? $value : null,
        };
    }
}
