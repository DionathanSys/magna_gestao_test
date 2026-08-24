<?php

namespace App\Filament\Resources\Viagems\Actions;

use App\Enum\MotivoDivergenciaViagem;
use App\Models\JustificativaDispersaoViagem;
use App\Models\Viagem;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class JustificarDispersaoAction
{
    public static function make(): Action
    {
        return Action::make('justificar-dispersao')
            ->label('Justificar dispersão')
            ->icon('heroicon-o-document-text')
            ->color('warning')
            ->schema([
                Placeholder::make('resumo_viagem')
                    ->label('Dados da viagem')
                    ->content(fn (Viagem $record): HtmlString => new HtmlString(
                        'Viagem: <strong>'.e($record->numero_viagem ?: 'Não informada').'</strong><br>'
                        .'Placa: <strong>'.e($record->veiculo?->placa ?: 'Não informada').'</strong><br>'
                        .'KM rodado: <strong>'.number_format((float) $record->km_rodado, 2, ',', '.').'</strong><br>'
                        .'KM pago: <strong>'.number_format((float) $record->km_pago, 2, ',', '.').'</strong><br>'
                        .'KM dispersão: <strong>'.number_format((float) $record->km_dispersao, 2, ',', '.').'</strong><br>'
                        .'Dispersão: <strong>'.number_format((float) $record->dispersao_percentual, 2, ',', '.').'%</strong>'
                    )),
                Placeholder::make('justificativas_existentes')
                    ->label('Justificativas já registradas')
                    ->content(function (Viagem $record): HtmlString {
                        $justificativas = $record->justificativasDispersao()
                            ->with('criador:id,name')
                            ->latest()
                            ->get();

                        if ($justificativas->isEmpty()) {
                            return new HtmlString('Nenhuma justificativa registrada para esta viagem.');
                        }

                        return new HtmlString($justificativas
                            ->map(function (JustificativaDispersaoViagem $justificativa): string {
                                $observacao = filled($justificativa->observacao)
                                    ? '<br>Observação: '.nl2br(e($justificativa->observacao))
                                    : '';

                                return '<div class="mb-3">'
                                    .'<strong>'.e($justificativa->motivo).'</strong><br>'
                                    .'Registrada em '.e($justificativa->created_at->format('d/m/Y H:i'))
                                    .' por '.e($justificativa->criador?->name ?? 'Usuário não identificado')
                                    .$observacao
                                    .'</div>';
                            })
                            ->implode(''));
                    }),
                Select::make('motivo')
                    ->label('Motivo')
                    ->options(MotivoDivergenciaViagem::toSelectArray())
                    ->searchable()
                    ->required(),
                Textarea::make('observacao')
                    ->label('Observação')
                    ->rows(3)
                    ->maxLength(1000),
            ])
            ->action(function (Viagem $record, array $data): void {
                JustificativaDispersaoViagem::query()->create([
                    'viagem_id' => $record->id,
                    'motivo' => $data['motivo'],
                    'observacao' => $data['observacao'] ?? null,
                    'numero_viagem' => $record->numero_viagem,
                    'veiculo_placa' => $record->veiculo?->placa,
                    'data_competencia' => $record->data_competencia,
                    'km_rodado' => $record->km_rodado ?? 0,
                    'km_pago' => $record->km_pago ?? 0,
                    'km_dispersao' => $record->km_dispersao ?? 0,
                    'dispersao_percentual' => $record->dispersao_percentual ?? 0,
                    'created_by' => Auth::id(),
                ]);

                Notification::make()
                    ->success()
                    ->title('Justificativa registrada')
                    ->send();
            })
            ->modalHeading('Justificar dispersão de KM')
            ->modalSubmitActionLabel('Registrar justificativa');
    }
}
