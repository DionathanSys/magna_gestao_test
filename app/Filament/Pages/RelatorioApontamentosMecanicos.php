<?php

namespace App\Filament\Pages;

use App\Models\Colaborador;
use App\Services\Oficina\RelatorioApontamentosMecanicosService;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;
use UnitEnum;

class RelatorioApontamentosMecanicos extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected string $view = 'filament.pages.relatorio-apontamentos-mecanicos';

    protected static ?string $navigationLabel = 'Apontamentos Mecânicos';

    protected static ?string $title = 'Relatório de Apontamentos dos Mecânicos';

    protected static string|UnitEnum|null $navigationGroup = 'Relatórios';

    public ?array $data = [];

    public array $dadosRelatorio = [];

    public bool $buscaRealizada = false;

    public function mount(): void
    {
        $this->data = [
            'periodo' => now()->startOfMonth()->format('d/m/Y').' - '.now()->endOfMonth()->format('d/m/Y'),
            'colaborador_ids' => [],
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Section::make('Filtros')
                    ->description('Filtra apontamentos que cruzam o período informado e sequencia os registros por mecânico.')
                    ->columns(3)
                    ->columnSpan(12)
                    ->components([
                        DateRangePicker::make('periodo')
                            ->label('Período')
                            ->autoApply()
                            ->firstDayOfWeek(0)
                            ->alwaysShowCalendar()
                            ->required(),
                        Select::make('colaborador_ids')
                            ->label('Mecânicos')
                            ->placeholder('Todos os mecânicos')
                            ->options(fn (): array => Colaborador::query()
                                ->where('tipo', 'MECANICO')
                                ->orderBy('nome')
                                ->get()
                                ->mapWithKeys(fn (Colaborador $colaborador): array => [
                                    $colaborador->id => trim(($colaborador->codigo ? $colaborador->codigo.' - ' : '').$colaborador->nome),
                                ])
                                ->all())
                            ->multiple()
                            ->searchable()
                            ->preload(),
                    ]),
            ])
            ->statePath('data');
    }

    public function carregarDados(): void
    {
        try {
            $this->dadosRelatorio = app(RelatorioApontamentosMecanicosService::class)->obterDados($this->data ?? []);
            $this->buscaRealizada = true;

            Notification::make()
                ->success()
                ->title('Dados carregados')
                ->body($this->dadosRelatorio['total_apontamentos'].' apontamento(s) encontrado(s).')
                ->send();
        } catch (\Throwable $exception) {
            $this->dadosRelatorio = [];
            $this->buscaRealizada = false;

            Notification::make()
                ->danger()
                ->title('Erro ao carregar dados')
                ->body($exception->getMessage())
                ->send();
        }
    }

    public function gerarPdf(): mixed
    {
        try {
            $dados = app(RelatorioApontamentosMecanicosService::class)->obterDados($this->data ?? []);

            if (($dados['total_apontamentos'] ?? 0) === 0) {
                Notification::make()
                    ->warning()
                    ->title('Nenhum apontamento encontrado')
                    ->body('Ajuste os filtros para gerar o PDF.')
                    ->send();

                return null;
            }

            $pdf = Pdf::loadView('pdf.relatorio-apontamentos-mecanicos', [
                'dados' => $dados,
                'dataGeracao' => now()->format('d/m/Y H:i:s'),
            ])->setPaper('A4', 'landscape');

            return response()->streamDownload(
                fn () => print ($pdf->output()),
                'relatorio-apontamentos-mecanicos-'.date('Y-m-d-H-i').'.pdf',
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

    public function formatarMinutos(?int $minutos): string
    {
        if ($minutos === null) {
            return '-';
        }

        return intdiv($minutos, 60).'h '.($minutos % 60).'min';
    }
}
