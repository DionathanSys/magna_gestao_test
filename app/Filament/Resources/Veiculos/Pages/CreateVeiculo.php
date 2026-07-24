<?php

namespace App\Filament\Resources\Veiculos\Pages;

use App\Filament\Resources\Veiculos\VeiculoResource;
use App\Models\Veiculo;
use App\Services\Pneus\SincronizarPosicoesMapaVeiculoService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateVeiculo extends CreateRecord
{
    protected static string $resource = VeiculoResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data): Model {
            $record = Veiculo::query()->create($data);

            app(SincronizarPosicoesMapaVeiculoService::class)->handle($record);

            return $record;
        });
    }
}
