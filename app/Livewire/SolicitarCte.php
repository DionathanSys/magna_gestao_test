<?php

namespace App\Livewire;

use App\Enum\ClienteEnum;
use App\Jobs\CriarViagemBugioJob;
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
use App\Services\NotificacaoService as notify;
use Illuminate\Support\Facades\Auth;
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
                    ->columns(12)
                    ->columnSpanFull()
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
                            ->columnSpan(['md' => 3, 'xl' => 4])
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
                            ->columnSpan(['md' => 1, 'xl' => 2])
                            ->searchable()
                            ->preload()
                            ->options(fn() => collect(db_config('config-bugio.veiculos'))->pluck('placa', 'placa')->toArray())
                            ->required()
                            ->reactive(),
                        FileUpload::make('anexos')
                            ->columnSpan(['md' => 4, 'xl' => 6])
                            ->label('Anexos')
                            ->multiple()
                            ->maxFiles(10)
                            ->panelLayout('grid')
                            ->directory('cte')
                            ->visibility('private')
                            ->required(),
                        Repeater::make('data-integrados')
                            ->label('Integrados')
                            ->columns(['md' => 4, 'xl' => 6])
                            ->columnSpan(['md' => 2, 'xl' => 5])
                            ->defaultItems(1)
                            ->addActionLabel('Adicionar Integrado')
                            ->deletable(false)
                            ->minItems(1)
                            ->schema([
                                Select::make('integrado_id')
                                    ->label('Integrado')
                                    ->searchable()
                                    ->columnSpan(['md' => 2, 'xl' => 4])
                                    ->preload()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                    ->options(\App\Models\Integrado::query()
                                        ->where('cliente', ClienteEnum::BUGIO)
                                        ->pluck('nome', 'id'))
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, Get $get, ?string $state) {
                                        if ($state) {
                                            $kmRota = \App\Models\Integrado::find($state)?->km_rota;
                                            $kmTotal = $get('../../km_total') + ($kmRota ?? 0);
                                            $set('km_rota', $kmRota ?? 0);
                                            $set('../../km_total', number_format($kmTotal, 2, '.', ''));
                                            $set('../../valor_frete', number_format($this->calcularFrete($kmTotal), 2, '.', ''));
                                        } else {
                                            $kmTotal = $get('../../km_total') - $get('km_rota', 0);
                                            $set('../../km_total', number_format($kmTotal, 2, '.', ''));
                                            $set('../../valor_frete', number_format($this->calcularFrete($kmTotal), 2, '.', ''));
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

                                            $set('../../km_total', number_format($kmTotal, 2, '.', ''));
                                            $set('../../valor_frete', number_format($this->calcularFrete($kmTotal), 2, '.', ''));
                                        }
                                    })
                                    ->live(onBlur: true)
                            ]),
                    ]),

            ])
            ->statePath('data');
    }

    public function handle(): void
    {
        $data = $this->mutateData($this->data ?? []);

        Log::debug("dados do componente livewire", [
            'método' => __METHOD__ . '-' . __LINE__,
            'data' => $data,
        ]);

        $service = new CteService\CteService();
        $service->solicitarCtePorEmail($data);

        if ($service->hasError()) {
            notify::error('Erro ao enviar solicitação de CTe.');
            return;
        }

        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        CriarViagemBugioJob::dispatch($data);

        notify::success('Solicitação de CTe enviada com sucesso!');
        $this->resetForm();
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

    private function calcularFrete(float $kmTotal): float
    {
        $valorQuilometro = db_config('config-bugio.valor-quilometro', 0);

        return $valorQuilometro * $kmTotal;
    }

    private function resetForm(): void
    {
        $this->data = [];
        $this->form->fill();
    }

    public function render()
    {
        return view('livewire.solicitar-cte');
    }
}
