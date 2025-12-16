<?php

namespace App\Livewire;

use App\Enum\ClienteEnum;
use App\Jobs\CriarViagemBugioJob;
use App\Jobs\SolicitarCteBugio;
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
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

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
                        TextInput::make('nro_notas')
                            ->label('Nº de Notas Fiscais')
                            ->required()
                            ->columnSpan(['md' => 1, 'xl' => 2]),
                        FileUpload::make('anexos')
                            ->columnSpan(['md' => 4, 'xl' => 6])
                            ->label('Anexos')
                            ->multiple()
                            ->maxFiles(10)
                            ->preserveFilenames()
                            ->directory('cte')
                            ->visibility('private')
                            ->required(),
                        Repeater::make('data-integrados')
                            ->label('Integrados')
                            ->columns(['md' => 4, 'xl' => 6])
                            ->columnSpan(['md' => 2, 'xl' => 5])
                            ->defaultItems(1)
                            ->addActionLabel('Adicionar Integrado')
                            ->deletable(true)
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
                                            $integrado = \App\Models\Integrado::find($state);
                                            $kmRota = $integrado?->km_rota;
                                            $municipio = $integrado?->municipio;
                                            $kmTotal = $get('../../km_total') + ($kmRota ?? 0);
                                            $set('km_rota', $kmRota ?? 0);
                                            $set('municipio', $municipio ?? '');
                                            $set('../../km_total', number_format($kmTotal, 2, '.', ''));
                                            $set('../../valor_frete', number_format($this->calcularFrete($kmTotal), 2, '.', ''));
                                        } else {
                                            $kmTotal = $get('../../km_total') - $get('km_rota', 0);
                                            $set('../../km_total', number_format($kmTotal, 2, '.', ''));
                                            $set('../../valor_frete', number_format($this->calcularFrete($kmTotal), 2, '.', ''));
                                            $set('km_rota', 0);
                                            $set('municipio', null);
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
                                    ->live(onBlur: true),
                                TextInput::make('municipio')
                                    ->label('Municipio')
                                    ->columnSpanFull()
                                    ->readOnly(),
                            ]),
                    ]),

            ])
            ->statePath('data');
    }

    public function handle(): void
    {

        $data = $this->mutateData($this->data ?? []);

        if (!$this->validateData($data)) {
            notify::error('Dados inválidos. Verifique os campos e tente novamente.');
            return;
        }

        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();
        $data['status']     = 'pendente';

        SolicitarCteBugio::dispatch($data);

        unset($data['anexos']);

        // CriarViagemBugioJob::dispatch($data);

        notify::success('Solicitação de CTe enviada com sucesso!');

        $this->resetForm();
    }

    private function validateData(array $data): bool
    {
        $validator = Validator::make($data, [
            'km_total'                   => 'required|numeric|min:0',
            'valor_frete'                => 'required|numeric|min:0',
            'motorista.cpf'              => 'required|string',
            'veiculo'                    => 'required|string|exists:veiculos,placa',
            'anexos'                     => 'required|array|min:1',
            'destinos'                   => 'required|array|min:1',
            'destinos.*.integrado_id'    => 'required|integer|exists:integrados,id',
            'destinos.*.km_rota'         => 'required|numeric|min:0',
        ], [
            'km_total.required'            => "O campo 'KM Total' é obrigatório.",
            'valor_frete.required'         => "O campo 'Valor do Frete' é obrigatório.",
            'motorista.cpf.required'       => "O campo 'Motorista' é obrigatório.",
            'veiculo.required'             => "O campo 'Veículo' é obrigatório.",
            'veiculo.exists'               => "O veículo selecionado não foi encontrado.",
            'anexos.required'              => "O campo 'Anexos' é obrigatório.",
            'anexos.array'                 => "O campo 'Anexos' deve ser um array.",
            'anexos.min'                   => "É necessário anexar pelo menos 1 arquivo.",
            'destinos.required'            => "O campo 'Integrados' é obrigatório.",
            'destinos.array'               => "O campo 'Integrados' deve ser um array.",
            'destinos.min'                 => "É necessário adicionar pelo menos 1 destino.",
            'destinos.*.integrado_id.required'    => "O campo 'Integrado' é obrigatório em cada item.",
            'destinos.*.integrado_id.exists'      => "O integrado selecionado não foi encontrado em cada item.",
            'destinos.*.km_rota.required'         => "O campo 'KM Rota' é obrigatório em cada item.",
        ]);

        if ($validator->fails()) {
            Log::warning('validação de dados falhou', [
                'método' => __METHOD__ . '-' . __LINE__,
                'errors' => $validator->errors()->all(),
                'data' => $data,
            ]);

            foreach ($validator->errors()->all() as $error) {
                notify::error($error);
            }

            return false;
        }

        // Validação adicional: verificar tipos de arquivos (PDF e XML obrigatórios)
        if (!$this->validarTiposAnexos($data['anexos'] ?? [])) {
            return false;
        }

        return true;
    }

    /**
     * Validar se existe pelo menos 1 PDF e 1 XML nos anexos
     */
    private function validarTiposAnexos(array $anexos): bool
    {
        if (empty($anexos)) {
            notify::error('Pelo menos um anexo deve ser enviado.');
            return false;
        }

        $hasPdf = false;
        $hasXml = false;
        $tiposEncontrados = [];

        foreach ($anexos as $anexo) {
            // Pegar extensão do arquivo
            $extension = strtolower(pathinfo($anexo, PATHINFO_EXTENSION));
            $tiposEncontrados[] = $extension;

            if ($extension === 'pdf') {
                $hasPdf = true;
            }

            if (in_array($extension, ['xml', 'txt'])) {
                $hasXml = true;
            }
        }

        // Validar se tem pelo menos 1 PDF
        if (!$hasPdf) {
            notify::error('É obrigatório enviar pelo menos 1 arquivo PDF.');
            Log::warning('Validação falhou: PDF não encontrado', [
                'anexos' => $anexos,
                'tipos_encontrados' => $tiposEncontrados,
            ]);
            return false;
        }

        // Validar se tem pelo menos 1 XML
        if (!$hasXml) {
            notify::error('É obrigatório enviar pelo menos 1 arquivo XML.');
            Log::warning('Validação falhou: XML não encontrado', [
                'anexos' => $anexos,
                'tipos_encontrados' => $tiposEncontrados,
            ]);
            return false;
        }

        return true;
    }

    private function mutateData(array $data): array
    {
        $data['destinos']           = $this->mutateDestinos($data['data-integrados']);
        $data['veiculo']            = $data['veiculo'] ?? null;
        $data['veiculo_id']         = \App\Models\Veiculo::where('placa', $data['veiculo'])->first()?->id ?? null;
        $data['km_pago']            = $data['km_total'] ?? 0;
        $data['km_rodado']          = 0;
        $data['data_competencia']   = now()->format('Y-m-d');
        $data['frete']              = $data['valor_frete'] ?? 0.0;
        $data['condutor']           = collect(db_config('config-bugio.motoristas'))->firstWhere('cpf', $data['motorista'] ?? null)['motorista'] ?? null;
        $data['motorista']          = [
            'cpf' => $data['motorista'] ?? null,
        ];

        $data['anexos'] = $this->processarAnexos($data['anexos'] ?? []);

        // Remover dados desnecessários
        unset($data['data-integrados']);

        return $data;
    }

    private function mutateDestinos(array $destinos)
    {
        $destinosProcessados = [];

        foreach ($destinos as $destino) {
            $destinosProcessados[] = [
                'integrado_id'    => $destino['integrado_id'],
                'km_rota'         => $destino['km_rota'],
                'integrado_nome'  => \App\Models\Integrado::find($destino['integrado_id'])?->nome ?? 'N/A',
            ];
        }
        return $destinosProcessados;
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

    /**
     * Processar arquivos temporários do Livewire
     * 
     * @param array $anexos
     * @return array Array com paths dos arquivos salvos
     */
    private function processarAnexos(array $anexos): array
    {
        $arquivosSalvos = [];

        foreach ($anexos as $key => $anexo) {
            // Verificar se é um objeto TemporaryUploadedFile
            if ($anexo instanceof TemporaryUploadedFile) {
                // Mover arquivo temporário para storage permanente
                $path = $anexo->store('private/cte');
                $arquivosSalvos[] = $path;
            } elseif (is_string($anexo)) {
                // Se já for uma string (path), apenas adicionar
                $arquivosSalvos[] = $anexo;
            } else {
                Log::warning('Tipo de anexo desconhecido', [
                    'key' => $key,
                    'type' => gettype($anexo),
                    'value' => $anexo,
                ]);
            }
        }

        return $arquivosSalvos;
    }
}
