<?php

namespace App\Filament\Resources\Checklists\Schemas;

use Dom\Text;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ChecklistForm
{

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Tabs::make('tabs')
                    ->columnSpanFull()
                    ->tabs([
                        Tabs\Tab::make('Info Veículo')
                            ->columns(12)
                            ->schema([
                                Select::make('veiculo_id')
                                    ->required()
                                    ->relationship('veiculo', 'placa')
                                    ->columnSpan(3),
                                DatePicker::make('data_referencia')
                                    ->label('Data Realização')
                                    ->columnSpan(3)
                                    ->required(),
                                TextInput::make('quilometragem')
                                    ->columnSpan(3)
                                    ->required()
                                    ->numeric()
                                    ->default(0),
                                FileUpload::make('anexos')
                                    ->columnSpanFull(),
                            ]),
                        Tabs\Tab::make('Checklist')
                            ->columns(12)
                            ->schema([
                                Repeater::make('itens_verificados')
                                    ->label('Itens Verificados')
                                    ->columns(12)
                                    ->columnSpan(6)
                                    ->defaultItems(self::getCountItens())
                                    ->collapsed()
                                    ->itemLabel(fn(array $state): ?string => $state['item'] ? $state['item'] . ($state['status'] ? ' - ' . $state['status'] : '') : 'Vazio')
                                    ->schema([
                                        TextInput::make('item')
                                            ->label('Item')
                                            ->required()
                                            ->columnSpan(4),
                                        Select::make('status')
                                            ->label('Status')
                                            ->columnSpan(1)
                                            ->placeholder('Selecione')
                                            ->options([
                                                'OK' => 'OK',
                                                'NOK' => 'NOK',
                                            ]),
                                        Toggle::make('obrigatorio')
                                            ->label('Obrigatório')
                                            ->columnSpan(1)
                                            ->default(true)
                                            ->inline(false)
                                            ->disabled(),
                                        Textarea::make('observacoes')
                                            ->label('Observações')
                                            ->columnSpanFull()
                                            ->rows(2),

                                    ])
                                    ->default(fn() => self::getItens())
                                    ->extraItemActions([
                                        Action::make('ok')
                                            ->icon(Heroicon::CheckCircle)
                                            ->color('info')
                                            ->action(function (array $arguments, Repeater $component): void {
                                                $state = $component->getState();
                                                $state[$arguments['item']]['status'] = 'OK';
                                                $component->state($state);
                                                ds($component->getItemState($arguments['item']));
                                            }),
                                    ]),
                            ])

                    ])
            ])
        ;
    }

    public static function getCountItens(): int
    {
        return count(db_config('config-checklist.itens', []));
    }

    public static function getItens(): array
    {
        $itens = collect(db_config('config-checklist.itens', []))
            ->map(fn($item) => [
                'item' => $item['item'] ?? 'Erro ao carregar item',
                'status' => $item['obrigatorio'] ? 'NOK' : null,
                'obrigatorio' => $item['obrigatorio'] ?? true,
                'observacoes' => null,
            ])
            ->toArray();

        return $itens;
    }
}
