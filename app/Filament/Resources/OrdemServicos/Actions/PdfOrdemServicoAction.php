<?php

namespace App\Filament\Resources\OrdemServicos\Actions;

use App\Enum\Frete\TipoRelatorioDocumentoFreteEnum;
use App\Imports\DocumentoFreteImport;
use App\Jobs\ProcessarDocumentoFreteJob;
use Filament\Actions\Action;
use App\Services;
use App\Models;
use App\Services\NotificacaoService as notify;

use Filament\Forms\Components\Select;
use Filament\Support\Enums\Size;
use Illuminate\Support\Facades\Log;

class PdfOrdemServicoAction
{
    public static function make(): Action
    {
        return Action::make('PDF OS')
            ->label('Abrir PDF')
            ->icon('heroicon-o-eye')
            ->size(Size::ExtraSmall)
            ->url(function ($record) {
                return route('ordem-servico.pdf.visualizar', $record);
            })
            ->openUrlInNewTab()
            ->color('success');
    }
}
