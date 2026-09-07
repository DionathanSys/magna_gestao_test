<?php

namespace App\Filament\Resources\ResultadoPeriodos\Pages;

use App\Enum\StatusDiversosEnum;
use App\Filament\Resources\ManutencaoLancamentos\ManutencaoLancamentoResource;
use App\Filament\Resources\ResultadoPeriodos\ResultadoPeriodoResource;
use App\Models\ManutencaoLancamento;
use App\Services\ResultadoPeriodo\ResultadoPeriodoVinculoService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

class AnaliseManutencaoResultadoPeriodo extends AnaliseResultadoPeriodo implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected string $view = 'filament.resources.resultado-periodos.pages.analise-manutencao-resultado-periodo';

    public function getTitle(): string
    {
        return 'Custos de manutenção';
    }

    public function table(Table $table): Table
    {
        return ManutencaoLancamentoResource::table($table)
            ->query($this->getRecord()->manutencaoLancamentos()->getQuery())
            ->groups([
                Group::make('ordemServico.id')
                    ->label('Ordem interna')
                    ->collapsible(),
            ])
            ->defaultGroup('ordemServico.id')
            ->headerActions([
                Action::make('vincular_lancamentos')
                    ->label('Vincular custos')
                    ->icon('heroicon-o-link')
                    ->visible(fn (): bool => $this->getRecord()->status === StatusDiversosEnum::PENDENTE->value)
                    ->schema([
                        Select::make('lancamentos')
                            ->label('Lançamentos sem vínculo')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->required()
                            ->options(function (): array {
                                $record = $this->getRecord();

                                return ManutencaoLancamento::query()
                                    ->whereNull('resultado_periodo_id')
                                    ->where('veiculo_id', $record->veiculo_id)
                                    ->orderByDesc('data_negociacao')
                                    ->get()
                                    ->mapWithKeys(fn (ManutencaoLancamento $lancamento): array => [
                                        $lancamento->id => sprintf(
                                            '%s | %s | %s | R$ %s',
                                            $lancamento->data_negociacao?->format('d/m/Y') ?? 'Sem data',
                                            $lancamento->produto,
                                            $lancamento->ordem_servico_id ? 'OS #'.$lancamento->ordem_servico_id : 'Sem OS',
                                            number_format($lancamento->valor_total_centavos / 100, 2, ',', '.')
                                        ),
                                    ])
                                    ->all();
                            }),
                    ])
                    ->action(function (array $data, ResultadoPeriodoVinculoService $service): void {
                        $record = $this->getRecord();
                        $vinculados = 0;

                        try {
                            ManutencaoLancamento::query()
                                ->whereKey($data['lancamentos'])
                                ->whereNull('resultado_periodo_id')
                                ->where('veiculo_id', $record->veiculo_id)
                                ->each(function (ManutencaoLancamento $lancamento) use ($record, $service, &$vinculados): void {
                                    if ($service->vincular($lancamento, 'resultado_especifico', resultadoPeriodoId: $record->id)) {
                                        $vinculados++;
                                    }
                                });
                        } catch (\RuntimeException $exception) {
                            Notification::make()
                                ->title('Vínculo não realizado')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title("{$vinculados} lançamento(s) vinculado(s) ao resultado.")
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public function getViewData(): array
    {
        return [
            'record' => $this->getRecord(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('analise')
                ->label('Voltar à análise')
                ->icon('heroicon-o-chart-bar')
                ->url(fn (): string => ResultadoPeriodoResource::getUrl('analise', ['record' => $this->recordId])),
            Action::make('editar')
                ->label('Editar resultado')
                ->icon('heroicon-o-pencil-square')
                ->url(fn (): string => ResultadoPeriodoResource::getUrl('edit', ['record' => $this->recordId])),
        ];
    }
}
