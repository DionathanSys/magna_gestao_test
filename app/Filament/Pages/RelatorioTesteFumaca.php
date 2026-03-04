<?php

namespace App\Filament\Pages;

use App\Models\Veiculo;
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

    protected static ?string $navigationLabel = 'RelatÃ³rio Teste de FumaÃ§a';

    protected static ?string $title = 'RelatÃ³rio â€“ Teste de FumaÃ§a';

    protected static string|UnitEnum|null $navigationGroup = 'RelatÃ³rios';

    public array $dadosRelatorio = [];

    public bool $buscaRealizada = false;

    public string $ordenarPor = 'dias_vencido';

    public string $direcaoOrdenacao = 'desc';

    public function carregarDados(): void
    {
        try {
            $hoje = Carbon::today();

            $this->dadosRelatorio = Veiculo::query()
                ->where('is_active', true)
                ->with('kmAtual')
                ->orderBy('placa')
                ->get()
                ->filter(function (Veiculo $veiculo) use ($hoje) {
                    $info = $veiculo->informacoes_complementares;

                    if (empty($info['teste_fumaca'])) {
                        return false;
                    }

                    $dataTeste = Carbon::parse($info['teste_fumaca']);

                    return $hoje->diffInDays($dataTeste, false) <= -150;
                })
                ->map(function (Veiculo $veiculo) use ($hoje) {
                    $info  = $veiculo->informacoes_complementares;
                    $dataTeste   = Carbon::parse($info['teste_fumaca']);
                    $diasVencido = (int) abs($hoje->diffInDays($dataTeste));

                    return [
                        'placa'       => $veiculo->placa,
                        'km_atual'    => $veiculo->quilometragem_atual,
                        'data_teste'  => $info['teste_fumaca'],
                        'dias_vencido' => $diasVencido,
                    ];
                })
                ->sortByDesc('dias_vencido')
                ->values()
                ->toArray();

            $this->buscaRealizada = true;

            Notification::make()
                ->title('Dados carregados com sucesso')
                ->success()
                ->body(count($this->dadosRelatorio) . ' veÃ­culo(s) encontrado(s)')
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
            $hoje = Carbon::today();

            $dados = Veiculo::query()
                ->where('is_active', true)
                ->with('kmAtual')
                ->orderBy('placa')
                ->get()
                ->filter(function (Veiculo $veiculo) use ($hoje) {
                    $info = $veiculo->informacoes_complementares;

                    if (empty($info['teste_fumaca'])) {
                        return false;
                    }

                    return $hoje->diffInDays(Carbon::parse($info['teste_fumaca']), false) <= -150;
                })
                ->map(function (Veiculo $veiculo) use ($hoje) {
                    $info        = $veiculo->informacoes_complementares;
                    $dataTeste   = Carbon::parse($info['teste_fumaca']);
                    $diasVencido = (int) abs($hoje->diffInDays($dataTeste));

                    return [
                        'placa'        => $veiculo->placa,
                        'km_atual'     => $veiculo->quilometragem_atual,
                        'data_teste'   => $info['teste_fumaca'],
                        'dias_vencido' => $diasVencido,
                    ];
                })
                ->sortByDesc('dias_vencido')
                ->values()
                ->toArray();

            $pdf = Pdf::loadView('pdf.relatorio-teste-fumaca', [
                'dados'          => $dados,
                'totalRegistros' => count($dados),
                'dataGeracao'    => now()->format('d/m/Y H:i:s'),
            ]);

            $pdf->setPaper('A4', 'portrait');

            return response()->streamDownload(
                fn () => print($pdf->output()),
                'relatorio-teste-fumaca-' . date('Y-m-d-H-i') . '.pdf'
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
            $this->ordenarPor      = $coluna;
            $this->direcaoOrdenacao = $coluna === 'dias_vencido' ? 'desc' : 'asc';
        }

        if (!empty($this->dadosRelatorio)) {
            usort($this->dadosRelatorio, function ($a, $b) {
                $valorA = $a[$this->ordenarPor] ?? '';
                $valorB = $b[$this->ordenarPor] ?? '';

                return $this->direcaoOrdenacao === 'asc'
                    ? $valorA <=> $valorB
                    : $valorB <=> $valorA;
            });
        }
    }
}
