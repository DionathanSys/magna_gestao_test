<?php

namespace App\Models;

use App\Enum\OrdemServico\PosicaoItemOrdemServicoEnum;
use App\Services\Servico\ServicoCacheService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Servico extends Model
{
    protected $casts = [
        'controla_posicao' => 'boolean',
        'posicoes_permitidas' => 'array',
        'is_active' => 'boolean',
        'garantia_km' => 'integer',
        'garantia_dias' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $servico): void {
            if (! $servico->controla_posicao) {
                $servico->posicoes_permitidas = null;

                return;
            }

            if (blank($servico->posicoes_permitidas)) {
                $servico->posicoes_permitidas = PosicaoItemOrdemServicoEnum::values();
            }
        });

        static::saved(function (self $servico): void {
            ServicoCacheService::forget($servico->id);
        });

        static::deleted(function (self $servico): void {
            ServicoCacheService::forget($servico->id);
        });
    }

    public function garantias(): HasMany
    {
        return $this->hasMany(GarantiaServico::class, 'servico_id');
    }

    public function posicoesPermitidas(): array
    {
        if (! $this->controla_posicao) {
            return [];
        }

        return filled($this->posicoes_permitidas)
            ? array_values(array_intersect($this->posicoes_permitidas, PosicaoItemOrdemServicoEnum::values()))
            : PosicaoItemOrdemServicoEnum::values();
    }

    public function posicoesPermitidasSelectArray(): array
    {
        return collect($this->posicoesPermitidas())
            ->mapWithKeys(fn (string $posicao): array => [$posicao => $posicao])
            ->all();
    }
}
