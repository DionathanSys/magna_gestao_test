<?php

namespace App\Filament\Resources\Veiculos;

use App\Filament\Resources\Veiculos\Pages\CreateVeiculo;
use App\Filament\Resources\Veiculos\Pages\EditVeiculo;
use App\Filament\Resources\Veiculos\Pages\ListVeiculos;
use App\Filament\Resources\Veiculos\Pages\MapaPneusVeiculo;
use App\Filament\Resources\Veiculos\RelationManagers\ManutencoesRelationManager;
use App\Filament\Resources\Veiculos\RelationManagers\PlanoPreventivoRelationManager;
use App\Filament\Resources\Veiculos\RelationManagers\PneusRelationManager;
use App\Filament\Resources\Veiculos\Schemas\VeiculoForm;
use App\Filament\Resources\Veiculos\Tables\VeiculosTable;
use App\Models\Veiculo;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class VeiculoResource extends Resource
{
    protected static ?string $model = Veiculo::class;

    protected static string|UnitEnum|null $navigationGroup = 'Veículos';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected static ?string $modelLabel = 'Veículos';

    protected static ?string $pluralModelLabel = 'Veículos';

    protected static ?string $recordTitleAttribute = 'placa';

    public static function form(Schema $schema): Schema
    {
        return VeiculoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VeiculosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            PneusRelationManager::class,
            ManutencoesRelationManager::class,
            PlanoPreventivoRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVeiculos::route('/'),
            'create' => CreateVeiculo::route('/create'),
            'edit' => EditVeiculo::route('/{record}/edit'),
            'mapa-pneus' => MapaPneusVeiculo::route('/{record}/mapa-pneus'),
        ];
    }

    public static function getGlobalSearchResultActions(Model $record): array
    {
        return [
            Action::make('mapa-pneus')
                ->label('Mapa de pneus')
                ->icon('heroicon-o-map')
                ->url(static::getUrl('mapa-pneus', ['record' => $record])),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
