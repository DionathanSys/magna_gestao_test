<?php

namespace App\Models;

use App\Casts\MoneyCast;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ResultadoPeriodo extends Model
{
    protected $casts = [
        'folha_pagamento_centavos' => MoneyCast::class,
    ];

    protected $appends = [
        'periodo',
        'km_rodado_abastecimento',
        'km_pago',
        'dispersao_km',
        'dispersao_km_real',
        'dispersao_km_abastecimento_km_viagem',
        'percentual_dispersao_km_abastecimento',
        'percentual_dispersao_km_real',
        'margem_liquida',
        'custo_por_km',
        'quantidade_viagens',
        'media_km_pago_viagem',
        'resultado_liquido',
        'faturamento_por_km_rodado',
        'faturamento_por_km_pago',
        'percentual_manutencao_faturamento',
        'diferenca_meta_consumo',
        'meta_consumo',
        'litros_estimados_meta',
        'desperdicio_litros',
        'desperdicio_valor',
        'variacao_faturamento_mes_anterior',
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

    /**
     * ✅ Método necessário para DissociateBulkAction funcionar
     * Retorna o nome do relacionamento inverso em Abastecimento
     */
    public function getInverseRelationshipFor(Model $record): string
    {
        return 'resultadoPeriodo';
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

    public function manutencaoLancamentos(): HasMany
    {
        return $this->hasMany(ManutencaoLancamento::class);
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

    protected function title(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->veiculo->placa.' '.ucfirst(Carbon::parse($this->data_fim)->locale('pt_BR')->isoFormat('MMM YY'))
        );
    }

    protected function periodo(): Attribute
    {
        return Attribute::make(
            get: fn (): string => Carbon::parse($this->data_inicio)->format('d/m/Y').' à '.Carbon::parse($this->data_fim)->format('d/m/Y')
        );
    }

    // Período do mês anterior do mesmo veículo
    public function periodoMesAnterior(): HasOne
    {
        return $this->hasOne(ResultadoPeriodo::class, 'veiculo_id', 'veiculo_id')
            ->where('id', '!=', $this->id) // Não pegar o próprio registro
            ->where('data_fim', '<', $this->data_inicio) // Anterior a este período
            ->orderBy('data_fim', 'desc') // Pegar o mais recente
            ->limit(1);
    }

    /**
     *Accessor: Variação do Faturamento em relação ao mês anterior
     * Compara o faturamento atual com o do período anterior do mesmo veículo
     */
    protected function variacaoFaturamentoMesAnterior(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                // Faturamento do período atual
                $faturamentoAtual = $this->documentos_sum_valor_liquido ?? 0;

                if ($faturamentoAtual <= 0) {
                    return null;
                }

                // Busca o período anterior do mesmo veículo
                $periodoAnterior = static::query()
                    ->where('veiculo_id', $this->veiculo_id)
                    ->where('id', '!=', $this->id)
                    ->where('data_fim', '<', $this->data_inicio)
                    ->withSum('documentos', 'valor_liquido')
                    ->orderBy('data_fim', 'desc')
                    ->first();

                // Se não houver período anterior
                if (! $periodoAnterior) {
                    return null;
                }

                $faturamentoAnterior = $periodoAnterior->documentos_sum_valor_liquido ?? 0;

                // Se não houver faturamento no período anterior
                if ($faturamentoAnterior <= 0) {
                    return '+100%'; // Aumentou infinito (de 0 para algo)
                }

                // Calcula a variação percentual
                $variacao = (($faturamentoAtual - $faturamentoAnterior) / $faturamentoAnterior) * 100;

                // Formata a mensagem
                if ($variacao > 0) {
                    return sprintf('+%.1f%%', $variacao);
                } elseif ($variacao < 0) {
                    return sprintf('%.1f%%', $variacao); // Já tem o sinal negativo
                } else {
                    return '0%';
                }
            }
        );
    }

    protected function kmPago(): Attribute
    {
        return Attribute::make(
            get: fn (): float => (float) ($this->viagens_sum_km_pago ?? 0)
        );
    }

    protected function kmRodadoViagens(): Attribute
    {
        return Attribute::make(
            get: fn (): int => ($this->viagens_sum_km_rodado ?? 0)
        );
    }

    protected function kmRodadoAbastecimento(): Attribute
    {
        return Attribute::make(
            get: function (): ?int {
                $kmFinal = $this->abastecimentoFinal?->quilometragem;
                $kmInicial = $this->abastecimentoInicial?->ultimo_abastecimento_anterior?->quilometragem;

                return $kmFinal === null || $kmInicial === null ? null : max(0, $kmFinal - $kmInicial);
            }
        );
    }

    protected function quantidadeLitrosCombustivel(): Attribute
    {
        return Attribute::make(
            get: fn (): float => (float) ($this->abastecimentos_sum_quantidade ?? $this->abastecimentos->sum('quantidade'))
        );
    }

    protected function precoMedioCombustivel(): Attribute
    {
        return Attribute::make(
            get: fn (): float => $this->quantidade_litros_combustivel > 0
                ? round((($this->abastecimentos_sum_preco_total ?? $this->abastecimentos->sum('preco_total') * 100) / 100) / $this->quantidade_litros_combustivel, 4)
                : 0
        );
    }

    protected function consumoMedioCombustivel(): Attribute
    {
        return Attribute::make(
            get: fn (): ?float => $this->quantidade_litros_combustivel > 0 && $this->km_rodado_abastecimento !== null
                ? round($this->km_rodado_abastecimento / $this->quantidade_litros_combustivel, 2)
                : null
        );
    }

    protected function metaConsumo(): Attribute
    {
        return Attribute::make(
            get: function (): ?float {
                $meta = (float) ($this->veiculo?->tipoVeiculo?->meta_media ?? 0);

                return $meta > 0 ? $meta : null;
            }
        );
    }

    protected function litrosEstimadosMeta(): Attribute
    {
        return Attribute::make(
            get: function (): ?float {
                if ($this->meta_consumo === null || $this->km_rodado_abastecimento === null) {
                    return null;
                }

                return round($this->km_rodado_abastecimento / $this->meta_consumo, 2);
            }
        );
    }

    protected function desperdicioLitros(): Attribute
    {
        return Attribute::make(
            get: fn (): ?float => $this->litros_estimados_meta === null
                ? null
                : round($this->quantidade_litros_combustivel - $this->litros_estimados_meta, 2)
        );
    }

    protected function desperdicioValor(): Attribute
    {
        return Attribute::make(
            get: fn (): ?float => $this->desperdicio_litros === null || $this->preco_medio_combustivel <= 0
                ? null
                : round($this->desperdicio_litros * $this->preco_medio_combustivel, 2)
        );
    }

    /**
     *Accessor: Diferença entre consumo real e meta
     * Retorna quanto ficou acima (+) ou abaixo (-) da meta
     */
    protected function diferencaMetaConsumo(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                // Pega a meta do tipo de veículo
                $meta = $this->veiculo?->tipoVeiculo?->meta_media;

                // Se não houver meta
                if (! $meta || $meta <= 0) {
                    return 'Sem Meta';
                }

                // Consumo real do período
                $consumoReal = $this->consumo_medio_combustivel;

                // Se não houver consumo
                if (! $consumoReal || $consumoReal <= 0) {
                    return 'Sem Consumo';
                }

                // Calcula a diferença (positivo = acima da meta, negativo = abaixo)
                $diferenca = $consumoReal - $meta;

                // Calcula o percentual
                $percentual = ($diferenca / $meta) * 100;

                // Formata a mensagem
                if ($diferenca > 0) {
                    return sprintf(
                        '+%.2f km/L (+%.1f%%)',
                        abs($diferenca),
                        abs($percentual)
                    );
                } elseif ($diferenca < 0) {
                    return sprintf(
                        '-%.2f km/L (-%.1f%%)',
                        abs($diferenca),
                        abs($percentual)
                    );
                } else {
                    // Exatamente na meta
                    return '';
                }
            }
        );
    }

    protected function dispersaoKm(): Attribute
    {
        return Attribute::make(
            get: fn (): ?float => $this->km_rodado_abastecimento === null ? null : $this->km_rodado_abastecimento - $this->km_pago
        );
    }

    protected function dispersaoKmReal(): Attribute
    {
        return Attribute::make(
            get: fn (): int => $this->km_rodado_viagens - $this->km_pago
        );
    }

    protected function dispersaoKmAbastecimentoKmViagem(): Attribute
    {
        return Attribute::make(
            get: fn (): ?float => $this->km_rodado_abastecimento === null ? null : $this->km_rodado_viagens - $this->km_rodado_abastecimento
        );
    }

    protected function percentualDispersaoKmAbastecimento(): Attribute
    {
        return Attribute::make(
            get: fn (): ?float => $this->km_pago > 0 && $this->dispersao_km !== null
                ? ($this->dispersao_km / $this->km_pago) * 100
                : null
        );
    }

    protected function percentualDispersaoKmReal(): Attribute
    {
        return Attribute::make(
            get: fn (): ?float => $this->km_pago > 0 ? ($this->dispersao_km_real / $this->km_pago) * 100 : null
        );
    }

    protected function quantidadeViagens(): Attribute
    {
        return Attribute::make(
            get: fn (): int => $this->viagens_count ?? 0
        );
    }

    protected function mediaKmPagoViagem(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->quantidade_viagens > 0 ? number_format($this->km_pago / $this->quantidade_viagens, 2, ',', '.').' Km/Viagem' : '0'
        );
    }

    /**
     * Accessor: Resultado Líquido
     * Faturamento - Combustível - Manutenção - Folha
     */
    protected function resultadoLiquido(): Attribute
    {
        return Attribute::make(
            get: function (): float {
                $faturamento = $this->documentos_sum_valor_liquido ?? 0;
                $combustivel = $this->abastecimentos_sum_preco_total ?? 0;
                $manutencao = $this->manutencao_lancamentos_sum_valor_total_centavos ?? 0;
                $folhaPagamento = (int) ($this->getRawOriginal('folha_pagamento_centavos') ?? 0);

                return $faturamento - $combustivel - $manutencao - $folhaPagamento;
            }
        );
    }

    /**
     *Accessor: Faturamento por KM Rodado
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
     *Accessor: Faturamento por KM Pago
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

    protected function margemLiquida(): Attribute
    {
        return Attribute::make(
            get: fn (): ?float => ($this->documentos_sum_valor_liquido ?? 0) > 0
                ? ($this->resultado_liquido / $this->documentos_sum_valor_liquido) * 100
                : null
        );
    }

    protected function custoPorKm(): Attribute
    {
        return Attribute::make(
            get: function (): ?float {
                if ($this->km_rodado_abastecimento === null || $this->km_rodado_abastecimento <= 0) {
                    return null;
                }

                $custos = ($this->abastecimentos_sum_preco_total ?? 0)
                    + ($this->manutencao_lancamentos_sum_valor_total_centavos ?? 0);

                return ($custos / 100 + (float) $this->folha_pagamento_centavos) / $this->km_rodado_abastecimento;
            }
        );
    }

    /**
     *Accessor: Percentual de Manutenção sobre Faturamento
     * (Manutenção / Faturamento) * 100
     */
    protected function percentualManutencaoFaturamento(): Attribute
    {
        return Attribute::make(
            get: function (): float {
                $faturamento = $this->documentos_sum_valor_liquido ?? 0;
                $manutencao = $this->manutencao_lancamentos_sum_valor_total_centavos ?? 0;

                if ($faturamento <= 0) {
                    return 0;
                }

                return ($manutencao / $faturamento) * 100;
            }
        );
    }
}
