<?php

namespace App\Filament\Mobile\Resources\OrdemServicos;

use App\Filament\Resources\OrdemServicos\Pages\MobileCreateOrdemServico;
use App\Filament\Resources\OrdemServicos\Pages\MobileDetailOrdemServico;
use App\Filament\Resources\OrdemServicos\Pages\MobileListOrdemServicos;
use App\Models\OrdemServico;
use BackedEnum;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class OrdemServicoResource extends Resource
{
    protected static ?string $model = OrdemServico::class;

    protected static string|UnitEnum|null $navigationGroup = 'Manutenção';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $modelLabel = 'Ordem Serviço';

    protected static ?string $pluralModelLabel = 'Ordens Serviço';

    protected static ?string $recordTitleAttribute = 'id';

    public static function getPages(): array
    {
        return [
            'index' => MobileListOrdemServicos::route('/'),
            'create' => MobileCreateOrdemServico::route('/create'),
            'detail' => MobileDetailOrdemServico::route('/{record}'),
        ];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Placa' => $record->veiculo->placa.' - '.$record->status->value,
        ];
    }
}
