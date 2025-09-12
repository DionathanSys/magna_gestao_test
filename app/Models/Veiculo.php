<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsToMany, HasMany, HasOne};
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\SoftDeletes;

class Veiculo extends Model
{

    use SoftDeletes;

    protected $casts = [
        'is_active' => 'boolean',
        'informacoes_complementares' => 'array',
    ];

    // Descomente se quiser sempre no array/json
    // protected $appends = ['km_atual'];

    public function pneus(): HasMany
    {
        return $this->hasMany(PneuPosicaoVeiculo::class);
    }

    public function kmAtual(): HasOne
    {
        return $this->hasOne(HistoricoQuilometragem::class)->latestOfMany();
    }

    public function planoPreventivo(): BelongsToMany
    {
        return $this->belongsToMany(PlanoPreventivo::class, 'planos_manutencao_veiculo', 'veiculo_id', 'plano_preventivo_id');
    }

    /**
     * Accessor: retorna a quilometragem atual (numero) via relação kmAtual().
     * Acesso: $veiculo->quilometragem_atual
     */
    protected function quilometragemAtual(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->relationLoaded('kmAtual')) {
                    return $this->getRelation('kmAtual')?->quilometragem ?? 0;
                }
                return (float) ($this->kmAtual()->value('quilometragem') ?? 0);
            }
        );
    }


}
