<?php

namespace App\Filament\Resources\Checklists\Schemas;

use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
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
                                    ->default(now())
                                    ->required(),
                                TextInput::make('quilometragem')
                                    ->columnSpan(3)
                                    ->required()
                                    ->numeric()
                                    ->default(0),
                            ]),
                        Tabs\Tab::make('Checklist')
                            ->columns(12)
                            ->schema([
                                Repeater::make('itens')
                                    ->label('Itens')
                                    ->columns(12)
                                    ->columnSpan(6)
                                    ->defaultItems(self::getCountItens())
                                    ->collapsed()
                                    ->itemLabel(fn(array $state): ?string => $state['status'] . ' ' . $state['item'])
                                    ->schema([
                                        TextInput::make('item')
                                            ->label('Item')
                                            ->required()
                                            ->columnSpan(4),
                                        Select::make('status')
                                            ->label('OK')
                                            ->columnSpan(2)
                                            ->placeholder('Selecione')
                                            ->selectablePlaceholder(false)
                                            ->requiredIf('obrigatorio', true)
                                            ->boolean(trueLabel: 'OK', falseLabel: 'NOK'),
                                        Toggle::make('corrigido')
                                            ->label('Corrigido')
                                            ->inline(false)
                                            ->default(false)
                                            ->columnSpan(1),
                                        Hidden::make('obrigatorio')
                                            ->label('Obrigatório')
                                            ->columnSpan(1),
                                        Textarea::make('observacoes')
                                            ->label('Observações')
                                            ->columnSpanFull()
                                            ->rows(2),

                                    ])
                                    ->default(fn() => self::getItens())
                                    ->deletable(false)
                                    ->addable(false)
                                    ->extraItemActions([
                                        Action::make('ok')
                                            ->icon(Heroicon::CheckCircle)
                                            ->color('info')
                                            // ->visible(fn(array $arguments): bool => $arguments['item']['status'] !== 'OK')
                                            ->action(function (array $arguments, Repeater $component): void {
                                                $state = $component->getState();
                                                $state[$arguments['item']]['status'] = 'OK';
                                                $component->state($state);
                                            }),
                                        Action::make('nok')
                                            ->icon(Heroicon::XCircle)
                                            ->color('danger')
                                            // ->visible(fn(array $item): bool => $item['status'] !== 'NOK')
                                            ->action(function (array $arguments, Repeater $component): void {
                                                $state = $component->getState();
                                                $state[$arguments['item']]['status'] = 'NOK';
                                                $component->state($state);
                                            }),
                                    ]),
                            ]),
                        Tabs\Tab::make('Anexos')
                            ->schema([
                                FileUpload::make('anexos')
                                    ->label('Anexos')
                                    ->multiple()
                                    ->disk('public')
                                    ->directory('checklists')
                                    ->columnSpanFull(),
                            ])
                    ])
            ]);;
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
