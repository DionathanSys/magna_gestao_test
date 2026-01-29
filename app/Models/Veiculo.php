<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, BelongsToMany, HasMany, HasManyThrough, HasOne};
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\SoftDeletes;

class Veiculo extends Model
{

    use SoftDeletes;

    protected $casts = [
        'is_active' => 'boolean',
        'informacoes_complementares' => 'array',
    ];

    protected $appends = ['quilometragem_atual'];

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

    public function manutencoes(): HasMany
    {
        return $this->hasMany(OrdemServico::class, 'veiculo_id');
    }

    public function itens(): HasManyThrough
    {
        return $this->hasManyThrough(
            ItemOrdemServico::class,
            OrdemServico::class,
            'veiculo_id',
            'ordem_servico_id',
            'id',
            'id'
        );
    }

    public function tipoVeiculo(): BelongsTo
    {
        return $this->belongsTo(TipoVeiculo::class, 'tipo_veiculo_id');
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

    /**
     * Calcula a quilometragem média diária do veículo
     * com base no histórico de quilometragem
     * 
     * @param int $dias Número de dias a considerar (padrão: 30)
     * @return float Quilometragem média por dia
     */
    public function calcularKmMedioDiario(int $dias = 30): float
    {
        $dataInicio = now()->subDays($dias);
        
        $registros = HistoricoQuilometragem::query()
            ->where('veiculo_id', $this->id)
            ->where('data_referencia', '>=', $dataInicio)
            ->orderBy('data_referencia')
            ->get();

        if ($registros->count() < 2) {
            // Se não houver registros suficientes, tenta calcular com todos disponíveis
            $registros = HistoricoQuilometragem::query()
                ->where('veiculo_id', $this->id)
                ->orderBy('data_referencia')
                ->get();
        }

        if ($registros->count() < 2) {
            return 0; // Não há dados suficientes
        }

        $primeiro = $registros->first();
        $ultimo = $registros->last();

        $diferencaKm = $ultimo->quilometragem - $primeiro->quilometragem;
        $diferencaDias = $primeiro->data_referencia->diffInDays($ultimo->data_referencia);

        if ($diferencaDias == 0) {
            return 0;
        }

        return round($diferencaKm / $diferencaDias, 2);
    }

    /**
     * Calcula a data prevista baseada em km restante e km médio diário
     * 
     * @param float $kmRestante Quilometragem restante até a próxima manutenção
     * @return \Carbon\Carbon|null Data prevista ou null se não for possível calcular
     */
    public function calcularDataPrevista(float $kmRestante): ?\Carbon\Carbon
    {
        $kmMedio = $this->calcularKmMedioDiario();

        if ($kmMedio <= 0) {
            return null; // Não é possível calcular sem km médio
        }

        $diasRestantes = ceil($kmRestante / $kmMedio);

        return now()->addDays($diasRestantes);
    }


}
