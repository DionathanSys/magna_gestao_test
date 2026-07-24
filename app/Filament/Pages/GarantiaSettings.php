<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Inerba\DbConfig\AbstractPageSettings;

class GarantiaSettings extends AbstractPageSettings
{
    public ?array $data = [];

    protected static ?string $title = 'Garantia';

    protected string $view = 'filament.pages.garantia-settings';

    public static function getNavigationGroup(): ?string
    {
        return 'Configurações';
    }

    protected function settingName(): string
    {
        return 'config-garantia';
    }

    public function getDefaultData(): array
    {
        return [
            'garantia_km_default' => 10000,
            'garantia_dias_default' => 90,
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Section::make('Garantia de Serviços')
                    ->description('Limites usados quando o serviço não possui garantia própria cadastrada.')
                    ->columns(12)
                    ->columnSpan(6)
                    ->schema([
                        TextInput::make('garantia_km_default')
                            ->label('Garantia padrão em km')
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->columnSpan(6),
                        TextInput::make('garantia_dias_default')
                            ->label('Garantia padrão em dias')
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->columnSpan(6),
                    ]),
            ])
            ->statePath('data');
    }
}
