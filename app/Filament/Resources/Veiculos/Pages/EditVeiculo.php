<?php

namespace App\Filament\Resources\Veiculos\Pages;

use App\Filament\Resources\Veiculos\VeiculoResource;
use App\Services\Pneus\SincronizarPosicoesMapaVeiculoService;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EditVeiculo extends EditRecord
{
    protected static string $resource = VeiculoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('mapaPneus')
                ->label('Mapa de Pneus')
                ->icon('heroicon-o-square-3-stack-3d')
                ->url(fn () => VeiculoResource::getUrl('mapa-pneus', ['record' => $this->record], isAbsolute: false)),
            DeleteAction::make()
                ->visible(fn () => Auth::user()->is_admin),
            ForceDeleteAction::make()
                ->disabled(fn () => ! Auth::user()->is_admin),
            RestoreAction::make()
                ->disabled(fn () => ! Auth::user()->is_admin),
        ];
    }

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    public function getContentTabLabel(): ?string
    {
        return 'Geral';
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            return DB::transaction(function () use ($record, $data): Model {
                $previousMapaPneuId = $record->mapa_pneu_id;

                $record->update($data);

                app(SincronizarPosicoesMapaVeiculoService::class)->handle($record->fresh(), $previousMapaPneuId);

                return $record;
            });
        } catch (DomainException $exception) {
            throw ValidationException::withMessages([
                'mapa_pneu_id' => $exception->getMessage(),
            ]);
        }
    }
}
