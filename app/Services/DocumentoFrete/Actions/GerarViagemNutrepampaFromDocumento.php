<?php

namespace App\Services\DocumentoFrete\Actions;

use App\Enum\MotivoDivergenciaViagem;
use App\Models;
use App\Services\Veiculo\VeiculoService;
use App\Services\Viagem\ViagemService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Log;

class GerarViagemNutrepampaFromDocumento
{
    protected array $data = [];
    protected array $errors = [];
    protected array $viagensCriadas = [];

    private const VIAGEM_ATE_120_KM     = 5.65;
    private const VIAGEM_ATE_500_KM     = 5.21;
    private const VIAGEM_ATE_1000_KM    = 4.73;
    private const VIAGEM_ACIMA_1000_KM  = 4.58;
    private const TOLERANCIA_PERCENTUAL = 0.01;

    public function __construct(
        protected Collection|SupportCollection|null $documentosFrete = null
    ) {
        $this->documentosFrete = $documentosFrete ?? new Collection();
    }

    public function handle()
    {
        $this->processarDocumentosFrete();

        $actionViagem = new ViagemService();

        foreach ($this->data as $key => $dados) {
            try {
                
                $viagem = $actionViagem->create($dados);

                if ($viagem) {
                    $this->viagensCriadas[] = $viagem;
                    Log::info('Viagem Nutrepampa criada a partir de documentos de frete. ID da Viagem: ' . $viagem->id, [
                        'metodo' => __METHOD__ . '@' . __LINE__,
                        'dados_viagem' => $dados,
                    ]);

                    Models\DocumentoFrete::whereIn('id', $dados['documentos_frete_ids'])
                        ->update([
                            'viagem_id'             => $viagem->id,
                            'documento_transporte'  => $viagem->documento_transporte,
                        ]);

                } else {
                    Log::warning('Falha ao criar viagem Nutrepampa a partir de documentos de frete.', [
                        'metodo' => __METHOD__ . '@' . __LINE__,
                        'dados_viagem' => $dados,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Erro ao criar viagem Nutrepampa a partir de documentos de frete.', [
                    'metodo' => __METHOD__ . '@' . __LINE__,
                    'error' => $e->getMessage(),
                    'dados_viagem' => $dados,
                ]);
            }
        }
        return $this->viagensCriadas;
    }

    /**
     * Adiciona novos documentos de frete a uma viagem existente e recalcula o km_pago
     * 
     * @param Models\Viagem $viagem
     * @param Collection|array $novosDocumentosIds - IDs dos novos documentos de frete
     * @return bool
     */
    public function adicionarDocumentosViagem(Models\Viagem $viagem, Collection|array $novosDocumentosIds): bool
    {
        try {
            // Converte array para Collection se necessário
            if (is_array($novosDocumentosIds)) {
                $novosDocumentosIds = collect($novosDocumentosIds);
            }

            // Busca os novos documentos
            $novosDocumentos = Models\DocumentoFrete::whereIn('id', $novosDocumentosIds)->get();

            if ($novosDocumentos->isEmpty()) {
                Log::warning('Nenhum documento de frete válido encontrado para adicionar à viagem.', [
                    'metodo' => __METHOD__ . '@' . __LINE__,
                    'viagem_id' => $viagem->id,
                    'documentos_ids' => $novosDocumentosIds,
                ]);
                return false;
            }

            // Carrega os documentos já vinculados à viagem
            $documentosExistentes = $viagem->documentos()->get();

            // Calcula o valor total atual (documentos existentes)
            $valorTotalExistente = $documentosExistentes->sum('valor_liquido');

            // Calcula o valor total dos novos documentos
            $valorTotalNovos = $novosDocumentos->sum('valor_liquido');

            // Calcula o novo valor total
            $novoValorTotal = $valorTotalExistente + $valorTotalNovos;

            // Recalcula o km_pago com base no novo valor total
            $calculoKmPago = $this->calcularKmPago($novoValorTotal);

            // Atualiza a viagem com o novo km_pago
            $viagem->update([
                'km_pago' => $calculoKmPago['km_pago'],
                'valor_total_documento' => $novoValorTotal,
            ]);

            // Vincula os novos documentos à viagem
            Models\DocumentoFrete::whereIn('id', $novosDocumentosIds)
                ->update([
                    'viagem_id' => $viagem->id,
                    'documento_transporte' => $viagem->documento_transporte,
                ]);

            Log::info('Documentos de frete adicionados à viagem com sucesso. KM Pago recalculado.', [
                'metodo' => __METHOD__ . '@' . __LINE__,
                'viagem_id' => $viagem->id,
                'documentos_ids' => $novosDocumentosIds,
                'valor_total_anterior' => $valorTotalExistente,
                'valor_total_novos' => $valorTotalNovos,
                'novo_valor_total' => $novoValorTotal,
                'km_pago_anterior' => $viagem->getOriginal('km_pago'),
                'km_pago_novo' => $calculoKmPago['km_pago'],
                'faixa_calculada' => $calculoKmPago['faixa'],
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Erro ao adicionar documentos de frete à viagem.', [
                'metodo' => __METHOD__ . '@' . __LINE__,
                'viagem_id' => $viagem->id,
                'documentos_ids' => $novosDocumentosIds,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    private function processarDocumentosFrete(): void
    {
        $documentosGroupVeiculoId = $this->documentosFrete->groupBy(['veiculo_id', function (Models\DocumentoFrete $documento) {
            return $documento->data_emissao;
        }]);

        $documentosGroupVeiculoId->each(function (Collection $documentosGroupDataEmissao, $veiculoId) {

            $documentosGroupDataEmissao->each(function (Collection $documentosDataEmissao, $dataEmissao) use ($veiculoId) {

                $documentosDataEmissao->groupBy('valor_total')->each(function (Collection $documentosGroupValorTotal, $valorTotal) use ($veiculoId, $dataEmissao) {

                    $documentosIds = $documentosGroupValorTotal->pluck('id')->toArray();
                    $valorTotalDocumentos = $documentosGroupValorTotal->sum('valor_liquido');
                    $veiculo = Models\Veiculo::find($veiculoId);
                    $calculoKmPago = $this->calcularKmPago($valorTotalDocumentos);

                    $this->data[] = [
                        'veiculo_id'            => $veiculo->id,
                        'unidade_negocio'       => $veiculo->filial,
                        'cliente'               => $veiculo->informacoes_complementares['cliente'],
                        'numero_viagem'         => 'NP-'.$documentosGroupValorTotal->first()->documento_transporte,
                        'documento_transporte'  => $documentosGroupValorTotal->first()->documento_transporte,
                        'km_pago'               => $calculoKmPago['km_pago'],
                        'km_cadastro'           => 0,
                        'km_rodado'             => 0,
                        'km_cobrar'             => 0,
                        'data_competencia'      => $dataEmissao,
                        'data_inicio'           => $dataEmissao,
                        'data_fim'              => $dataEmissao,
                        'conferido'             => false,
                        'motivo_divergencia'    => MotivoDivergenciaViagem::SEM_OBS->value,
                        'valor_total_documento' => $valorTotalDocumentos,
                        'documentos_frete_ids'  => $documentosIds,
                    ];
                });
            });
        });

        Log::debug('Dados agrupados para criação de viagens Nutrepampa a partir dos documentos de frete.', [
            'metodo' => __METHOD__ . '@' . __LINE__,
            'data' => $this->data,
        ]);
    }

    /**
     * Recalcula o km_pago de uma viagem existente baseado nos documentos vinculados
     * 
     * @param Models\Viagem $viagem
     * @return bool
     */
    public function recalcularKmPagoViagem(Models\Viagem $viagem): bool
    {
        try {
            // Carrega todos os documentos vinculados à viagem
            $documentos = $viagem->documentos()->get();

            Log::debug('Iniciando recalculo de km_pago para a viagem.', [
                'metodo' => __METHOD__ . '@' . __LINE__,
                'viagem_id' => $viagem->id,
                'quantidade_documentos' => $documentos->count(),
            ]);

            if ($documentos->isEmpty()) {
                Log::warning('Viagem sem documentos de frete vinculados para recalcular km_pago.', [
                    'metodo' => __METHOD__ . '@' . __LINE__,
                    'viagem_id' => $viagem->id,
                ]);
                return false;
            }

            // Calcula o valor total dos documentos (usando valor_liquido)
            $valorTotal = $documentos->sum('valor_liquido');

            // Recalcula o km_pago
            $calculoKmPago = $this->calcularKmPago($valorTotal);

            // Atualiza a viagem
            $viagem->update([
                'km_pago' => $calculoKmPago['km_pago'],
                'valor_total_documento' => $valorTotal,
            ]);

            Log::info('KM Pago da viagem recalculado com sucesso.', [
                'metodo' => __METHOD__ . '@' . __LINE__,
                'viagem_id' => $viagem->id,
                'quantidade_documentos' => $documentos->count(),
                'valor_total' => $valorTotal,
                'km_pago_anterior' => $viagem->getOriginal('km_pago'),
                'km_pago_novo' => $calculoKmPago['km_pago'],
                'faixa_calculada' => $calculoKmPago['faixa'],
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Erro ao recalcular km_pago da viagem.', [
                'metodo' => __METHOD__ . '@' . __LINE__,
                'viagem_id' => $viagem->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Testa as 3 faixas de preço e retorna aquela que mais se aproxima
     * 
     * @param float $valorDocumentos
     * @return array ['km_pago' => float, 'valor_km' => float, 'faixa' => string, 'diferenca' => float]
     */
    private function calcularKmPago(float $valorDocumentos): array
    {
        // Calcular KM para cada faixa
        $opcoes = [
            'ate_120' => [
                'km_calculado'  => $valorDocumentos / self::VIAGEM_ATE_120_KM,
                'valor_km'      => self::VIAGEM_ATE_120_KM,
                'limite_inferior' => 0,
                'faixa'         => 'Até 120 km',
                'limite_km'     => 120,
            ],
            'ate_500' => [
                'km_calculado'  => $valorDocumentos / self::VIAGEM_ATE_500_KM,
                'valor_km'      => self::VIAGEM_ATE_500_KM,
                'limite_inferior' => 121,
                'faixa'         => 'Até 500 km',
                'limite_km'     => 500,
            ],
            'ate_1000' => [
                'km_calculado'  => $valorDocumentos / self::VIAGEM_ATE_1000_KM,
                'valor_km'      => self::VIAGEM_ATE_1000_KM,
                'limite_inferior' => 501,
                'faixa' =>      '501-1000 km',
                'limite_km'     => 1000,
            ],
            'acima_1000' => [
                'km_calculado'  => $valorDocumentos / self::VIAGEM_ACIMA_1000_KM,
                'valor_km'      => self::VIAGEM_ACIMA_1000_KM,
                'limite_inferior' => 1001,
                'faixa'         => 'Acima de 1000 km',
                'limite_km'     => PHP_INT_MAX,
            ],
        ];

        // ✅ Encontrar a faixa correta baseada no KM calculado
        $melhorOpcao = null;

        foreach ($opcoes as $key => $value) {
            $kmCalculado    = $value['km_calculado'];
            $limiteInferior = $value['limite_inferior'];
            $limiteSuperior = $value['limite_km'];

            // Verificar se o KM calculado está dentro da faixa (com tolerância)
            if ($kmCalculado >= $limiteInferior && $kmCalculado <= $limiteSuperior * (1 + self::TOLERANCIA_PERCENTUAL)) {
                // Calcular diferença entre valor original e valor recalculado
                $valorRecalculado = $kmCalculado * $value['valor_km'];
                $diferenca = abs($valorDocumentos - $valorRecalculado);

                $melhorOpcao = [
                    'km_pago'   => round($kmCalculado, 2),
                    'valor_km'  => $value['valor_km'],
                    'faixa'     => $value['faixa'],
                    'diferenca' => round($diferenca, 2),
                    'valor_recalculado' => round($valorRecalculado, 2),
                ];

                break;
            }
        }

        if (!$melhorOpcao) {
            $menorDiferenca = PHP_FLOAT_MAX;

            foreach ($opcoes as $opcao) {
                $kmCalculado        = $opcao['km_calculado'];
                $valorRecalculado   = $kmCalculado * $opcao['valor_km'];
                $diferenca          = abs($valorDocumentos - $valorRecalculado);

                if ($diferenca < $menorDiferenca) {
                    $menorDiferenca = $diferenca;
                    $melhorOpcao = [
                        'km_pago'   => round($kmCalculado, 2),
                        'valor_km'  => $opcao['valor_km'],
                        'faixa'     => $opcao['faixa'],
                        'diferenca' => round($diferenca, 2),
                        'valor_recalculado' => round($valorRecalculado, 2),
                    ];
                }
            }
        }

        Log::debug('Cálculo de KM detalhado', [
            'metodo' => __METHOD__ . '@' . __LINE__,
            'valor_documentos' => $valorDocumentos,
            'opcoes_testadas' => $opcoes,
            'resultado' => $melhorOpcao,
        ]);

        return $melhorOpcao;
    }
}
