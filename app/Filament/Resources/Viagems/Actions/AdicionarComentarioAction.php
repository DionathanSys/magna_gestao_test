<?php

namespace App\Filament\Resources\Viagems\Actions;

use App\{Models, Services};
use App\Models\Integrado;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use PhpOffice\PhpSpreadsheet\RichText\RichText;

class AdicionarComentarioAction
{
    public static function make(): Action
    {
        return Action::make('adicionarComentario')
            ->label('Adicionar Comentário')
            ->icon(Heroicon::ChatBubbleBottomCenterText)
            ->fillForm([])
            ->schema(fn(Schema $form) =>
                $form
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

                //Ajustes nos dados
                $data['veiculo_id'] = $record->veiculo_id;
                $integradosId = $data['integrados'] ?? [];

                $service = app(\App\Services\Comentario\ComentarioService::class);
                $service->adicionarComentario(array($record->id, Models\Viagem::class), $data);

                foreach($integradosId as $integradoId){
                    $integrado = Integrado::find($integradoId);
                    $data['conteudo'] = "[Viagem: {$record->id} {$integrado->nome}] " . $data['conteudo'];
                    $service->adicionarComentario(array($integradoId, Models\Integrado::class), $data);
                }

            })
            ->modalHeading('Adicionar Comentário à Viagem');
    }
}
