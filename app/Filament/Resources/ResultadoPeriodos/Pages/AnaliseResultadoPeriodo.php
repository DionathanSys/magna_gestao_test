<?php

namespace App\Filament\Resources\ResultadoPeriodos\Pages;

use App\Filament\Resources\ResultadoPeriodos\ResultadoPeriodoResource;
use App\Models\ResultadoPeriodo;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;

class AnaliseResultadoPeriodo extends Page
{
    protected static string $resource = ResultadoPeriodoResource::class;

    protected string $view = 'filament.resources.resultado-periodos.pages.analise-resultado-periodo';

    public int|string $recordId;

    protected ?ResultadoPeriodo $cachedRecord = null;

    public function mount(int|string $record): void
    {
        $this->recordId = $record;
    }

    public function getTitle(): string
    {
        return 'Análise do veículo';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('editar')
                ->label('Editar resultado')
                ->icon('heroicon-o-pencil-square')
                ->url(fn (): string => ResultadoPeriodoResource::getUrl('edit', ['record' => $this->recordId])),
            Action::make('voltar')
                ->label('Voltar aos resultados')
                ->color('gray')
                ->icon('heroicon-o-arrow-left')
                ->url(fn (): string => ResultadoPeriodoResource::getUrl('index')),
        ];
    }

    public function getRecord(): ResultadoPeriodo
    {
        if ($this->cachedRecord) {
            return $this->cachedRecord;
        }

        return $this->cachedRecord = ResultadoPeriodo::query()
            ->with([
                'veiculo:id,placa,tipo_veiculo_id',
                'veiculo.tipoVeiculo:id,descricao,meta_media',
                'abastecimentoInicial',
                'abastecimentoFinal',
            ])
            ->withCount(['viagens', 'documentos', 'abastecimentos'])
            ->withSum('documentos', 'valor_liquido')
            ->withSum('abastecimentos', 'preco_total')
            ->withSum('abastecimentos', 'quantidade')
            ->withSum('viagens', 'km_pago')
            ->withSum('viagens', 'km_rodado')
            ->withSum('manutencaoLancamentos', 'valor_total_centavos')
            ->findOrFail($this->recordId);
    }

    public function getViewData(): array
    {
        $record = $this->getRecord();
        $faturamento = ((float) ($record->documentos_sum_valor_liquido ?? 0)) / 100;
        $combustivel = ((float) ($record->abastecimentos_sum_preco_total ?? 0)) / 100;
        $manutencao = ((float) ($record->manutencao_lancamentos_sum_valor_total_centavos ?? 0)) / 100;
        $resultadoLiquido = $faturamento - $combustivel - $manutencao;
        $margemLiquida = $faturamento > 0 ? ($resultadoLiquido / $faturamento) * 100 : null;
        $litros = (float) ($record->abastecimentos_sum_quantidade ?? 0);
        $kmPago = (float) ($record->viagens_sum_km_pago ?? 0);
        $kmRodadoViagens = (float) ($record->viagens_sum_km_rodado ?? 0);
        $kmRodadoAbastecimento = $this->getKmRodadoAbastecimento($record);
        $consumo = $kmRodadoAbastecimento !== null && $litros > 0
            ? $kmRodadoAbastecimento / $litros
            : null;
        $metaConsumo = $record->veiculo?->tipoVeiculo?->meta_media;
        $dias = Carbon::parse($record->data_inicio)->diffInDays(Carbon::parse($record->data_fim)) + 1;
        $custoTotal = $combustivel + $manutencao;

        return [
            'record' => $record,
            'resumo' => [
                'faturamento' => $faturamento,
                'combustivel' => $combustivel,
                'manutencao' => $manutencao,
                'resultado_liquido' => $resultadoLiquido,
                'margem_liquida' => $margemLiquida,
                'km_pago' => $kmPago,
                'km_rodado_viagens' => $kmRodadoViagens,
                'km_rodado_abastecimento' => $kmRodadoAbastecimento,
                'dispersao_km' => $kmRodadoAbastecimento !== null ? $kmRodadoAbastecimento - $kmPago : null,
                'consumo' => $consumo,
                'meta_consumo' => $metaConsumo,
                'litros' => $litros,
                'dias' => $dias,
                'custo_por_km' => $kmRodadoAbastecimento ? $custoTotal / $kmRodadoAbastecimento : null,
            ],
            'composicaoFinanceira' => $this->getComposicaoFinanceira($faturamento, $combustivel, $manutencao),
            'alertas' => $this->getAlertas($record, $resultadoLiquido, $margemLiquida, $kmRodadoAbastecimento, $consumo, $metaConsumo),
            'manutencoesPorOs' => $this->getManutencoesPorOs($record),
            'viagens' => $record->viagens()
                ->orderByDesc('data_competencia')
                ->limit(5)
                ->get(['id', 'numero_viagem', 'data_competencia', 'km_pago', 'km_rodado', 'documento_transporte'])
                ->map(fn ($viagem): array => [
                    'numero' => $viagem->numero_viagem,
                    'data' => Carbon::parse($viagem->data_competencia)->format('d/m/Y'),
                    'km_pago' => (float) $viagem->km_pago,
                    'km_rodado' => (float) $viagem->km_rodado,
                    'documento' => $viagem->documento_transporte,
                ]),
            'abastecimentos' => $record->abastecimentos()
                ->orderByDesc('data_abastecimento')
                ->limit(5)
                ->get(['id', 'data_abastecimento', 'posto_combustivel', 'quantidade', 'preco_total', 'quilometragem'])
                ->map(fn ($abastecimento): array => [
                    'data' => Carbon::parse($abastecimento->data_abastecimento)->format('d/m/Y'),
                    'posto' => $abastecimento->posto_combustivel,
                    'litros' => (float) $abastecimento->quantidade,
                    'valor' => (float) $abastecimento->preco_total,
                    'km' => (int) $abastecimento->quilometragem,
                ]),
            'documentos' => $record->documentos()
                ->orderByDesc('data_emissao')
                ->limit(5)
                ->get(['id', 'numero_documento', 'data_emissao', 'parceiro_destino', 'valor_liquido'])
                ->map(fn ($documento): array => [
                    'numero' => $documento->numero_documento,
                    'data' => Carbon::parse($documento->data_emissao)->format('d/m/Y'),
                    'destino' => $documento->parceiro_destino,
                    'valor' => (float) $documento->valor_liquido,
                ]),
        ];
    }

    private function getKmRodadoAbastecimento(ResultadoPeriodo $record): ?int
    {
        $kmFinal = $record->abastecimentoFinal?->quilometragem;
        $kmInicial = $record->abastecimentoInicial?->ultimo_abastecimento_anterior?->quilometragem;

        if ($kmFinal === null || $kmInicial === null) {
            return null;
        }

        return max(0, $kmFinal - $kmInicial);
    }

    private function getManutencoesPorOs(ResultadoPeriodo $record)
    {
        return $record->manutencaoLancamentos()
            ->with('ordemServico:id,tipo_manutencao,status')
            ->orderByDesc('data_negociacao')
            ->get([
                'id',
                'ordem_servico_id',
                'data_negociacao',
                'tipo_manutencao',
                'produto',
                'codigo_produto',
                'quantidade',
                'unidade',
                'grupo_produto',
                'parceiro',
                'valor_total_centavos',
            ])
            ->groupBy(fn ($lancamento): string => $lancamento->ordem_servico_id ? (string) $lancamento->ordem_servico_id : 'sem-os')
            ->map(function ($lancamentos, string $ordemServicoId): array {
                $ordemServico = $lancamentos->first()->ordemServico;

                return [
                    'ordem_servico_id' => $ordemServicoId === 'sem-os' ? null : (int) $ordemServicoId,
                    'tipo' => $ordemServico?->tipo_manutencao?->value ?? $lancamentos->first()->tipo_manutencao,
                    'status' => $ordemServico?->status?->value,
                    'total' => $lancamentos->sum('valor_total_centavos') / 100,
                    'lancamentos' => $lancamentos->map(fn ($lancamento): array => [
                        'data' => Carbon::parse($lancamento->data_negociacao)->format('d/m/Y'),
                        'produto' => $lancamento->produto,
                        'codigo' => $lancamento->codigo_produto,
                        'quantidade' => (float) $lancamento->quantidade,
                        'unidade' => $lancamento->unidade,
                        'grupo' => $lancamento->grupo_produto,
                        'parceiro' => $lancamento->parceiro,
                        'valor' => $lancamento->valor_total_centavos / 100,
                    ]),
                ];
            })
            ->sortByDesc('total')
            ->values();
    }

    private function getComposicaoFinanceira(float $faturamento, float $combustivel, float $manutencao): array
    {
        $base = max($faturamento, 1);

        return [
            ['label' => 'Combustível', 'valor' => $combustivel, 'percentual' => min(100, ($combustivel / $base) * 100), 'cor' => 'amber'],
            ['label' => 'Manutenção', 'valor' => $manutencao, 'percentual' => min(100, ($manutencao / $base) * 100), 'cor' => 'rose'],
            ['label' => 'Resultado líquido', 'valor' => $faturamento - $combustivel - $manutencao, 'percentual' => max(0, min(100, (($faturamento - $combustivel - $manutencao) / $base) * 100)), 'cor' => 'emerald'],
        ];
    }

    private function getAlertas(
        ResultadoPeriodo $record,
        float $resultadoLiquido,
        ?float $margemLiquida,
        ?int $kmRodadoAbastecimento,
        ?float $consumo,
        mixed $metaConsumo,
    ): array {
        $alertas = [];

        if ($record->documentos_count === 0) {
            $alertas[] = ['tom' => 'warning', 'titulo' => 'Sem faturamento vinculado', 'descricao' => 'Não há documentos de frete neste resultado.'];
        }

        if ($resultadoLiquido < 0) {
            $alertas[] = ['tom' => 'danger', 'titulo' => 'Resultado negativo', 'descricao' => 'Os custos vinculados superam o faturamento do período.'];
        } elseif ($margemLiquida !== null && $margemLiquida < 10) {
            $alertas[] = ['tom' => 'warning', 'titulo' => 'Margem reduzida', 'descricao' => 'A margem líquida está abaixo de 10%.'];
        }

        if ($kmRodadoAbastecimento === null) {
            $alertas[] = ['tom' => 'info', 'titulo' => 'KM de abastecimento incompleto', 'descricao' => 'É necessário um abastecimento anterior e um final para apurar o KM rodado.'];
        }

        if ($consumo !== null && $metaConsumo && $consumo < $metaConsumo) {
            $alertas[] = ['tom' => 'warning', 'titulo' => 'Consumo abaixo da meta', 'descricao' => 'O consumo apurado está abaixo da meta configurada para o tipo de veículo.'];
        }

        if ($alertas === []) {
            $alertas[] = ['tom' => 'success', 'titulo' => 'Resultado sob controle', 'descricao' => 'Os principais indicadores possuem dados e não apresentam desvios relevantes.'];
        }

        return $alertas;
    }
}
