<?php

namespace App\Filament\Resources\PneuInspecoes\Pages;

use App\Filament\Resources\PneuInspecoes\PneuInspecaoResource;
use App\Models\Pneu;
use Filament\Resources\Pages\CreateRecord;

class CreatePneuInspecao extends CreateRecord
{
    protected static string $resource = PneuInspecaoResource::class;

    public function mount(): void
    {
        parent::mount();

        $pneu = request()->integer('pneu_id')
            ? Pneu::query()->with('cicloAtual')->find(request()->integer('pneu_id'))
            : null;

        $this->form->fill(array_filter([
            'pneu_id' => request()->integer('pneu_id') ?: null,
            'pneu_ciclo_id' => request()->integer('pneu_ciclo_id') ?: $pneu?->cicloAtual?->id,
            'veiculo_id' => request()->integer('veiculo_id') ?: null,
            'pneu_posicao_veiculo_id' => request()->integer('pneu_posicao_veiculo_id') ?: null,
            'tipo' => request()->string('tipo')->toString() ?: null,
            'data_inspecao' => request()->string('data_inspecao')->toString() ?: now()->toDateString(),
            'km_referencia' => request()->integer('km_referencia') ?: null,
        ], fn ($value) => $value !== null && $value !== ''));
    }

    protected function getRedirectUrl(): string
    {
        return request()->string('redirect')->toString() ?: static::getResource()::getUrl('index');
    }
}
