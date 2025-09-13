<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Inerba\DbConfig\AbstractPageSettings;
use Filament\Schemas\Components;
use Filament\Schemas\Schema;

class TesteSettings extends AbstractPageSettings
{
    public ?array $data = [];

    protected static ?string $title = 'Teste';

    // protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-wrench-screwdriver'; // Uncomment if you want to set a custom navigation icon

    // protected ?string $subheading = ''; // Uncomment if you want to set a custom subheading

    // protected static ?string $slug = 'teste-settings'; // Uncomment if you want to set a custom slug

    protected string $view = 'filament.pages.teste-settings';

    public static function getNavigationGroup(): ?string
    {
        return 'Cadastro';
    }

    protected function settingName(): string
    {
        return 'teste';
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
                Repeater::make('itens')
                    ->columnSpan(6)
                    ->schema([
                        TextInput::make('nome')
                            ->required(),
                        Select::make('ativo')
                            ->options([
                                'sim' => 'Sim',
                                'não' => 'Não',
                            ])
                            ->default('sim')
                            ->native(false)
                            ->required(),
                    ])
                    ->columns(2)
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Salvar')
                ->action(fn() => $this->save()),
        ];
    }
}
