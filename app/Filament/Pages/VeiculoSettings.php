<?php

namespace App\Filament\Pages;

use App\Models\VeiculoDocumento;
use BackedEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Inerba\DbConfig\AbstractPageSettings;

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
        return [
            'alertas_documentos' => [],
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Section::make('Alertas de Documentos de Veículos')
                    ->description('Configure quais tipos de documentos serão alertados, por unidade, e quem receberá os e-mails.')
                    ->columns(12)
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('alertas_documentos')
                            ->label('Regras de alerta')
                            ->addActionLabel('Adicionar regra')
                            ->columns(12)
                            ->columnSpanFull()
                            ->collapsible()
                            ->itemLabel(function (array $state): ?string {
                                $tipo = VeiculoDocumento::tipoOptions()[$state['tipo'] ?? null] ?? 'Tipo não definido';
                                $unidades = implode(', ', $state['unidades'] ?? []);

                                return trim($tipo.' - '.$unidades, ' -');
                            })
                            ->schema([
                                Toggle::make('ativo')
                                    ->label('Ativo')
                                    ->default(true)
                                    ->columnSpan(1),
                                Select::make('tipo')
                                    ->label('Tipo de Documento')
                                    ->options(VeiculoDocumento::tipoOptions())
                                    ->native(false)
                                    ->required()
                                    ->columnSpan(3),
                                Select::make('unidades')
                                    ->label('Unidades')
                                    ->options([
                                        'CATANDUVAS' => 'Catanduvas',
                                        'CHAPECO' => 'Chapecó',
                                        'CONCORDIA' => 'Concórdia',
                                    ])
                                    ->multiple()
                                    ->native(false)
                                    ->required()
                                    ->columnSpan(4)
                                    ->helperText('Usa a filial cadastrada no veículo.'),
                                Repeater::make('emails')
                                    ->label('E-mails')
                                    ->addActionLabel('Adicionar e-mail')
                                    ->simple(
                                        TextInput::make('email')
                                            ->email()
                                            ->required()
                                            ->autocomplete(false)
                                    )
                                    ->minItems(1)
                                    ->columnSpan(4),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }
}
