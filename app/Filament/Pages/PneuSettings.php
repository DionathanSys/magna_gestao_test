<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Inerba\DbConfig\AbstractPageSettings;

class PneuSettings extends AbstractPageSettings
{
    public ?array $data = [];

    protected static ?string $title = 'Pneu';

    // protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-wrench-screwdriver'; // Uncomment if you want to set a custom navigation icon

    // protected ?string $subheading = ''; // Uncomment if you want to set a custom subheading

    // protected static ?string $slug = 'pneu-settings'; // Uncomment if you want to set a custom slug

    protected string $view = 'filament.pages.pneu-settings';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

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
                            ->description('Esta configuração ficou legada após a normalização em tabelas.')
                            ->columns(12)
                            ->columnSpan(6)
                            ->schema([
                                Placeholder::make('marcas_notice')
                                    ->label('Cadastro Normalizado')
                                    ->content('Use o recurso Marcas de Pneu no menu de Pneus.'),
                            ]),
                        Section::make('Modelos de Pneu')
                            ->description('Esta configuração ficou legada após a normalização em tabelas.')
                            ->columns(12)
                            ->columnSpan(6)
                            ->schema([
                                Placeholder::make('modelos_notice')
                                    ->label('Cadastro Normalizado')
                                    ->content('Use o recurso Modelos de Pneu no menu de Pneus.'),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }
}
