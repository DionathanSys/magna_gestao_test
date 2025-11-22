<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Number;

class ResultadoPeriodo extends Model
{

    protected $appends = [
        'periodo',
        'km_rodado_abastecimento',
        'km_pago',
        'dispersao_km',
        'dispersao_km_abastecimento_km_viagem',
        'quantidade_viagens',
        'media_km_pago_viagem',
        'resultado_liquido',
        'faturamento_por_km_rodado',
        'faturamento_por_km_pago',
        'percentual_manutencao_faturamento',
    ];

    public function veiculo(): BelongsTo
    {
        return $this->belongsTo(Veiculo::class);
    }

    public function tipoVeiculo(): BelongsTo
    {
        return $this->belongsTo(TipoVeiculo::class, 'tipo_veiculo_id');
    }

    public function abastecimentos(): HasMany
    {
        return $this->hasMany(Abastecimento::class);
    }

    public function viagens(): HasMany
    {
        return $this->hasMany(Viagem::class);
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(DocumentoFrete::class);
    }

    public function manutencao(): HasOne
    {
        return $this->hasOne(ManutencaoCusto::class);
    }

    public function abastecimentoInicial(): HasOne
    {
        return $this->hasOne(Abastecimento::class)
            ->ofMany([
                'data_abastecimento' => 'min',
                'id' => 'min', // Desempate se houver mesma data
            ], function ($query) {
                // Adiciona filtros extras se necessário
                $query->whereNotNull('data_abastecimento');
            });
    }

    public function abastecimentoFinal(): HasOne
    {
        return $this->hasOne(Abastecimento::class)
            ->whereNotNull('data_abastecimento')
            ->orderBy('data_abastecimento', 'desc')
            ->orderBy('id', 'desc');
    }

    protected function periodo(): Attribute
    {
        return Attribute::make(
            get: fn(): string => Carbon::parse($this->data_inicio)->format('d/m/Y') . ' à ' . Carbon::parse($this->data_fim)->format('d/m/Y')
        );
    }

    protected function kmPago(): Attribute
    {
        return Attribute::make(
            get: fn(): float => (float) ($this->viagens_sum_km_pago ?? 0)
        );
    }

    protected function kmRodadoViagens(): Attribute
    {
        return Attribute::make(
            get: fn(): int => ($this->viagens_sum_km_rodado ?? 0)
        );
    }

    protected function kmRodadoAbastecimento(): Attribute
    {
        return Attribute::make(
            get: function (): int {
                $kmFinal = $this->abastecimentoFinal?->quilometragem ?? 0;
                $kmInicial = $this->abastecimentoInicial?->ultimo_abastecimento_anterior?->quilometragem ?? 0;
                return $kmFinal - $kmInicial;
            }
        );
    }

    protected function dispersaoKm(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->km_rodado_abastecimento - ($this->km_pago ?? 0)
        );
    }

    protected function dispersaoKmAbastecimentoKmViagem(): Attribute
    {
        return Attribute::make(
            get: fn(): int => ($this->km_rodado_viagens ?? 0) - ($this->km_rodado_abastecimento ?? 0)
        );
    }

    protected function quantidadeViagens(): Attribute
    {
        return Attribute::make(
            get: fn(): int => $this->viagens_count ?? 0
        );
    }

    protected function mediaKmPagoViagem(): Attribute
    {
        return Attribute::make(
            get: fn(): string => $this->quantidade_viagens > 0 ? number_format($this->km_pago / $this->quantidade_viagens, 2, ',', '.') . " Km/Viagem": "0"
        );
    }

     /**
     * ⭐ Accessor: Resultado Líquido
     * Faturamento - Combustível - Manutenção
     */
    protected function resultadoLiquido(): Attribute
    {
        return Attribute::make(
            get: function (): float {
                $faturamento = $this->documentos_sum_valor_liquido ?? 0;
                $combustivel = $this->abastecimentos_sum_preco_total ?? 0;
                $manutencao = $this->manutencao_sum_custo_total ?? 0;
                
                return $faturamento - $combustivel - $manutencao;
            }
        );
    }

    /**
     * ⭐ Accessor: Faturamento por KM Rodado
     * Faturamento / KM Rodado (baseado em abastecimentos)
     */
    protected function faturamentoPorKmRodado(): Attribute
    {
        return Attribute::make(
            get: function (): float {
                $faturamento = $this->documentos_sum_valor_liquido ?? 0;
                $kmRodado = $this->km_rodado_abastecimento;
                
                if ($kmRodado <= 0) {
                    return 0;
                }
                
                return $faturamento / $kmRodado;
            }
        );
    }

    /**
     * ⭐ Accessor: Faturamento por KM Pago
     * Faturamento / KM Pago (baseado em viagens)
     */
    protected function faturamentoPorKmPago(): Attribute
    {
        return Attribute::make(
            get: function (): float {
                $faturamento = $this->documentos_sum_valor_liquido ?? 0;
                $kmPago = $this->km_pago;
                
                if ($kmPago <= 0) {
                    return 0;
                }
                
                return $faturamento / $kmPago;
            }
        );
    }

    /**
     * ⭐ Accessor: Percentual de Manutenção sobre Faturamento
     * (Manutenção / Faturamento) * 100
     */
    protected function percentualManutencaoFaturamento(): Attribute
    {
        return Attribute::make(
            get: function (): float {
                $faturamento = $this->documentos_sum_valor_liquido ?? 0;
                $manutencao = $this->manutencao_sum_custo_total ?? 0;
                
                if ($faturamento <= 0) {
                    return 0;
                }
                Log::debug('Cálculo do percentual de manutenção sobre faturamento', [
                    'faturamento' => $faturamento / 100,
                    'manutencao' => $manutencao,
                    'calculo' => ($manutencao / $faturamento) * 100,
                ]);
                return ($manutencao / ($faturamento / 100)) * 100;
            }
        );
    }
}
