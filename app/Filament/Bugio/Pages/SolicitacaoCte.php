<?php

namespace App\Filament\Bugio\Pages;

use BackedEnum;
use Filament\Pages\Page;

class SolicitacaoCte extends Page
{
    protected static ?string $title = 'Solicitar CTe';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-document-plus';

    protected string $view = 'filament.bugio.pages.solicitacao-cte';
}
