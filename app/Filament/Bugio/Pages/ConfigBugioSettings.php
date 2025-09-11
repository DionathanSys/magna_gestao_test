<?php

namespace App\Filament\Bugio\Pages;

use App\Models;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Inerba\DbConfig\AbstractPageSettings;
use Filament\Schemas\Components;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Inerba\DbConfig\DbConfig;
use App\Services\NotificacaoService as notify;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Facades\Auth;

class ConfigBugioSettings extends AbstractPageSettings
{
    public ?array $data = [];

    protected static ?string $title = 'Configurações';

    // protected static string | BackedEnum | null $navigationIcon = 'document-plus'; // Uncomment if you want to set a custom navigation icon

    protected ?string $subheading = 'Configurações Painel Bugio'; // Uncomment if you want to set a custom subheading

    // protected static ?string $slug = 'config-bugio-settings'; // Uncomment if you want to set a custom slug

    protected string $view = 'filament.pages.config-bugio-settings';

    public static function getNavigationGroup(): ?string
    {
        return null;
    }

    protected function settingName(): string
    {
        return 'config-bugio';
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
                Section::make('Configurações de Email')
                    ->columnSpanFull()
                    ->columns(12)
                    ->schema([
                        TextInput::make('email')
                            ->label('Email Emissor CTe')
                            ->disabled(fn(): bool => !Auth::user()->is_admin)
                            ->columnSpan(6)
                            ->email()
                            ->autocomplete(false)
                            ->required(),
                        TextInput::make('email-retorno')
                            ->label('Email para Retorno')
                            ->columnSpan(6)
                            ->columnStart(1)
                            ->email()
                            ->autocomplete(false)
                            ->required(),
                        Repeater::make('emails-copia')
                            ->label('Email\'s em Cópia')
                            ->addActionLabel('Incluir Email em Cópia')
                            ->deletable(fn(): bool => Auth::user()->is_admin)
                            ->defaultItems(1)
                            ->columnSpan(6)
                            ->columnStart(1)
                            ->simple(
                                TextInput::make('email')
                                    ->columnSpanFull()
                                    ->email()
                                    ->required()
                                    ->afterStateUpdated(function (Set $set, ?string $state, ?string  $old) {
                                        if($old != Auth::user()->email) {
                                            $set('email', strtolower($old));
                                        }
                                    })
                                    ->autocomplete(false)

                            ),
                    ]),
                Section::make('Cadastro Motorista/Veículo')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('motoristas')
                            ->label('Motoristas')
                            ->addActionLabel('Incluir Motorista')
                            ->columns(12)
                            ->columnSpan(6)
                            ->collapsible()
                            ->collapsed()
                            ->schema([
                                TextInput::make('motorista')
                                    ->label('Motorista Padrão')
                                    ->columnSpan(6)
                                    ->columnStart(1)
                                    ->required(),
                                TextInput::make('cpf')
                                    ->label('Nº CPF')
                                    ->columnSpan(3)
                                    ->required(),
                                Select::make('placa')
                                    ->label('Placa')
                                    ->options(Models\Veiculo::query()
                                        ->pluck('placa', 'placa')
                                        ->toArray())
                                    ->searchable()
                                    ->columnSpan(3)
                                    ->required(),
                            ]),
                        Repeater::make('veiculos')
                            ->label('Veículos')
                            ->addActionLabel('Incluir Veículo')
                            ->columns(12)
                            ->columnSpan(6)
                            ->collapsible()
                            ->collapsed()
                            ->schema([
                                Select::make('placa')
                                    ->label('Placa')
                                    ->options(Models\Veiculo::query()
                                        ->pluck('placa', 'placa')
                                        ->toArray())
                                    ->searchable()
                                    ->columnSpan(3)
                                    ->columnStart(1)
                                    ->required(),
                            ]),
                    ]),
                Section::make('Configurações do Frete')
                    ->columnSpanFull()
                    ->columns(12)
                    ->schema([
                        TextInput::make('valor-quilometro')
                            ->label('R$/Km')
                            ->disabled(fn(): bool => !Auth::user()->is_admin)
                            ->columnStart(1)
                            ->columnSpan(2)
                            ->numeric()
                            ->prefix('R$')
                            ->default(0.01)
                            ->minValue(0.01)
                            ->required(),
                    ])
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

    public function save(): void
    {
        /** @var array<string,mixed> $state */
        $state = $this->content->getState();

        collect($state)->each(function ($setting, $key) {
            DbConfig::set($this->settingName() . '.' . $key, $setting);
        });

        notify::success('Configurações salvas com sucesso!');
    }
}
