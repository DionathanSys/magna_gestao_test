<?php

namespace App\Filament\Resources\Veiculos\Pages;

use App\Enum\Pneu\TipoInspecaoPneuEnum;
use App\Filament\Resources\PneuInspecoes\Schemas\PneuInspecaoForm;
use App\Filament\Resources\Veiculos\VeiculoResource;
use App\Models\Pneu;
use App\Models\PneuInspecao;
use App\Models\PneuPosicaoVeiculo;
use App\Models\Veiculo;
use App\Services\NotificacaoService as notify;
use App\Services\Pneus\MovimentarPneuService;
use App\Services\Pneus\PneuService;
use App\Services\Pneus\SincronizarPosicoesMapaVeiculoService;
use App\Support\Pneus\MapaPneusLayout;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Support\Collection;

class MapaPneusVeiculo extends Page implements HasActions
{
    use InteractsWithActions;

    protected static string $resource = VeiculoResource::class;

    protected static ?string $title = 'Mapa de Pneus';

    protected string $view = 'filament.resources.veiculos.pages.mapa-pneus-veiculo';

    public int|string $recordId;

    public ?int $selectedPosicaoId = null;

    public ?int $rodizioSourcePosicaoId = null;

    public ?int $reaplicarSourcePosicaoId = null;

    public string $interactionMode = 'inspect';

    protected ?Veiculo $cachedRecord = null;

    protected ?Collection $cachedPosicoes = null;

    public function mount(int|string $record): void
    {
        $this->recordId = $record;
        $this->syncMapPositions();
        $this->selectedPosicaoId = request()->integer('selected') ?: $this->getPosicoes()->first()?->id;
    }

    public static function canAccess(array $parameters = []): bool
    {
        return true;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('voltar')
                ->label('Voltar ao veículo')
                ->url(fn () => VeiculoResource::getUrl('edit', ['record' => $this->getRecord()], false)),
        ];
    }

    public function selectPosicao(int $posicaoId): void
    {
        $this->selectedPosicaoId = $posicaoId;
    }

    public function setInteractionMode(string $mode): void
    {
        if (! array_key_exists($mode, $this->getInteractionModes())) {
            return;
        }

        $this->interactionMode = $mode;

        if ($mode !== 'rotate') {
            $this->rodizioSourcePosicaoId = null;
        }

        if ($mode !== 'reapply') {
            $this->reaplicarSourcePosicaoId = null;
        }
    }

    public function openInspection(int $posicaoId): void
    {
        $this->selectedPosicaoId = $posicaoId;
        $this->replaceMountedAction('inspecionarPosicao', ['posicao' => $posicaoId]);
    }

    public function openPosicaoAction(string $actionName, int $posicaoId): void
    {
        $this->selectedPosicaoId = $posicaoId;
        $this->replaceMountedAction($actionName, ['posicao' => $posicaoId]);
    }

    public function handleSlotClick(int $posicaoId): void
    {
        $this->selectedPosicaoId = $posicaoId;

        $posicao = $this->getPosicoes()->firstWhere('id', $posicaoId);

        if (! $posicao) {
            return;
        }

        if (! $this->ensureOperationalPosition($posicao)) {
            return;
        }

        match ($this->interactionMode) {
            'inspect' => filled($posicao->pneu_id)
                ? $this->openInspection($posicaoId)
                : null,
            'rotate' => $this->handleRodizioSelection($posicao),
            'reapply' => $this->handleReaplicarSelection($posicao),
            'invert' => filled($posicao->pneu_id)
                ? $this->openPosicaoAction('inverterPosicao', $posicaoId)
                : $this->notifyInvalidSelection('Selecione um pneu aplicado para inverter.'),
            'swap' => filled($posicao->pneu_id)
                ? $this->openPosicaoAction('trocarPosicao', $posicaoId)
                : $this->notifyInvalidSelection('Selecione um pneu aplicado para trocar.'),
            'remove' => filled($posicao->pneu_id)
                ? $this->openPosicaoAction('desvincularPosicao', $posicaoId)
                : $this->notifyInvalidSelection('Selecione um pneu aplicado para desvincular.'),
            'bind' => blank($posicao->pneu_id)
                ? $this->openPosicaoAction('vincularPosicao', $posicaoId)
                : $this->notifyInvalidSelection('Selecione uma posição vazia para vincular um pneu.'),
            default => null,
        };
    }

    public function getRecord(): Veiculo
    {
        if ($this->cachedRecord) {
            return $this->cachedRecord;
        }

        return $this->cachedRecord = Veiculo::query()
            ->with(['kmAtual', 'tipoVeiculo', 'mapaPneu.posicoes'])
            ->findOrFail($this->recordId);
    }

    public function getViewData(): array
    {
        $posicoes = $this->getPosicoes();
        $selectedPosicao = $this->selectedPosicaoId
            ? $posicoes->firstWhere('id', $this->selectedPosicaoId)
            : null;

        return [
            'record' => $this->getRecord(),
            'mapa' => MapaPneusLayout::build($this->getRecord(), $posicoes, $this->selectedPosicaoId),
            'selectedPosicao' => $selectedPosicao,
            'interactionMode' => $this->interactionMode,
            'interactionModes' => $this->getInteractionModes(),
        ];
    }

    protected function getPosicoes()
    {
        if ($this->cachedPosicoes) {
            return $this->cachedPosicoes;
        }

        return $this->cachedPosicoes = PneuPosicaoVeiculo::query()
            ->where('veiculo_id', $this->recordId)
            ->with([
                'pneu.ultimaInspecao',
                'pneu.marcaCatalogo',
                'pneu.modeloCatalogo',
                'pneu.medidaCatalogo',
                'pneu.ultimoRecap.desenhoPneu',
                'pneu.cicloAtual.desenhoPneu',
                'pneu.desenhoPneu',
                'veiculo.kmAtual',
                'mapaPosicao',
            ])
            ->orderBy('sequencia')
            ->get();
    }

    protected function syncMapPositions(): void
    {
        $record = $this->getRecord();

        if (! $record->mapa_pneu_id) {
            return;
        }

        app(SincronizarPosicoesMapaVeiculoService::class)->handle($record);
        $this->flushPageCache();
    }

    public function inspecionarPosicaoAction(): Action
    {
        return Action::make('inspecionarPosicao')
            ->label('Inspecionar')
            ->icon('heroicon-o-clipboard-document-check')
            ->visible(function (?PneuPosicaoVeiculo $record, array $arguments): bool {
                $posicao = $record ?? $this->resolvePosicaoFromArguments($arguments);

                return filled($posicao?->pneu_id);
            })
            ->slideOver()
            ->fillForm(function (?PneuPosicaoVeiculo $record, array $arguments): array {
                $posicao = $record ?? $this->resolvePosicaoFromArguments($arguments);

                return $this->getInspectionFormData($posicao);
            })
            ->schema(function (?PneuPosicaoVeiculo $record, array $arguments): array {
                $posicao = $record ?? $this->resolvePosicaoFromArguments($arguments);

                return array_merge([
                    Section::make('Resumo do Pneu')
                        ->columns(12)
                        ->collapsible()
                        ->persistCollapsed()
                        ->components([
                            Grid::make([
                                'default' => 1,
                                'md' => 2,
                                'xl' => 3,
                            ])
                                ->columnSpanFull()
                                ->schema([
                                    TextInput::make('pneu_info')->label('Pneu')->readOnly()->disabled()->dehydrated(false),
                                    TextInput::make('veiculo_info')->label('Veículo')->readOnly()->disabled()->dehydrated(false),
                                    TextInput::make('posicao_info')->label('Posição')->readOnly()->disabled()->dehydrated(false),
                                    TextInput::make('medida_info')->label('Medida')->readOnly()->disabled()->dehydrated(false),
                                    TextInput::make('ciclo_info')->label('Ciclo Atual')->readOnly()->disabled()->dehydrated(false),
                                    TextInput::make('km_info')->label('Km Rodado na Posição')->readOnly()->disabled()->dehydrated(false),
                                    TextInput::make('ultima_inspecao_info')->label('Última Inspeção')->readOnly()->disabled()->dehydrated(false),
                                    TextInput::make('ultimo_resultado_info')->label('Último Resultado')->readOnly()->disabled()->dehydrated(false),
                                    TextInput::make('ultimo_sulco_info')->label('Último Sulco')->readOnly()->disabled()->dehydrated(false),
                                ]),
                        ]),
                ], PneuInspecaoForm::getComponentsForOperationalAction());
            })
            ->action(function (array $data): void {
                PneuInspecao::create($data);
                $this->flushPageCache();

                Notification::make()
                    ->title('Inspeção registrada com sucesso')
                    ->success()
                    ->send();
            });
    }

    public function vincularPosicaoAction(): Action
    {
        return Action::make('vincularPosicao')
            ->label('Vincular')
            ->icon('heroicon-o-arrow-up-on-square')
            ->color('info')
            ->modalWidth(Width::ExtraLarge)
            ->visible(function (array $arguments): bool {
                $posicao = $this->resolvePosicaoFromArguments($arguments);

                return blank($posicao?->pneu_id);
            })
            ->fillForm(function (array $arguments): array {
                $posicao = $this->resolvePosicaoFromArguments($arguments);

                return [
                    'posicao' => $this->formatPosicaoCode($posicao),
                    'data_inicial' => now()->toDateString(),
                ];
            })
            ->schema(fn (Schema $schema) => $schema
                ->columns(4)
                ->schema([
                    Select::make('pneu_id')
                        ->label('Pneu')
                        ->columnSpan(3)
                        ->native(false)
                        ->getSearchResultsUsing(fn (string $search): array => (new PneuService)->getPneusDisponiveis($search))
                        ->getOptionLabelUsing(fn ($value): ?string => Pneu::find($value)?->numero_fogo)
                        ->searchable()
                        ->searchDebounce(700)
                        ->required(),
                    TextInput::make('posicao')
                        ->label('Posição')
                        ->columnSpan(1)
                        ->readOnly(),
                    DatePicker::make('data_inicial')
                        ->label('Dt. Inicial')
                        ->columnSpan(2)
                        ->default(now())
                        ->maxDate(now())
                        ->required(),
                    $this->makeKmField('km_inicial', 'KM Inicial')
                        ->columnSpan(2),
                ]))
            ->action(function (Action $action, array $data, array $arguments): void {
                $posicao = $this->resolvePosicaoFromArguments($arguments);

                if (! $posicao) {
                    notify::error(titulo: 'Falha ao vincular pneu', mensagem: 'Posição não encontrada.');
                    $action->halt();

                    return;
                }

                try {
                    (new MovimentarPneuService)->aplicarPneu($posicao, $data);
                    $this->flushPageCache();
                } catch (\Throwable $e) {
                    notify::error(titulo: 'Falha ao vincular pneu', mensagem: $e->getMessage());
                    $action->halt();
                }
            });
    }

    public function inverterPosicaoAction(): Action
    {
        return Action::make('inverterPosicao')
            ->label('Inverter')
            ->icon('heroicon-o-arrow-path')
            ->modalWidth(Width::ExtraLarge)
            ->visible(function (array $arguments): bool {
                $posicao = $this->resolvePosicaoFromArguments($arguments);

                return filled($posicao?->pneu_id);
            })
            ->schema(fn (Schema $schema) => $schema
                ->columns(8)
                ->schema([
                    TextInput::make('motivo')
                        ->columnSpan(5)
                        ->default(\App\Enum\Pneu\MotivoMovimentoPneuEnum::INVERSAO->value)
                        ->disabled()
                        ->required(),
                    TextInput::make('sulco')
                        ->label('Sulco (mm)')
                        ->columnSpan(3)
                        ->numeric()
                        ->maxValue(30)
                        ->minValue(0),
                    DatePicker::make('data_movimento')
                        ->label('Dt. Movimento')
                        ->columnSpan(4)
                        ->default(now())
                        ->maxDate(now())
                        ->required(),
                    $this->makeKmField('km_movimento', 'Km Movimento')
                        ->columnSpan(4),
                    Textarea::make('observacao')
                        ->label('Observação')
                        ->columnSpanFull()
                        ->maxLength(255),
                    $this->makeAnexosField(),
                ]))
            ->action(function (Action $action, array $data, array $arguments): void {
                $posicao = $this->resolvePosicaoFromArguments($arguments);

                if (! $posicao) {
                    notify::error(titulo: 'Falha ao inverter pneu', mensagem: 'Posição não encontrada.');
                    $action->halt();

                    return;
                }

                try {
                    (new MovimentarPneuService)->inverterPneu($posicao, $data);
                    $this->flushPageCache();
                } catch (\Throwable $e) {
                    notify::error(titulo: 'Falha ao inverter pneu', mensagem: $e->getMessage());
                    $action->halt();
                }
            });
    }

    public function rodizioPosicoesAction(): Action
    {
        return Action::make('rodizioPosicoes')
            ->label('Rodízio')
            ->icon('heroicon-o-arrows-right-left')
            ->modalWidth(Width::Large)
            ->visible(function (array $arguments): bool {
                $origem = $this->resolvePosicaoFromArguments(['posicao' => $arguments['source'] ?? null]);
                $destino = $this->resolvePosicaoFromArguments(['posicao' => $arguments['target'] ?? null]);

                return filled($origem?->pneu_id) && filled($destino?->pneu_id);
            })
            ->schema(fn (Schema $schema) => $schema
                ->columns(8)
                ->schema([
                    TextInput::make('motivo')
                        ->columnSpan(5)
                        ->default(\App\Enum\Pneu\MotivoMovimentoPneuEnum::RODIZIO->value)
                        ->disabled()
                        ->required(),
                    TextInput::make('sulco')
                        ->label('Sulco Removido (mm)')
                        ->columnSpan(3)
                        ->numeric()
                        ->maxValue(30)
                        ->minValue(0),
                    DatePicker::make('data_movimento')
                        ->label('Dt. Movimento')
                        ->columnSpan(4)
                        ->default(now())
                        ->maxDate(now())
                        ->required(),
                    $this->makeKmField('km_movimento', 'Km Movimento')
                        ->columnSpan(4),
                    Textarea::make('observacao')
                        ->label('Observação')
                        ->columnSpanFull()
                        ->maxLength(255),
                    $this->makeAnexosField(),
                ]))
            ->action(function (Action $action, array $data, array $arguments): void {
                $origem = $this->resolvePosicaoFromArguments(['posicao' => $arguments['source'] ?? null]);
                $destino = $this->resolvePosicaoFromArguments(['posicao' => $arguments['target'] ?? null]);

                if (! $origem || ! $destino) {
                    notify::error(titulo: 'Falha ao realizar rodízio', mensagem: 'Posições não encontradas.');
                    $action->halt();

                    return;
                }

                try {
                    (new MovimentarPneuService)->rodizioPneu(collect([$origem, $destino]), $data);
                    $this->rodizioSourcePosicaoId = null;
                    $this->flushPageCache();
                } catch (\Throwable $e) {
                    notify::error(titulo: 'Falha ao realizar rodízio', mensagem: $e->getMessage());
                    $action->halt();
                }
            });
    }

    public function reaplicarPosicoesAction(): Action
    {
        return Action::make('reaplicarPosicoes')
            ->label('Reaplicar')
            ->icon('heroicon-o-arrow-path')
            ->modalWidth(Width::Large)
            ->visible(function (array $arguments): bool {
                $origem = $this->resolvePosicaoFromArguments(['posicao' => $arguments['source'] ?? null]);
                $destino = $this->resolvePosicaoFromArguments(['posicao' => $arguments['target'] ?? null]);

                return filled($origem?->pneu_id) && blank($destino?->pneu_id);
            })
            ->schema(fn (Schema $schema) => $schema
                ->columns(8)
                ->schema([
                    TextInput::make('motivo')
                        ->columnSpan(5)
                        ->default(\App\Enum\Pneu\MotivoMovimentoPneuEnum::REAPLICACAO->value)
                        ->disabled()
                        ->required(),
                    TextInput::make('sulco')
                        ->label('Sulco Removido (mm)')
                        ->columnSpan(3)
                        ->numeric()
                        ->maxValue(30)
                        ->minValue(0),
                    DatePicker::make('data_movimento')
                        ->label('Dt. Movimento')
                        ->columnSpan(4)
                        ->default(now())
                        ->maxDate(now())
                        ->required(),
                    $this->makeKmField('km_movimento', 'Km Movimento')
                        ->columnSpan(4),
                    Textarea::make('observacao')
                        ->label('Observação')
                        ->columnSpanFull()
                        ->maxLength(255),
                    $this->makeAnexosField(),
                ]))
            ->action(function (Action $action, array $data, array $arguments): void {
                $origem = $this->resolvePosicaoFromArguments(['posicao' => $arguments['source'] ?? null]);
                $destino = $this->resolvePosicaoFromArguments(['posicao' => $arguments['target'] ?? null]);

                if (! $origem || ! $destino) {
                    notify::error(titulo: 'Falha ao reaplicar pneu', mensagem: 'Posições não encontradas.');
                    $action->halt();

                    return;
                }

                try {
                    (new MovimentarPneuService)->reaplicarPneu($origem, $destino, $data);
                    $this->reaplicarSourcePosicaoId = null;
                    $this->flushPageCache();
                } catch (\Throwable $e) {
                    notify::error(titulo: 'Falha ao reaplicar pneu', mensagem: $e->getMessage());
                    $action->halt();
                }
            });
    }

    public function trocarPosicaoAction(): Action
    {
        return Action::make('trocarPosicao')
            ->label('Trocar')
            ->icon('heroicon-o-arrows-right-left')
            ->modalWidth(Width::ExtraLarge)
            ->visible(function (array $arguments): bool {
                $posicao = $this->resolvePosicaoFromArguments($arguments);

                return filled($posicao?->pneu_id);
            })
            ->schema(fn (Schema $schema) => $schema
                ->columns(8)
                ->schema([
                    Select::make('motivo')
                        ->columnSpan(5)
                        ->options(\App\Enum\Pneu\MotivoMovimentoPneuEnum::toSelectArray())
                        ->required(),
                    TextInput::make('sulco')
                        ->label('Sulco Removido (mm)')
                        ->columnSpan(3)
                        ->numeric()
                        ->maxValue(30)
                        ->minValue(0),
                    Select::make('pneu_id')
                        ->label('Pneu')
                        ->columnSpanFull()
                        ->native(false)
                        ->getSearchResultsUsing(fn (string $search): array => (new PneuService)->getPneusDisponiveis($search))
                        ->getOptionLabelUsing(fn ($value): ?string => Pneu::find($value)?->numero_fogo)
                        ->searchable()
                        ->searchDebounce(700)
                        ->required(),
                    DatePicker::make('data_movimento')
                        ->label('Dt. Movimento')
                        ->columnSpan(4)
                        ->default(now())
                        ->maxDate(now())
                        ->required(),
                    $this->makeKmField('km_movimento', 'Km Movimento')
                        ->columnSpan(4),
                    Textarea::make('observacao')
                        ->label('Observação')
                        ->columnSpanFull()
                        ->maxLength(255),
                    $this->makeAnexosField(),
                ]))
            ->action(function (Action $action, array $data, array $arguments): void {
                $posicao = $this->resolvePosicaoFromArguments($arguments);

                if (! $posicao) {
                    notify::error(titulo: 'Falha ao substituir pneu', mensagem: 'Posição não encontrada.');
                    $action->halt();

                    return;
                }

                try {
                    (new MovimentarPneuService)->trocarPneu($posicao, $data);
                    $this->flushPageCache();
                } catch (\Throwable $e) {
                    notify::error(titulo: 'Falha ao substituir pneu', mensagem: $e->getMessage());
                    $action->halt();
                }
            });
    }

    public function desvincularPosicaoAction(): Action
    {
        return Action::make('desvincularPosicao')
            ->label('Desvincular')
            ->icon('heroicon-o-arrow-down-on-square')
            ->color('danger')
            ->modalWidth(Width::Large)
            ->visible(function (array $arguments): bool {
                $posicao = $this->resolvePosicaoFromArguments($arguments);

                return filled($posicao?->pneu_id);
            })
            ->schema(fn (Schema $schema) => $schema
                ->columns(8)
                ->schema([
                    Select::make('motivo')
                        ->columnSpanFull()
                        ->native(false)
                        ->options(\App\Enum\Pneu\MotivoMovimentoPneuEnum::toSelectArray())
                        ->required(),
                    TextInput::make('sulco')
                        ->label('Sulco Removido (mm)')
                        ->columnSpan(2)
                        ->required()
                        ->numeric()
                        ->maxValue(30)
                        ->minValue(0),
                    DatePicker::make('data_final')
                        ->label('Dt. Final')
                        ->columnSpan(3)
                        ->default(now())
                        ->maxDate(now())
                        ->required(),
                    $this->makeKmField('km_final', 'Km Final')
                        ->columnSpan(3),
                    Textarea::make('observacao')
                        ->label('Observação')
                        ->columnSpanFull()
                        ->maxLength(255),
                    $this->makeAnexosField(),
                ]))
            ->action(function (Action $action, array $data, array $arguments): void {
                $posicao = $this->resolvePosicaoFromArguments($arguments);

                if (! $posicao) {
                    notify::error(titulo: 'Falha ao desvincular pneu', mensagem: 'Posição não encontrada.');
                    $action->halt();

                    return;
                }

                try {
                    (new MovimentarPneuService)->removerPneu($posicao, $data);
                    $this->flushPageCache();
                } catch (\Throwable $e) {
                    notify::error(titulo: 'Falha ao desvincular pneu', mensagem: $e->getMessage());
                    $action->halt();
                }
            });
    }

    protected function resolvePosicaoFromArguments(array $arguments): ?PneuPosicaoVeiculo
    {
        $posicaoId = $arguments['posicao'] ?? null;

        if (! $posicaoId) {
            return null;
        }

        return $this->getPosicoes()->firstWhere('id', (int) $posicaoId);
    }

    protected function getInteractionModes(): array
    {
        return [
            'inspect' => ['label' => 'Inspecionar', 'hint' => 'Clique no pneu aplicado para abrir a inspeção.'],
            'rotate' => ['label' => 'Rodízio', 'hint' => 'Clique no primeiro pneu e depois no segundo para abrir o rodízio.'],
            'reapply' => ['label' => 'Reaplicar', 'hint' => 'Clique no pneu de origem e depois em uma posição vazia para reaplicá-lo.'],
            'invert' => ['label' => 'Inverter', 'hint' => 'Clique no pneu aplicado para abrir a inversão.'],
            'swap' => ['label' => 'Trocar', 'hint' => 'Clique no pneu aplicado para substituir por outro.'],
            'remove' => ['label' => 'Desvincular', 'hint' => 'Clique no pneu aplicado para remover da posição.'],
            'bind' => ['label' => 'Vincular', 'hint' => 'Clique em uma posição vazia para aplicar um pneu.'],
        ];
    }

    protected function notifyInvalidSelection(string $message): void
    {
        Notification::make()
            ->title($message)
            ->warning()
            ->send();
    }

    protected function flushPageCache(): void
    {
        $this->cachedRecord = null;
        $this->cachedPosicoes = null;
    }

    protected function handleRodizioSelection(PneuPosicaoVeiculo $posicao): void
    {
        if (blank($posicao->pneu_id)) {
            $this->notifyInvalidSelection('Selecione um pneu aplicado para o rodízio.');

            return;
        }

        if (! $this->rodizioSourcePosicaoId) {
            $this->rodizioSourcePosicaoId = $posicao->id;

            Notification::make()
                ->title('Selecione o segundo pneu para concluir o rodízio.')
                ->info()
                ->send();

            return;
        }

        if ($this->rodizioSourcePosicaoId === $posicao->id) {
            $this->notifyInvalidSelection('Selecione uma segunda posição diferente para o rodízio.');

            return;
        }

        $this->openPosicaoAction('rodizioPosicoes', $this->rodizioSourcePosicaoId);
        $this->replaceMountedAction('rodizioPosicoes', [
            'source' => $this->rodizioSourcePosicaoId,
            'target' => $posicao->id,
        ]);
    }

    protected function handleReaplicarSelection(PneuPosicaoVeiculo $posicao): void
    {
        if (! $this->reaplicarSourcePosicaoId) {
            if (blank($posicao->pneu_id)) {
                $this->notifyInvalidSelection('Selecione um pneu aplicado para iniciar a reaplicação.');

                return;
            }

            $this->reaplicarSourcePosicaoId = $posicao->id;

            Notification::make()
                ->title('Selecione uma posição vazia para concluir a reaplicação.')
                ->info()
                ->send();

            return;
        }

        if ($this->reaplicarSourcePosicaoId === $posicao->id) {
            $this->notifyInvalidSelection('Selecione uma posição de destino diferente da origem.');

            return;
        }

        if (filled($posicao->pneu_id)) {
            $this->notifyInvalidSelection('Selecione uma posição vazia para reaplicar o pneu.');

            return;
        }

        $this->openPosicaoAction('reaplicarPosicoes', $this->reaplicarSourcePosicaoId);
        $this->replaceMountedAction('reaplicarPosicoes', [
            'source' => $this->reaplicarSourcePosicaoId,
            'target' => $posicao->id,
        ]);
    }

    protected function getInspectionFormData(?PneuPosicaoVeiculo $record): array
    {
        $ultimaInspecao = $record?->pneu?->ultimaInspecao;
        $ultimoSulcoInfo = collect([
            $ultimaInspecao?->sulco_interno,
            $ultimaInspecao?->sulco_centro,
            $ultimaInspecao?->sulco_externo,
        ])
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value) => number_format((float) $value, 2, ',', '.'))
            ->implode(' - ');

        return [
            'pneu_id' => $record?->pneu_id,
            'pneu_ciclo_id' => $record?->pneu_ciclo_id,
            'veiculo_id' => $record?->veiculo_id,
            'pneu_posicao_veiculo_id' => $record?->id,
            'pneu_info' => ($record?->pneu?->numero_fogo ?? 'N/A').' - '.($record?->pneu?->marcaCatalogo?->nome ?? 'N/A').' / '.($record?->pneu?->modeloCatalogo?->nome ?? 'N/A'),
            'veiculo_info' => $this->getRecord()->placa,
            'posicao_info' => $record ? $this->formatPosicaoTitle($record) : 'N/A',
            'medida_info' => $record?->pneu?->medidaCatalogo?->codigo ?? 'N/A',
            'ciclo_info' => (string) ($record?->pneu?->ciclo_vida ?? 'N/A'),
            'km_info' => number_format($record?->km_rodado ?? 0, 0, ',', '.'),
            'ultima_inspecao_info' => $ultimaInspecao?->data_inspecao?->format('d/m/Y') ?? 'Sem registro',
            'ultimo_resultado_info' => $ultimaInspecao?->resultado?->value ?? 'N/A',
            'ultimo_sulco_info' => $ultimoSulcoInfo !== '' ? $ultimoSulcoInfo : 'Sem registro',
            'tipo' => TipoInspecaoPneuEnum::CAMPO->value,
            'resultado' => null,
            'data_inspecao' => now()->toDateString(),
            'km_referencia' => $this->getRecord()->quilometragem_atual,
            'apto_recapagem' => null,
            'observacao' => null,
            'anexos' => [],
        ];
    }

    protected function makeKmField(string $name, string $label): TextInput
    {
        return TextInput::make($name)
            ->label($label)
            ->numeric()
            ->required()
            ->live(debounce: 700)
            ->helperText('Confira a quilometragem do veículo antes de confirmar a movimentação.');
    }

    protected function makeAnexosField(): FileUpload
    {
        return FileUpload::make('anexos')
            ->image()
            ->openable()
            ->downloadable()
            ->multiple()
            ->panelLayout('grid')
            ->disk('local')
            ->directory('pneus/movimentacoes')
            ->visibility('private')
            ->columnSpanFull();
    }

    protected function ensureOperationalPosition(PneuPosicaoVeiculo $posicao): bool
    {
        if (! $this->getRecord()->mapa_pneu_id || $posicao->mapa_pneu_posicao_id) {
            return true;
        }

        $this->notifyInvalidSelection('A posição ainda não foi sincronizada com o mapa do veículo. Salve o veículo novamente ou revise o mapa configurado.');

        return false;
    }

    protected function formatPosicaoCode(?PneuPosicaoVeiculo $posicao): string
    {
        return (string) ($posicao?->mapaPosicao?->codigo ?? $posicao?->posicao ?? 'N/A');
    }

    protected function formatPosicaoTitle(?PneuPosicaoVeiculo $posicao): string
    {
        if (! $posicao) {
            return 'N/A';
        }

        $label = $posicao->mapaPosicao?->nome ?? $posicao->posicao;
        $codigo = $posicao->mapaPosicao?->codigo ?? $posicao->posicao;

        return $posicao->eixo.'º eixo / '.$label.' ('.$codigo.')';
    }
}
