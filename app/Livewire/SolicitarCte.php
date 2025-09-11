<?php

namespace App\Livewire;

use App\Enum\ClienteEnum;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Livewire\Component;
use App\Services\CteService;
use BackedEnum;
use Illuminate\Support\Facades\Log;

class SolicitarCte extends Component implements HasSchemas, HasActions
{

    use InteractsWithActions;
    use InteractsWithSchemas;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Section::make('Detalhes do Frete')
                    ->columns(['md' => 4, 'xl' => 6])
                    ->columnSpan(['md' => 2, 'xl' => 5])
                    ->schema([
                        TextInput::make('km_total')
                            ->label('KM Total')
                            ->columnStart(1)
                            ->columnSpan(['md' => 1, 'xl' => 2])
                            ->numeric()
                            ->required()
                            ->readOnly()
                            ->default(0)
                            ->minValue(0)
                            ->reactive(),
                        TextInput::make('valor_frete')
                            ->label('Valor do Frete')
                            ->columnSpan(['md' => 1, 'xl' => 2])
                            ->prefix('R$')
                            ->disabled()
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->reactive(),
                        Select::make('motorista')
                            ->label('Motorista')
                            ->columnSpan(['md' => 2, 'xl' => 4])
                            ->searchable()
                            ->preload()
                            ->options(fn() => collect(db_config('config-bugio.motoristas'))->pluck('motorista', 'cpf')->toArray())
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function (Set $set, Get $get, ?string $state) {
                                if ($state) {
                                    $placa = collect(db_config('config-bugio.motoristas'))
                                        ->firstWhere('cpf', $state)['placa'] ?? null;
                                    $set('veiculo', $placa);
                                } else {
                                    $set('veiculo', null);
                                }
                            }),
                        Select::make('veiculo')
                            ->label('Veículo')
                            ->columnSpan(['md' => 2, 'xl' => 4])
                            ->searchable()
                            ->preload()
                            ->options(fn() => collect(db_config('config-bugio.veiculos'))->pluck('placa', 'placa')->toArray())
                            ->required()
                            ->reactive(),
                        FileUpload::make('anexos')
                            ->label('Anexos')
                            ->columnSpan(['md' => 2, 'xl' => 4])
                            ->multiple()
                            ->maxFiles(10)
                            ->directory('cte')
                            ->visibility('public')
                            ->required()
                            ->downloadable()
                    ]),
                Repeater::make('data-integrados')
                    ->label('Integrados')
                    ->columns(['md' => 4, 'xl' => 6])
                    ->columnSpan(['md' => 2, 'xl' => 5])
                    ->defaultItems(1)
                    ->addActionLabel('Adicionar Integrado')
                    ->schema([
                        Select::make('integrado_id')
                            ->label('Integrado')
                            ->searchable()
                            ->columnSpan(['md' => 2, 'xl' => 4])
                            ->preload()
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                            ->options(\App\Models\Integrado::query()
                                ->where('cliente', ClienteEnum::BUGIU)
                                ->pluck('nome', 'id'))
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get, ?string $state) {
                                if ($state) {
                                    $kmRota = \App\Models\Integrado::find($state)?->km_rota;
                                    $kmTotal = $get('../../km_total') + ($kmRota ?? 0);
                                    $set('km_rota', $kmRota ?? 0);
                                    $set('../../km_total', $kmTotal);
                                } else {
                                    $set('km_rota', 0);
                                }
                            }),
                        TextInput::make('km_rota')
                            ->label('KM Rota')
                            ->columnSpan(['md' => 1, 'xl' => 2])
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->default(0)
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state, ?string $old) {
                                if ($state !== $old) {
                                    $kmTotal = $get('../../km_total') - ($old ?? 0) + ($state ?? 0);
                                    $set('../../km_total', $kmTotal);
                                }
                            })
                            ->live(onBlur: true)
                    ]),

            ])
            ->statePath('data');
    }

    public function handle(): void
    {

        $data = $this->mutateData($this->data ?? []);

        Log::debug(__METHOD__ . '-' . __LINE__, [
            'data' => $data,
        ]);
        
        $service = new CteService\CteService();
        $service->solicitarCtePorEmail($data);
    }

    private function mutateData(array $data): array
    {
        Log::debug(__METHOD__ . '-' . __LINE__, [
            'data' => $data,
        ]);

        $data['integrados'] = $data['data-integrados'];
        $data['veiculo'] = $data['veiculo'] ?? null;
        $data['motorista'] = [
            'cpf' => $data['motorista'] ?? null,
        ];

        // Remover dados desnecessários
        unset($data['data-integrados']);

        return $data;
    }

    public function render()
    {
        return view('livewire.solicitar-cte');
    }
}
