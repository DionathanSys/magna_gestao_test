<?php

namespace App\Filament\Resources\Viagems\Actions;

use App\Models;
use App\Models\Integrado;
use App\Services\Comentario\ComentarioService;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class AdicionarComentarioAction
{
    public static function make(): Action
    {
        return Action::make('adicionarComentario')
            ->label('Adicionar Comentário')
            ->icon(Heroicon::ChatBubbleBottomCenterText)
            ->fillForm([])
            ->schema(fn (Schema $form) => $form
                ->columns(12)
                ->schema([
                    CheckboxList::make('integrados')
                        ->label('Integrados')
                        ->columnSpan(6)
                        ->aboveContent('Selecione os integrados relacionados')
                        ->options(function ($record) {
                            return $record->integrados->pluck('nome', 'id')->toArray();
                        }),
                    Textarea::make('conteudo')
                        ->label('Comentário')
                        ->columnSpan(6)
                        ->rows(4),
                ]))
            ->action(function (array $data, $record) {

                // Ajustes nos dados
                $data['veiculo_id'] = $record->veiculo_id;
                $integradosId = $data['integrados'] ?? [];

                $service = app(ComentarioService::class);
                $service->adicionarComentario([$record->id, Models\Viagem::class], $data);

                foreach ($integradosId as $integradoId) {
                    $integrado = Integrado::find($integradoId);
                    $data['conteudo'] = "[Viagem: {$record->numero_viagem} - {$integrado->nome}] ".$data['conteudo'];
                    $service->adicionarComentario([$integradoId, Integrado::class], $data);
                }

            })
            ->modalHeading('Adicionar Comentário à Viagem');
    }
}
