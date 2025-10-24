<?php

namespace App\Filament\Pages;

use BackedEnum;
use Inerba\DbConfig\AbstractPageSettings;
use Filament\Schemas\Components;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class VeiculoSettings extends AbstractPageSettings
{
    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    protected static ?string $title = 'Veiculo';

    // protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-wrench-screwdriver'; // Uncomment if you want to set a custom navigation icon

    // protected ?string $subheading = ''; // Uncomment if you want to set a custom subheading

    // protected static ?string $slug = 'veiculo-settings'; // Uncomment if you want to set a custom slug

    protected string $view = 'filament.pages.veiculo-settings';

    public static function getNavigationGroup(): ?string
    {
        return 'Configurações';
    }

    protected function settingName(): string
    {
        return 'config-veiculo';
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

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
            ])
            ->statePath('data');
    }
}
