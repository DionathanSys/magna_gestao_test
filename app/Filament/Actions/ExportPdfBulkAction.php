<?php

namespace App\Filament\Actions;

use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;

class ExportPdfBulkAction
{
    /**
     * @param  string  $name  Unique action name
     * @param  string  $title  Report title
     * @param  array<int, array{key: string, label: string, align?: string, width?: string}>  $columns
     * @param  callable(Collection $records): array  $dataCallback  Receives records, returns array of rows (each row is array<string, string>)
     */
    public static function make(
        string $name,
        string $title,
        array $columns,
        callable $dataCallback,
    ): BulkAction {
        return BulkAction::make($name)
            ->label('Exportar PDF')
            ->icon('heroicon-o-document-arrow-down')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading("Exportar {$title}")
            ->modalDescription(fn (Collection $records) => "Serao exportados {$records->count()} registro(s) para PDF.")
            ->modalSubmitActionLabel('Exportar')
            ->action(function (Collection $records) use ($title, $columns, $dataCallback) {
                if ($records->count() > 500) {
                    Notification::make()
                        ->danger()
                        ->title('Muitos registros')
                        ->body('Selecione no maximo 500 registros para exportar.')
                        ->send();

                    return;
                }

                return static::gerarPdf($records, $title, $columns, $dataCallback);
            })
            ->deselectRecordsAfterCompletion();
    }

    protected static function gerarPdf(
        Collection $records,
        string $title,
        array $columns,
        callable $dataCallback,
    ): mixed {
        $originalMemoryLimit = ini_get('memory_limit');
        ini_set('memory_limit', '512M');

        try {
            $linhas = $dataCallback($records);

            $pdf = Pdf::loadView('pdf.relatorio-generico', [
                'titulo' => strtoupper($title),
                'colunas' => $columns,
                'linhas' => $linhas,
                'dataGeracao' => now()->format('d/m/Y H:i:s'),
            ]);

            $pdf->setPaper('a4', 'landscape');
            $pdf->setOption('isPhpEnabled', false);
            $pdf->setOption('isFontSubsettingEnabled', true);

            $fileName = str($title)->slug('_')->append('_'.now()->format('Y-m-d_His'))->append('.pdf');

            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->output();
            }, $fileName, [
                'Content-Type' => 'application/pdf',
            ]);
        } finally {
            ini_set('memory_limit', $originalMemoryLimit);
        }
    }
}
