<?php

namespace App\Filament\Resources\HistoricoMovimentoPneus;

use App\Filament\Resources\HistoricoMovimentoPneus\Pages\CreateHistoricoMovimentoPneu;
use App\Filament\Resources\HistoricoMovimentoPneus\Pages\EditHistoricoMovimentoPneu;
use App\Filament\Resources\HistoricoMovimentoPneus\Pages\ListHistoricoMovimentoPneus;
use App\Filament\Resources\HistoricoMovimentoPneus\Pages\ViewHistoricoMovimentoPneu;
use App\Filament\Resources\HistoricoMovimentoPneus\Schemas\HistoricoMovimentoPneuForm;
use App\Filament\Resources\HistoricoMovimentoPneus\Schemas\HistoricoMovimentoPneuInfolist;
use App\Filament\Resources\HistoricoMovimentoPneus\Tables\HistoricoMovimentoPneusTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use App\Models\HistoricoMovimentoPneu;
use UnitEnum;

class HistoricoMovimentoPneuResource extends Resource
{
    protected static ?string $model = HistoricoMovimentoPneu::class;

    protected static string|UnitEnum|null $navigationGroup = 'Pneus';

    protected static ?string $modelLabel = 'Hist. Mov. Pneu';

    protected static ?string $pluralModelLabel = 'Hist. Mov. Pneus';

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return HistoricoMovimentoPneuForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return HistoricoMovimentoPneuInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HistoricoMovimentoPneusTable::configure($table);
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
            'index' => ListHistoricoMovimentoPneus::route('/'),
            // 'create' => CreateHistoricoMovimentoPneu::route('/create'),
            'view' => ViewHistoricoMovimentoPneu::route('/{record}'),
            // 'edit' => EditHistoricoMovimentoPneu::route('/{record}/edit'),
        ];
    }
}
