<?php

namespace App\Filament\Resources\Pneus\Actions;

use App\Enum\Pneu\StatusPneuEnum;
use App\Models;
use App\Services;
use Filament\Actions\Action;
use App\Services\NotificacaoService as notify;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class RecaparPneuAction
{
    public static function make(): Action
    {
        return Action::make('recapar')
            ->label('Registrar Recapagem')
            ->color('info')
            ->schema([
                TextInput::make('pneu_id')
                    ->label('Pneu')
                    ->columnSpan(2),
                DatePicker::make('data_recapagem')
                    ->date('d/m/Y')
                    ->columnSpan(3)
                    ->displayFormat('d/m/Y')
                    ->closeOnDateSelection()
                    ->default(now())
                    ->maxDate(now()),
                TextInput::make('valor')
                    ->label('Valor')
                    ->columnSpan(3)
                    ->numeric()
                    ->default(0)
                    ->prefix('R$'),
                Select::make('desenho_pneu_id')
                    ->label('Desenho Borracha')
                    ->relationship('desenhoPneu', 'descricao', fn($query) => $query->where('estado_pneu', 'RECAPADO'))
                    ->searchable()
                    ->preload()
                    // ->createOptionForm(fn(Schema $schema) => DesenhoPneuResource::form($schema))
                    ->columnSpan(4),

            ])
            ->action(function (Action $action, array $data) {

                Log::debug(__METHOD__ . ' - Iniciando recapagem via action', ['data' => $data]);

                $service = new Services\Pneus\PneuService();
                $service->recapar($data);

                if ($service->hasError()) {
                    notify::error(titulo: 'Erro ao recapar pneu', mensagem: $service->getMessage());
                    $action->halt();
                }

                notify::success('Recapagem realizada com sucesso.');
            });
    }

}
