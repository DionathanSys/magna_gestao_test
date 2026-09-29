<?php

namespace App\Filament\Resources\PlanoPreventivos\Schemas;

use App\Services\Servico\ServicoCacheService;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PlanoPreventivoInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextEntry::make('descricao')
                    ->label('Descrição'),
                TextEntry::make('periodicidade')
                    ->label('Periodicidade')
                    ->placeholder('Não informada'),
                TextEntry::make('intervalo')
                    ->label('Intervalo')
                    ->numeric()
                    ->suffix(' km'),
                IconEntry::make('is_active')
                    ->label('Ativo')
                    ->boolean(),
                TextEntry::make('itens')
                    ->label('Serviços do plano')
                    ->columnSpanFull()
                    ->formatStateUsing(function ($state): string {
                        $itens = is_string($state) ? json_decode($state, true) : $state;

                        return collect($itens ?? [])
                            ->map(fn ($item): ?string => ServicoCacheService::getServicoLabel(data_get($item, 'servico_id')))
                            ->filter()
                            ->join(', ') ?: 'Nenhum serviço informado';
                    }),
                TextEntry::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i'),
                TextEntry::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime('d/m/Y H:i'),
            ]);
    }
}
