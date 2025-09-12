<?php

namespace App\Filament\Resources\Integrados;

use App\Filament\Resources\Integrados\Pages\CreateIntegrado;
use App\Filament\Resources\Integrados\Pages\EditIntegrado;
use App\Filament\Resources\Integrados\Pages\ListIntegrados;
use App\Filament\Resources\Integrados\Pages\ViewIntegrado;
use App\Filament\Resources\Integrados\Schemas\IntegradoForm;
use App\Filament\Resources\Integrados\Tables\IntegradosTable;
use App\Models\Integrado;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class IntegradoResource extends Resource
{
    protected static ?string $model = Integrado::class;

    protected static string|UnitEnum|null $navigationGroup = 'Cadastro';

    protected static ?string $modelLabel = 'Integrado';

    protected static ?string $pluralModelLabel = 'Integrados';

    protected static ?string $recordTitleAttribute = 'nome';

    public static function form(Schema $schema): Schema
    {
        return IntegradoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IntegradosTable::configure($table);
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
            'index' => ListIntegrados::route('/'),
            'create' => CreateIntegrado::route('/create'),
            'edit' => EditIntegrado::route('/{record}/edit'),
            'view' => ViewIntegrado::route('/{record}/view'),
        ];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Localização' => $record->municipio . ' - ' . $record->estado,
            'KM Rota' => $record->km_rota . ' km'
        ];
    }

    public static function getGlobalSearchResultActions(Model $record): array
    {
        return [
            Action::make('edit')
                ->url(static::getUrl('edit', ['record' => $record]), shouldOpenInNewTab: true),
        ];
    }

}
