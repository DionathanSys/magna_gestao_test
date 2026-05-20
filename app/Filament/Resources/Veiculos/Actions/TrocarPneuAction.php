<?php

namespace App\Filament\Resources\Veiculos\Actions;

use App\Enum;
use App\Models;
use App\Models\PneuPosicaoVeiculo;
use App\Services;
use App\Services\NotificacaoService as notify;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Icon;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class TrocarPneuAction
{
    public static function make(): Action
    {
        return Action::make('trocar-pneu')
            ->icon('heroicon-o-arrows-right-left')
            ->iconButton()
            ->tooltip('Substituir Pneu')
            ->visible(fn ($record) => ! $record->pneu_id == null)
            ->modalWidth(Width::ExtraLarge)
            ->schema(fn (Schema $schema) => $schema
                ->columns(8)
                ->schema([
                    Select::make('motivo')
                        ->columnSpan(5)
                        ->options(Enum\Pneu\MotivoMovimentoPneuEnum::toSelectArray())
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
                        ->getSearchResultsUsing(fn (string $search): array => (new Services\Pneus\PneuService)->getPneusDisponiveis($search))
                        ->getOptionLabelUsing(fn ($value): ?string => Models\Pneu::find($value)?->numero_fogo)
                        ->searchable()
                        ->searchDebounce(700)
                        ->required(),
                    DatePicker::make('data_movimento')
                        ->label('Dt. Movimento')
                        ->columnSpan(4)
                        ->date('d/m/Y')
                        ->default(now())
                        ->maxDate(now())
                        ->required(),
                    TextInput::make('km_movimento')
                        ->label('Km Movimento')
                        ->columnSpan(4)
                        ->numeric()
                        ->required()
                        ->live(debounce: 700)
                        ->afterStateUpdated(function (PneuPosicaoVeiculo $record, Field $component, $state) {
                            $limites = Services\Veiculo\VeiculoService::getQuilometragemLimiteMovimentacao($record->veiculo_id);
                            if ($state < $limites['km_minimo'] || $state > $limites['km_maximo']) {
                                $component->belowContent([
                                    Icon::make(Heroicon::InformationCircle)->color(Color::Indigo),
                                    Text::make('Verifique a quilometragem.')->weight(FontWeight::Bold)->color(Color::Amber),
                                ]);
                            }
                        }),
                    Textarea::make('observacao')
                        ->label('Observação')
                        ->columnSpanFull()
                        ->maxLength(255),
                    FileUpload::make('anexos')
                        ->image()
                        ->openable()
                        ->downloadable()
                        ->multiple()
                        ->panelLayout('grid')
                        ->disk('local')
                        ->directory('pneus/movimentacoes')
                        ->visibility('private')
                        ->columnSpanFull(),
                ]))
            ->action(function (Action $action, array $data, PneuPosicaoVeiculo $record) {
                try {
                    (new Services\Pneus\MovimentarPneuService)->trocarPneu($record, $data);
                } catch (\Throwable $e) {
                    notify::error(titulo: 'Falha ao substituir pneu', mensagem: $e->getMessage());
                    $action->halt();
                }
            });
    }
}
