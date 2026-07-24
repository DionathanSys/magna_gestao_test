<?php

namespace App\Services\Servico;

use App\Enum\OrdemServico\PosicaoItemOrdemServicoEnum;
use App\Models\Servico;
use Illuminate\Support\Facades\Cache;

class ServicoCacheService
{
    private const CACHE_KEY_SERVICOS = 'servicos.select.items';

    private const CACHE_KEY_POSICOES = 'servicos.posicoes.select.all';

    private const CACHE_KEY_POSICOES_PREFIX = 'servicos.posicoes.select.';

    private const CACHE_TTL = 43200;

    public static function getServicos(): array
    {
        return Cache::remember(self::CACHE_KEY_SERVICOS, self::CACHE_TTL, fn (): array => Servico::query()
            ->select('id', 'codigo', 'descricao', 'controla_posicao', 'posicoes_permitidas', 'is_active')
            ->orderBy('descricao')
            ->get()
            ->mapWithKeys(fn (Servico $servico): array => [$servico->id => [
                'id' => $servico->id,
                'codigo' => $servico->codigo,
                'descricao' => $servico->descricao,
                'controla_posicao' => (bool) $servico->controla_posicao,
                'posicoes_permitidas' => $servico->posicoes_permitidas,
                'is_active' => (bool) $servico->is_active,
                'label' => self::formatServicoLabel($servico),
                'search' => mb_strtolower(trim($servico->codigo.' '.$servico->descricao)),
            ]])
            ->all());
    }

    public static function getServico(int|string|null $servicoId): ?array
    {
        if (! $servicoId) {
            return null;
        }

        return self::getServicos()[(int) $servicoId] ?? null;
    }

    public static function getServicosForSelect(): array
    {
        return collect(self::getServicos())
            ->mapWithKeys(fn (array $servico): array => [$servico['id'] => $servico['label']])
            ->all();
    }

    public static function searchServicosForSelect(string $search, int $limit = 10): array
    {
        $terms = collect(explode(' ', mb_strtolower($search)))
            ->filter()
            ->values();

        return collect(self::getServicos())
            ->filter(fn (array $servico): bool => $terms->every(
                fn (string $term): bool => str_contains($servico['search'], $term)
            ))
            ->take($limit)
            ->mapWithKeys(fn (array $servico): array => [$servico['id'] => $servico['label']])
            ->all();
    }

    public static function getServicoLabel(int|string|null $servicoId): ?string
    {
        return self::getServico($servicoId)['label'] ?? null;
    }

    public static function controlaPosicao(int|string|null $servicoId): bool
    {
        return (bool) (self::getServico($servicoId)['controla_posicao'] ?? false);
    }

    public static function getPosicoesForSelect(int|string|null $servicoId = null): array
    {
        if (! $servicoId) {
            return Cache::remember(self::CACHE_KEY_POSICOES, self::CACHE_TTL, fn (): array => PosicaoItemOrdemServicoEnum::toSelectArray());
        }

        return Cache::remember(self::CACHE_KEY_POSICOES_PREFIX.(int) $servicoId, self::CACHE_TTL, function () use ($servicoId): array {
            $servico = self::getServico($servicoId);

            if (! $servico || ! $servico['controla_posicao']) {
                return [];
            }

            $posicoes = filled($servico['posicoes_permitidas'])
                ? array_values(array_intersect($servico['posicoes_permitidas'], PosicaoItemOrdemServicoEnum::values()))
                : PosicaoItemOrdemServicoEnum::values();

            return collect($posicoes)
                ->mapWithKeys(fn (string $posicao): array => [$posicao => $posicao])
                ->all();
        });
    }

    public static function forget(int|string|null $servicoId = null): void
    {
        Cache::forget(self::CACHE_KEY_SERVICOS);

        if ($servicoId) {
            Cache::forget(self::CACHE_KEY_POSICOES_PREFIX.(int) $servicoId);

            return;
        }

        Cache::forget(self::CACHE_KEY_POSICOES);

        foreach (Servico::query()->pluck('id') as $servicoId) {
            Cache::forget(self::CACHE_KEY_POSICOES_PREFIX.$servicoId);
        }
    }

    protected static function formatServicoLabel(Servico $servico): string
    {
        return trim($servico->codigo.' - '.$servico->descricao, ' -');
    }
}
