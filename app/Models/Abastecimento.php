<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Casts\MoneyCastDiesel;
use App\Enum\Abastecimento\TipoCombustivelEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class Abastecimento extends Model
{

    protected $appends = [
        'quilometragem_percorrida',
        'consumo_medio',
        'custo_por_km',
        'is_primeiro_abastecimento',
        'dias_desde_ultimo_abastecimento',
    ];

    protected $casts = [
        'data_abastecimento'    => 'datetime',
        'quilometragem'         => 'integer',
        'litros'                => 'decimal:2',
        'preco_por_litro'       => MoneyCastDiesel::class,
        'preco_total'           => MoneyCast::class,
        'tipo_combustivel'      => TipoCombustivelEnum::class,
    ];

    /**
     * Relação com o modelo Veículo
     *
     * @return BelongsTo
     */
    public function veiculo(): BelongsTo
    {
        return $this->belongsTo(Veiculo::class);
    }

    /**
     * Relação com o modelo ResultadoPeriodo
     *
     * @return BelongsTo
     */
    public function resultadoPeriodo(): BelongsTo
    {
        return $this->belongsTo(ResultadoPeriodo::class);
    }

    /**
     * Scope para buscar o último abastecimento antes ou na data especificada
     *
     * @param Builder $query
     * @param string|Carbon $data
     * @param int|null $veiculoId
     * @return Builder
     */
    public function scopeUltimoAteData(Builder $query, $data, $veiculoId = null): Builder
    {
        $dataFormatted = $data instanceof Carbon ? $data : Carbon::parse($data);
        
        $query = $query->where('data_abastecimento', '<=', $dataFormatted)
                      ->orderBy('data_abastecimento', 'desc')
                      ->orderBy('created_at', 'desc');
        
        if ($veiculoId) {
            $query->where('veiculo_id', $veiculoId);
        }
        
        return $query;
    }

    /**
     * Scope para buscar abastecimentos anteriores a uma data
     *
     * @param Builder $query
     * @param string|Carbon $data
     * @return Builder
     */
    public function scopeAnterioresAData(Builder $query, $data, $veiculoId = null): Builder
    {
        $dataFormatted = $data instanceof Carbon ? $data : Carbon::parse($data);

        $query = $query->where('data_abastecimento', '<', $dataFormatted);

        if ($veiculoId) {
            $query->where('veiculo_id', $veiculoId);
        }

        return $query;
    }

    /**
     * Método estático para obter o último abastecimento de um veículo até uma data específica
     *
     * @param int $veiculoId
     * @param string|Carbon $data
     * @return Abastecimento|null
     */
    public static function ultimoAbastecimentoAteData($veiculoId, $data): ?self
    {
        return static::ultimoAteData($data, $veiculoId)->first();
    }

    /**
     * Método estático para obter a quilometragem do último abastecimento antes de uma data
     * Útil para calcular a quilometragem percorrida
     *
     * @param int $veiculoId
     * @param string|Carbon $data
     * @return int|null
     */
    public static function quilometragemUltimoAbastecimentoAteData($veiculoId, $data): ?int
    {
        $ultimoAbastecimento = static::ultimoAbastecimentoAteData($veiculoId, $data);
        
        return $ultimoAbastecimento?->quilometragem;
    }

    /**
     * Accessor para obter o último abastecimento anterior deste veículo
     * Baseado na data de abastecimento do registro atual
     *
     * @return Attribute
     */
    protected function ultimoAbastecimentoAnterior(): Attribute
    {
        return Attribute::make(
            get: fn () => static::where('veiculo_id', $this->veiculo_id)
                ->where('data_abastecimento', '<', $this->data_abastecimento)
                ->where('considerar_calculo_medio', true)
                ->orderBy('data_abastecimento', 'desc')
                ->first()
        );
    }

    /**
     * Accessor para calcular a quilometragem percorrida desde o último abastecimento
     *
     * @return Attribute
     */
    protected function quilometragemPercorrida(): Attribute
    {
        return Attribute::make(
            get: function () {
                $ultimoAbastecimento = $this->ultimo_abastecimento_anterior;
                
                if (!$ultimoAbastecimento) {
                    return 0;
                }
                
                return $this->quilometragem - $ultimoAbastecimento->quilometragem;
            }
        );
    }

    /**
     * Accessor para calcular o consumo médio (km/L)
     *
     * @return Attribute
     */
    protected function consumoMedio(): Attribute
    {
        return Attribute::make(
            get: function () {
                $quilometragemPercorrida = $this->quilometragem_percorrida;

                if (!$quilometragemPercorrida || $this->quantidade <= 0) {
                    return null;
                }
                
                return round($quilometragemPercorrida / $this->quantidade, 4);
            }
        );
    }

    /**
     * Accessor para calcular o custo por quilômetro
     *
     * @return Attribute
     */
    protected function custoPorKm(): Attribute
    {
        return Attribute::make(
            get: function () {
                $quilometragemPercorrida = $this->quilometragem_percorrida;
                
                if (!$quilometragemPercorrida || $quilometragemPercorrida <= 0) {
                    return null;
                }
                
                return round($this->preco_total / $quilometragemPercorrida, 4);
            }
        );
    }

    /**
     * Accessor para verificar se é o primeiro abastecimento do veículo
     *
     * @return Attribute
     */
    protected function isPrimeiroAbastecimento(): Attribute
    {
        return Attribute::make(
            get: fn () => is_null($this->ultimo_abastecimento_anterior)
        );
    }

    /**
     * Accessor para obter dias desde o último abastecimento
     *
     * @return Attribute
     */
    protected function diasDesdeUltimoAbastecimento(): Attribute
    {
        return Attribute::make(
            get: function () {
                $ultimoAbastecimento = $this->ultimo_abastecimento_anterior;
                
                if (!$ultimoAbastecimento) {
                    return null;
                }
                
                return $this->data_abastecimento->diffInDays($ultimoAbastecimento->data_abastecimento);
            }
        );
    }

}
