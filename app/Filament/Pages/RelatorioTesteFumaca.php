<?php

namespace App\Filament\Pages;

use App\Models\Veiculo;
use App\Models\VeiculoDocumento;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use UnitEnum;

class RelatorioTesteFumaca extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-fire';

    protected string $view = 'filament.pages.relatorio-teste-fumaca';

    protected static ?string $navigationLabel = 'Relatório Teste de Fumaça';

    protected static ?string $title = 'Relatório – Teste de Fumaça';

    protected static string|UnitEnum|null $navigationGroup = 'Relatórios';

    public array $dadosRelatorio = [];

    public bool $buscaRealizada = false;

    public string $ordenarPor = 'dias_vencido';

    public string $direcaoOrdenacao = 'desc';

    public function carregarDados(): void
    {
        try {
            $this->dadosRelatorio = $this->getDadosTesteFumaca();

            $this->buscaRealizada = true;

            Notification::make()
                ->title('Dados carregados com sucesso')
                ->success()
                ->body(count($this->dadosRelatorio).' veículo(s) encontrado(s)')
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Erro ao carregar dados')
                ->danger()
                ->body($e->getMessage())
                ->send();

            $this->buscaRealizada = false;
            $this->dadosRelatorio = [];
        }
    }

    public function gerarPdf(): mixed
    {
        try {
            $dados = $this->getDadosTesteFumaca();

            $pdf = Pdf::loadView('pdf.relatorio-teste-fumaca', [
                'dados' => $dados,
                'totalRegistros' => count($dados),
                'dataGeracao' => now()->format('d/m/Y H:i:s'),
            ]);

            $pdf->setPaper('A4', 'portrait');

            return response()->streamDownload(
                fn () => print ($pdf->output()),
                'relatorio-teste-fumaca-'.date('Y-m-d-H-i').'.pdf'
            );
        } catch (\Exception $e) {
            Notification::make()
                ->title('Erro ao gerar PDF')
                ->danger()
                ->body($e->getMessage())
                ->send();

            return null;
        }
    }

    public function ordenarPorColuna(string $coluna): void
    {
        if ($this->ordenarPor === $coluna) {
            $this->direcaoOrdenacao = $this->direcaoOrdenacao === 'asc' ? 'desc' : 'asc';
        } else {
            $this->ordenarPor = $coluna;
            $this->direcaoOrdenacao = $coluna === 'dias_vencido' ? 'desc' : 'asc';
        }

        if (! empty($this->dadosRelatorio)) {
            usort($this->dadosRelatorio, function ($a, $b) {
                $valorA = $a[$this->ordenarPor] ?? '';
                $valorB = $b[$this->ordenarPor] ?? '';

                return $this->direcaoOrdenacao === 'asc'
                    ? $valorA <=> $valorB
                    : $valorB <=> $valorA;
            });
        }
    }

    private function getDadosTesteFumaca(): array
    {
        $hoje = Carbon::today();

        return Veiculo::query()
            ->where('is_active', true)
            ->with([
                'kmAtual',
                'documentos' => fn ($query) => $query
                    ->where('tipo', VeiculoDocumento::TIPO_TESTE_FUMACA)
                    ->orderByDesc('data_inicio')
                    ->orderByDesc('data_fim')
                    ->orderByDesc('id'),
            ])
            ->orderBy('placa')
            ->get()
            ->map(function (Veiculo $veiculo) use ($hoje): ?array {
                $documento = $veiculo->documentos->first();

                if (! $documento instanceof VeiculoDocumento) {
                    return null;
                }

                $dataTeste = $documento->data_inicio
                    ?: $documento->data_fim?->copy()->subDays(180);

                if (! $dataTeste || $hoje->diffInDays($dataTeste, false) > -150) {
                    return null;
                }

                return [
                    'placa' => $veiculo->placa,
                    'km_atual' => $veiculo->quilometragem_atual,
                    'data_teste' => $dataTeste->toDateString(),
                    'dias_vencido' => (int) abs($hoje->diffInDays($dataTeste)),
                ];
            })
            ->filter()
            ->sortByDesc('dias_vencido')
            ->values()
            ->toArray();
    }
}
