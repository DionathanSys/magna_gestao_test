<?php

namespace App\Filament\Pages;

use App\Models\PlanoPreventivo;
use App\Models\Veiculo;
use App\Services\PlanoManutencao\RelatorioExecucoesPlanoPreventivoService;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use UnitEnum;

class RelatorioExecucoesPlanoPreventivo extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected string $view = 'filament.pages.relatorio-execucoes-plano-preventivo';

    protected static ?string $slug = 'relatorio-execucoes-planos-preventivos';

    protected static ?string $navigationLabel = 'Planos Preventivos Executados';

    protected static ?string $title = 'Planos Preventivos Executados';

    protected static string|UnitEnum|null $navigationGroup = 'Relatórios';

    public ?array $data = [];

    /**
     * @var array<int, array<string, int|float|string|null>>
     */
    public array $dadosRelatorio = [];

    public bool $buscaRealizada = false;

    public function mount(): void
    {
        $this->data = [
            'veiculo_id' => null,
            'plano_preventivo_id' => null,
            'data_de' => null,
            'data_ate' => null,
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Section::make('Filtros do relatório')
                    ->columns(4)
                    ->columnSpan(12)
                    ->description('Consulte todas as execuções registradas para um veículo, independentemente do status da ordem de serviço.')
                    ->components([
                        Select::make('veiculo_id')
                            ->label('Veículo')
                            ->options(fn (): array => Veiculo::query()
                                ->orderBy('placa')
                                ->pluck('placa', 'id')
                                ->all())
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('plano_preventivo_id')
                            ->label('Plano preventivo')
                            ->placeholder('Todos os planos')
                            ->options(fn (): array => PlanoPreventivo::query()
                                ->orderBy('descricao')
                                ->pluck('descricao', 'id')
                                ->all())
                            ->searchable()
                            ->preload(),
                        DatePicker::make('data_de')
                            ->label('Execução a partir de')
                            ->displayFormat('d/m/Y'),
                        DatePicker::make('data_ate')
                            ->label('Execução até')
                            ->displayFormat('d/m/Y'),
                    ]),
            ])
            ->statePath('data');
    }

    public function carregarDados(): void
    {
        try {
            $this->dadosRelatorio = app(RelatorioExecucoesPlanoPreventivoService::class)
                ->obterDados($this->data ?? []);
            $this->buscaRealizada = true;

            Notification::make()
                ->success()
                ->title('Dados carregados')
                ->body(count($this->dadosRelatorio).' execução(ões) encontrada(s).')
                ->send();
        } catch (\Throwable $exception) {
            $this->dadosRelatorio = [];
            $this->buscaRealizada = false;

            Notification::make()
                ->danger()
                ->title('Erro ao carregar relatório')
                ->body($exception->getMessage())
                ->send();
        }
    }

    public function gerarPdf(): mixed
    {
        try {
            $dados = app(RelatorioExecucoesPlanoPreventivoService::class)
                ->obterDados($this->data ?? []);

            if ($dados === []) {
                Notification::make()
                    ->warning()
                    ->title('Nenhuma execução encontrada')
                    ->body('Ajuste os filtros antes de gerar o PDF.')
                    ->send();

                return null;
            }

            $pdf = Pdf::loadView('pdf.relatorio-execucoes-plano-preventivo', [
                'dados' => $dados,
                'dataGeracao' => now()->format('d/m/Y H:i:s'),
            ])->setPaper('A4', 'landscape');

            return response()->streamDownload(
                fn () => print ($pdf->output()),
                'relatorio-planos-preventivos-executados-'.date('Y-m-d-H-i').'.pdf',
                ['Content-Type' => 'application/pdf']
            );
        } catch (\Throwable $exception) {
            Notification::make()
                ->danger()
                ->title('Erro ao gerar PDF')
                ->body($exception->getMessage())
                ->send();

            return null;
        }
    }
}
