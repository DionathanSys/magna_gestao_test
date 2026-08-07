<?php

namespace App\Filament\Resources\OrdemServicos\Actions;

use App\Models\OrdemServico;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;

class GerarRelatorioApontamentosOficinaPeriodoAction
{
    public static function make(): Action
    {
        return Action::make('gerar_relatorio_apontamentos_oficina_periodo')
            ->label('Relatório apontamentos')
            ->icon('heroicon-o-document-arrow-down')
            ->color('primary')
            ->modalHeading('Gerar relatório de apontamentos da oficina')
            ->modalSubmitActionLabel('Gerar PDF')
            ->form([
                DateRangePicker::make('periodo')
                    ->label('Período')
                    ->autoApply()
                    ->firstDayOfWeek(0)
                    ->alwaysShowCalendar()
                    ->required(),
            ])
            ->action(function (array $data) {
                [$inicio, $fim] = self::parsePeriodo($data['periodo'] ?? null);

                if (! $inicio || ! $fim) {
                    Notification::make()
                        ->danger()
                        ->title('Período inválido')
                        ->body('Informe um período válido para gerar o relatório.')
                        ->send();

                    return null;
                }

                $ordensServico = self::buscarOrdensServico($inicio, $fim);

                if ($ordensServico->isEmpty()) {
                    Notification::make()
                        ->warning()
                        ->title('Nenhuma ordem encontrada')
                        ->body('Não existem ordens encerradas ou com apontamentos no período informado.')
                        ->send();

                    return null;
                }

                $pdf = Pdf::loadView('pdf.oficina-ordens-servico-apontamentos', [
                    'ordensServico' => $ordensServico,
                    'periodoInicio' => $inicio,
                    'periodoFim' => $fim,
                    'dataGeracao' => now()->format('d/m/Y H:i'),
                ])->setPaper('A4', 'portrait');

                $fileName = 'relatorio-apontamentos-oficina-'.$inicio->format('Y-m-d').'-'.$fim->format('Y-m-d').'.pdf';

                return response()->streamDownload(function () use ($pdf): void {
                    echo $pdf->output();
                }, $fileName, [
                    'Content-Type' => 'application/pdf',
                ]);
            });
    }

    private static function buscarOrdensServico(Carbon $inicio, Carbon $fim)
    {
        return OrdemServico::query()
            ->whereNull('parceiro_id')
            ->where(function (Builder $query) use ($inicio, $fim): void {
                $query
                    ->whereBetween('data_fim', [$inicio, $fim])
                    ->orWhereHas('apontamentosOficina', function (Builder $query) use ($inicio, $fim): void {
                        $query
                            ->where('iniciado_em', '<=', $fim)
                            ->where(function (Builder $query) use ($inicio): void {
                                $query
                                    ->whereNull('encerrado_em')
                                    ->orWhere('encerrado_em', '>=', $inicio);
                            });
                    });
            })
            ->with([
                'veiculo',
                'itens.servico',
                'apontamentosOficina.colaborador',
                'apontamentosOficina.itens.servico',
            ])
            ->orderBy('data_fim')
            ->orderBy('id')
            ->get();
    }

    private static function parsePeriodo(mixed $value): array
    {
        try {
            if (is_array($value)) {
                $values = array_values($value);

                $inicio = self::parseDateValue($value['startDate'] ?? $value['start'] ?? $values[0] ?? null)?->startOfDay();
                $fim = self::parseDateValue($value['endDate'] ?? $value['end'] ?? $values[1] ?? null)?->endOfDay();

                return [$inicio, $fim];
            }

            if (is_string($value) && str_contains($value, ' - ')) {
                [$rawInicio, $rawFim] = array_map('trim', explode(' - ', $value, 2));

                return [
                    self::parseDateValue($rawInicio)?->startOfDay(),
                    self::parseDateValue($rawFim)?->endOfDay(),
                ];
            }

            if (is_string($value) && filled($value)) {
                return [
                    self::parseDateValue($value)?->startOfDay(),
                    self::parseDateValue($value)?->endOfDay(),
                ];
            }
        } catch (\Throwable) {
            return [null, null];
        }

        return [null, null];
    }

    private static function parseDateValue(mixed $value): ?Carbon
    {
        if (! is_string($value) || blank($value)) {
            return null;
        }

        $value = trim($value);

        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $value) === 1) {
            return Carbon::createFromFormat('d/m/Y', $value);
        }

        $isoDate = Str::of($value)->before('T');

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $isoDate->value()) === 1) {
            return Carbon::createFromFormat('Y-m-d', $isoDate->value());
        }

        return Carbon::parse($value);
    }
}
