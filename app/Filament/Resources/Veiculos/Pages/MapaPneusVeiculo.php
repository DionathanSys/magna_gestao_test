<?php

namespace App\Filament\Resources\Veiculos\Pages;

use App\Enum\Pneu\TipoInspecaoPneuEnum;
use App\Filament\Resources\PneuInspecoes\Schemas\PneuInspecaoForm;
use App\Filament\Resources\Veiculos\VeiculoResource;
use App\Models\HistoricoMovimentoPneu;
use App\Models\PneuInspecao;
use App\Models\PneuPosicaoVeiculo;
use App\Models\Veiculo;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class MapaPneusVeiculo extends Page implements HasActions, HasTable
{
    use InteractsWithActions;
    use InteractsWithTable;

    protected static string $resource = VeiculoResource::class;

    protected static ?string $title = 'Mapa de Pneus';

    protected string $view = 'filament.resources.veiculos.pages.mapa-pneus-veiculo';

    public static function canAccess(array $parameters = []): bool
    {
        return true;
    }

    public int|string $recordId;

    public function mount(int|string $record): void
    {
        $this->recordId = $record;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('voltar')
                ->label('Voltar ao Veículo')
                ->url(fn () => VeiculoResource::getUrl('edit', ['record' => $this->getRecord()], isAbsolute: false)),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Mapa de pneus - '.$this->getRecord()->placa)
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('pneu.numero_fogo')
                    ->label('Pneu')
                    ->placeholder('Vazio')
                    ->weight(FontWeight::SemiBold),
                TextColumn::make('posicao')
                    ->label('Posição'),
                TextColumn::make('eixo')
                    ->label('Eixo'),
                TextColumn::make('sequencia')
                    ->label('Sequência'),
                TextColumn::make('km_inicial')
                    ->label('KM Inicial')
                    ->numeric(0, ',', '.'),
                TextColumn::make('veiculo.kmAtual.quilometragem')
                    ->label('Km Atual')
                    ->numeric(0, ',', '.'),
                TextColumn::make('km_rodado')
                    ->label('Km Posição')
                    ->numeric(0, ',', '.'),
                TextColumn::make('km_total_historico_ciclo')
                    ->label('Km Ciclo Atual')
                    ->numeric(0, ',', '.')
                    ->state(function (PneuPosicaoVeiculo $record): int {
                        if (! $record->pneu) {
                            return 0;
                        }

                        $kmHistorico = HistoricoMovimentoPneu::where('pneu_id', $record->pneu->id)
                            ->where('ciclo_vida', $record->pneu->ciclo_vida)
                            ->sum('km_percorrido');

                        return $kmHistorico + ($record->km_rodado ?? 0);
                    }),
                TextColumn::make('pneu.marcaCatalogo.nome')
                    ->label('Marca')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('pneu.modeloCatalogo.nome')
                    ->label('Modelo')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ultima_inspecao_data')
                    ->label('Última Inspeção')
                    ->state(fn (PneuPosicaoVeiculo $record) => $record->pneu?->inspecoes?->first()?->data_inspecao?->format('d/m/Y') ?? 'Sem registro'),
                TextColumn::make('ultima_inspecao_resultado')
                    ->label('Último Resultado')
                    ->state(fn (PneuPosicaoVeiculo $record) => $record->pneu?->inspecoes?->first()?->resultado?->value ?? 'N/A'),
            ])
            ->defaultSort('sequencia')
            ->paginated(false)
            ->groups([
                'eixo',
            ])
            ->defaultGroup('eixo')
            ->recordActions([
                Action::make('inspecionar')
                    ->label('Inspecionar')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->slideOver()
                    ->fillForm(function (PneuPosicaoVeiculo $record): array {
                        return [
                            'pneu_id' => $record->pneu_id,
                            'pneu_ciclo_id' => $record->pneu_ciclo_id,
                            'veiculo_id' => $record->veiculo_id,
                            'pneu_posicao_veiculo_id' => $record->id,
                            'tipo' => TipoInspecaoPneuEnum::CAMPO->value,
                            'resultado' => null,
                            'data_inspecao' => now()->toDateString(),
                            'km_referencia' => $this->getRecord()->quilometragem_atual,
                            'parceiro_id' => null,
                            'apto_recapagem' => null,
                            'observacao' => null,
                            'anexos' => [],
                        ];
                    })
                    ->schema(function (PneuPosicaoVeiculo $record): array {
                        return array_merge([
                            Section::make('Resumo do Pneu')
                                ->columns(2)
                                ->components([
                                    Placeholder::make('pneu_info')
                                        ->label('Pneu')
                                        ->content(($record->pneu?->numero_fogo ?? 'N/A').' - '.($record->pneu?->marcaCatalogo?->nome ?? 'N/A').' / '.($record->pneu?->modeloCatalogo?->nome ?? 'N/A')),
                                    Placeholder::make('posicao_info')
                                        ->label('Posição')
                                        ->content($record->eixo.'º eixo / '.$record->posicao),
                                    Placeholder::make('medida_info')
                                        ->label('Medida')
                                        ->content($record->pneu?->medidaCatalogo?->codigo ?? 'N/A'),
                                    Placeholder::make('km_info')
                                        ->label('Km Rodado na Posição')
                                        ->content(number_format($record->km_rodado ?? 0, 0, ',', '.')),
                                    Placeholder::make('ultima_inspecao_info')
                                        ->label('Última Inspeção')
                                        ->content($record->pneu?->inspecoes?->first()?->data_inspecao?->format('d/m/Y') ?? 'Sem registro'),
                                    Placeholder::make('ultimo_resultado_info')
                                        ->label('Último Resultado')
                                        ->content($record->pneu?->inspecoes?->first()?->resultado?->value ?? 'N/A'),
                                ]),
                        ], PneuInspecaoForm::getComponents());
                    })
                    ->action(function (array $data): void {
                        PneuInspecao::create($data);

                        Notification::make()
                            ->title('Inspeção registrada com sucesso')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public function getRecord(): Veiculo
    {
        return Veiculo::query()
            ->with(['kmAtual', 'tipoVeiculo'])
            ->findOrFail($this->recordId);
    }

    public function getViewData(): array
    {
        return [
            'record' => $this->getRecord(),
        ];
    }

    protected function getTableQuery()
    {
        return PneuPosicaoVeiculo::query()
            ->where('veiculo_id', $this->recordId)
            ->with([
                'pneu.inspecoes' => fn ($query) => $query->latest('data_inspecao')->latest('id'),
                'pneu.marcaCatalogo',
                'pneu.modeloCatalogo',
                'pneu.medidaCatalogo',
                'veiculo.kmAtual',
            ])
            ->orderBy('sequencia');
    }
}
