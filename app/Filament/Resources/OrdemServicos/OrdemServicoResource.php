<?php

namespace App\Filament\Resources\OrdemServicos;

use App\Filament\Resources\OrdemServicos\Pages\CreateOrdemServico;
use App\Filament\Resources\OrdemServicos\Pages\EditOrdemServico;
use App\Filament\Resources\OrdemServicos\Pages\ListOrdemServicos;
use App\Filament\Resources\OrdemServicos\Pages\OrdemServico as PagesOrdemServico;
use App\Filament\Resources\OrdemServicos\Pages\OrdemServicoCustom;
use App\Filament\Resources\OrdemServicos\Pages\OrdemServicoTeste;
use App\Filament\Resources\OrdemServicos\Schemas\OrdemServicoForm;
use App\Filament\Resources\OrdemServicos\Tables\OrdemServicosTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use App\Models\OrdemServico;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class OrdemServicoResource extends Resource
{
    protected static ?string $model = OrdemServico::class;

    protected static string|UnitEnum|null $navigationGroup = 'Manutenção';

    protected static ?string $modelLabel = 'Ordem Serviço';

    protected static ?string $pluralModelLabel = 'Ordens Serviço';

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return OrdemServicoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrdemServicosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrdemServicos::route('/'),
            'edit' => EditOrdemServico::route('/{record}/edit'),
            'custom' => OrdemServicoTeste::route('/{record}/custom'),
        ];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Placa' => $record->veiculo->placa . ' - ' . $record->status->value,
        ];
    }

    public static function getGlobalSearchResultActions(Model $record): array
    {
        return [
            Action::make('edit')
                ->url(static::getUrl('edit', ['record' => $record->id]), shouldOpenInNewTab: true),
        ];
    }
}
