<?php

namespace App\Filament\Resources\Servicos;

use App\Filament\Resources\Servicos\Pages\CreateServico;
use App\Filament\Resources\Servicos\Pages\EditServico;
use App\Filament\Resources\Servicos\Pages\ListServicos;
use App\Filament\Resources\Servicos\Schemas\ServicoForm;
use App\Filament\Resources\Servicos\Tables\ServicosTable;
use App\Models\Servico;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class ServicoResource extends Resource
{
    protected static ?string $model = Servico::class;

    protected static string|UnitEnum|null $navigationGroup = 'Cadastro';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-wrench';

    protected static ?string $modelLabel = 'Serviços';

    protected static ?string $pluralModelLabel = 'Serviços';

    protected static ?string $recordTitleAttribute = 'descricao';

    public static function form(Schema $schema): Schema
    {
        return ServicoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ServicosTable::configure($table);
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
            'index' => ListServicos::route('/'),
            'create' => CreateServico::route('/create'),
            'edit' => EditServico::route('/{record}/edit'),
        ];
    }
}
