<?php

namespace App\Filament\Resources\HistoricoQuilometragems;

use App\Filament\Resources\HistoricoQuilometragems\Pages\ManageHistoricoQuilometragems;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Models\HistoricoQuilometragem;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\Auth;
use Malzariey\FilamentDaterangepickerFilter\Enums\DropDirection;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
use UnitEnum;

class HistoricoQuilometragemResource extends Resource
{
    protected static ?string $model = HistoricoQuilometragem::class;

    protected static string|UnitEnum|null $navigationGroup = 'Veículos';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $modelLabel = 'Hit. Quilometragem';

    protected static ?string $pluralModelLabel = 'Hist. Quilometragens';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('veiculo_id')
                    ->relationship('veiculo', 'placa')
                    ->required(),
                TextInput::make('quilometragem')
                    ->required(),
                DatePicker::make('data_referencia')
                    ->label('Data de Referência')
                    ->maxDate(now())
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('id'),
                TextColumn::make('veiculo.placa')
                    ->label('Veículo'),
                TextColumn::make('quilometragem')
                    ->numeric(0, ',', '.'),
                TextColumn::make('data_referencia')
                    ->label('Data de Referência')
                    ->date('d/m/Y'),
            ])
            ->defaultSort('data_referencia', 'desc')
            ->filters([
                SelectFilter::make('veiculo_id')
                    ->label('Veículo')
                    ->relationship('veiculo', 'placa')
                    ->multiple()
                    ->searchable()
                    ->preload(),
                DateRangeFilter::make('data_referencia')
                    ->label('Dt. Referência')
                    ->drops(DropDirection::AUTO)
                    ->icon('heroicon-o-backspace')
                    ->alwaysShowCalendar()
                    ->autoApply()
                    ->firstDayOfWeek(0)
                    ->defaultYesterday(),
                Filter::make('veiculo_desatualizado')
                    ->label('Veículos com KM desatualizado')
                    ->query(
                        fn($query) =>
                        $query->whereHas('veiculo', function ($veiculoQuery) {
                            $veiculoQuery
                                ->where('is_active', true)
                                ->whereHas('kmAtual', function ($kmQuery) {
                                    $kmQuery->where('data_referencia', '<', now()->subDays(2));
                                })
                                ->orWhereDoesntHave('kmAtual');
                        })
                    )
                    ->toggle()
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn(): bool => Auth::user()->is_admin),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageHistoricoQuilometragems::route('/'),
        ];
    }
}
