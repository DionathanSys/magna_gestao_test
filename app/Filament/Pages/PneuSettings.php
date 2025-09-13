<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Inerba\DbConfig\AbstractPageSettings;
use Filament\Schemas\Components;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PneuSettings extends AbstractPageSettings
{
    public ?array $data = [];

    protected static ?string $title = 'Pneu';

    // protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-wrench-screwdriver'; // Uncomment if you want to set a custom navigation icon

    // protected ?string $subheading = ''; // Uncomment if you want to set a custom subheading

    // protected static ?string $slug = 'pneu-settings'; // Uncomment if you want to set a custom slug

    protected string $view = 'filament.pages.pneu-settings';

    public static function getNavigationGroup(): ?string
    {
        return 'Configurações';
    }

    protected function settingName(): string
    {
        return 'config-pneu';
    }

    /**
     * Provide default values.
     *
     * @return array<string, mixed>
     */
    public function getDefaultData(): array
    {
        return [];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Section::make('Configurações de Pneu')
                    ->columns(12)
                    ->columnSpanFull()
                    ->description('Configurações relacionadas a pneus.')
                    ->components([
                        Section::make('Marcas de Pneu')
                            ->description('Adicione as marcas de pneus disponíveis.')
                            ->columns(12)
                            ->columnSpan(6)
                            ->collapsible()
                            ->collapsed()
                            ->schema([
                                Repeater::make('marcas_pneu')
                                    ->label('Marcas de Pneu')
                                    ->grid(4)
                                    ->simple(
                                        TextInput::make('marca')
                                            ->label('Marca')
                                            ->required()
                                            ->columnSpanFull(),
                                    )
                                    ->columnSpanFull()
                                    ->addActionLabel('Adicionar Marca de Pneu')
                                    ->collapsible()
                                    ->collapsed(),
                            ]),
                        Section::make('Modelos de Pneu')
                            ->description('Adicione as marcas de pneus disponíveis.')
                            ->columns(12)
                            ->columnSpan(6)
                            ->collapsible()
                            ->collapsed()
                            ->schema([
                                Repeater::make('modelos_pneu')
                                    ->label('Modelos de Pneu')
                                    ->grid(4)
                                    ->simple(
                                        TextInput::make('modelo')
                                            ->label('Modelo')
                                            ->required()
                                            ->columnSpanFull(),
                                    )
                                    ->columnSpanFull()
                                    ->addActionLabel('Adicionar Modelo de Pneu')
                                    ->collapsible()
                                    ->collapsed(),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }
}
