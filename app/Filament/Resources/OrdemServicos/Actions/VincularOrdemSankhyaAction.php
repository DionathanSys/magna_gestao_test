<?php

namespace App\Filament\Resources\OrdemServicos\Actions;

use Filament\Actions\Action;
use App\Models;
use App\Services\NotificacaoService as notify;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Log;

class VincularOrdemSankhyaAction
{
    public static function make(): Action
    {
        return Action::make('vincular-os')
            ->label('Add Ordem Sankhya')
            ->icon('heroicon-o-clipboard-document-list')
            ->modal()
            ->modalHeading('Vincular OS Sankhya')
            ->modalDescription('Preencha o ID da Ordem de Serviço no Sankhya.')
            ->modalIcon('heroicon-o-document-plus')
            ->modalWidth(Width::Large)
            ->modalAlignment(Alignment::Center)
            ->extraModalFooterActions(fn(Action $action): array => [
                $action->makeModalSubmitAction('vincularOutro', arguments: ['another' => true]),
            ])
            ->modalSubmitActionLabel('Vincular')
            ->schema(fn(Schema $form) => $form
                ->columns(8)
                ->schema([
                    TextInput::make('ordem_sankhya_id')
                        ->label('ID Sankhya')
                        ->required()
                        ->numeric()
                        ->minValue(1)
                        ->live(onBlur: true)
                        ->columnSpan(2)
                        ->afterStateUpdated(function (Set $set, $state) {
                            $exists = Models\OrdemSankhya::where('ordem_sankhya_id', $state)->exists();
                            $set('existe', $exists ? 'Sim' : 'Não');
                        }),
                    TextInput::make('existe')
                        ->label('Já existe?')
                        ->readOnly()
                        ->live()
                        ->columnSpan(2),
                ]))
            ->action(function (Action $action, Schema $form, Models\OrdemServico $record, array $data, array $arguments) {
                if ($data['existe'] == 'Sim') {
                    notify::error('Ordem de Serviço Sankhya já vinculada!');
                    $action->halt();
                }

                Models\OrdemSankhya::create([
                    'ordem_servico_id' => $record->id,
                    'ordem_sankhya_id' => $data['ordem_sankhya_id'],
                ]);

                if ($arguments['another'] ?? false) {
                    $form->fill();
                    $action->halt();
                }

                return;
            });
    }
}
