<?php

namespace App\Filament\Pages;

use App\Models\PlanoPreventivo;
use App\Models\Veiculo;
use App\Services\PlanoManutencao\RelatorioPlanoManutencaoService;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Log;
use UnitEnum;

class RelatorioPlanoManutencao extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected string $view = 'filament.pages.relatorio-plano-manutencao';

    protected static ?string $navigationLabel = 'Relatório Plano Manutenção';

    protected static ?string $title = 'Relatório Plano de Manutenção';

    protected static string|UnitEnum|null $navigationGroup = 'Relatórios';

    public ?array $data = [];

    public ?array $dadosRelatorio = [];

    public function mount(): void
    {
        $this->data = $this->getDefaultData();
    }

    public function getDefaultData(): array
    {
        return [
            'veiculo_id' => null,
            'plano_preventivo_id' => null,
            'km_restante_maximo' => null,
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Section::make('Filtros do Relatório')
                    ->columns(3)
                    ->columnSpan(12)
                    ->description('Selecione os filtros desejados para gerar o relatório de plano de manutenção')
                    ->components([
                        Select::make('veiculo_id')
                            ->label('Veículo')
                            ->placeholder('Todos os veículos ativos')
                            ->options(
                                Veiculo::query()
                                    ->where('is_active', true)
                                    ->orderBy('placa')
                                    ->pluck('placa', 'id')
                            )
                            ->searchable()
                            ->preload()
                            ->multiple(),

                        Select::make('plano_preventivo_id')
                            ->label('Plano Preventivo')
                            ->placeholder('Todos os planos ativos')
                            ->options(
                                PlanoPreventivo::query()
                                    ->where('is_active', true)
                                    ->orderBy('descricao')
                                    ->pluck('descricao', 'id')
                            )
                            ->searchable()
                            ->preload()
                            ->multiple(),

                        TextInput::make('km_restante_maximo')
                            ->label('KM Restante Máximo')
                            ->placeholder('Ex: 5000')
                            ->numeric()
                            ->suffix('km')
                            ->helperText('Deixe vazio para trazer todos')
                            ->minValue(0),
                    ]),
            ])
            ->statePath('data');
    }

    public function gerarRelatorio()
    {
        try {
            $service = new RelatorioPlanoManutencaoService();

            $filtros = [
                'veiculo_id' => $this->data['veiculo_id'] ?? null,
                'plano_preventivo_id' => $this->data['plano_preventivo_id'] ?? null,
                'km_restante_maximo' => $this->data['km_restante_maximo'] ?? null,
            ];

            Log::info('Gerando relatório de plano de manutenção com filtros: ', $filtros);

            return $service->gerarRelatorio($filtros);
        } catch (\Exception $e) {
            Log::error('Erro ao gerar relatório de plano de manutenção: ' . $e->getMessage());

            Notification::make()
                ->title('Erro ao gerar relatório')
                ->danger()
                ->body($e->getMessage())
                ->send();

            return response('Erro ao gerar relatório', 500);
        }
    }

    public function visualizarRelatorio()
    {
        try {
            $service = new RelatorioPlanoManutencaoService();

            $filtros = [
                'veiculo_id' => $this->data['veiculo_id'] ?? null,
                'plano_preventivo_id' => $this->data['plano_preventivo_id'] ?? null,
                'km_restante_maximo' => $this->data['km_restante_maximo'] ?? null,
            ];

            return $service->visualizarRelatorio($filtros);
        } catch (\Exception $e) {
            Log::error('Erro ao visualizar relatório de plano de manutenção: ' . $e->getMessage());

            Notification::make()
                ->title('Erro ao visualizar relatório')
                ->danger()
                ->body($e->getMessage())
                ->send();

            return response('Erro ao visualizar relatório', 500);
        }
    }

    public function carregarDados()
    {
        try {
            $service = new RelatorioPlanoManutencaoService();

            $filtros = [
                'veiculo_id' => $this->data['veiculo_id'] ?? null,
                'plano_preventivo_id' => $this->data['plano_preventivo_id'] ?? null,
                'km_restante_maximo' => $this->data['km_restante_maximo'] ?? null,
            ];

            $this->dadosRelatorio = $service->obterDadosRelatorio($filtros);

            Notification::make()
                ->title('Dados carregados com sucesso')
                ->success()
                ->body(count($this->dadosRelatorio) . ' registro(s) encontrado(s)')
                ->send();
        } catch (\Exception $e) {
            Log::error('Erro ao carregar dados do relatório: ' . $e->getMessage());

            Notification::make()
                ->title('Erro ao carregar dados')
                ->danger()
                ->body($e->getMessage())
                ->send();

            $this->dadosRelatorio = [];
        }
    }
}
