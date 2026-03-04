<?php

namespace App\Filament\Pages;

use App\Models\Veiculo;
use BackedEnum;
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

                    return $hoje->diffInDays($dataTeste, false) <= -75;
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
                ->body(count($this->dadosRelatorio) . ' veículo(s) encontrado(s)')
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
