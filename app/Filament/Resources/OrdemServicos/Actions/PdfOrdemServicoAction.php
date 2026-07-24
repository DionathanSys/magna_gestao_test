<?php

namespace App\Filament\Resources\OrdemServicos\Actions;

use Filament\Actions\Action;

class PdfOrdemServicoAction
{
    public static function make(): Action
    {
        return Action::make('PDF OS')
            ->label('Abrir PDF')
            ->icon('heroicon-o-eye')
            ->url(function ($record) {
                return route('ordem-servico.pdf.visualizar', $record);
            })
            ->openUrlInNewTab()
            ->color('success');
    }
}
