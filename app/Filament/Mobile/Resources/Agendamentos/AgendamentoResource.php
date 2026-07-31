<?php

namespace App\Filament\Mobile\Resources\Agendamentos;

use App\Filament\Resources\Agendamentos\Pages\MobileOperacaoAgendamentos;
use App\Models\Agendamento;
use BackedEnum;
use Filament\Resources\Resource;
use UnitEnum;

class AgendamentoResource extends Resource
{
    protected static ?string $model = Agendamento::class;

    protected static string|UnitEnum|null $navigationGroup = 'Manutenção';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static ?string $modelLabel = 'Agendamento';

    protected static ?string $pluralModelLabel = 'Agendamentos';

    protected static ?string $recordTitleAttribute = 'id';

    public static function getPages(): array
    {
        return [
            'index' => MobileOperacaoAgendamentos::route('/'),
        ];
    }
}
