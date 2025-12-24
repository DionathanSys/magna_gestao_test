<?php

namespace App\Filament\Bugio\Resources\ViagemBugios\Pages;

use App\Filament\Bugio\Resources\ViagemBugios\ViagemBugioResource;
use App\Services\ViagemBugio\ViagemBugioService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use App\Services\NotificacaoService as notify;

class CreateViagemBugio extends CreateRecord
{
    protected static string $resource = ViagemBugioResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $service = new ViagemBugioService();
        $result = $service->criarViagem($data);

        if($service->hasError()) {
            notify::error(mensagem: 'Falha ao criar viagem Bugio' );
            $this->halt();
        }

        return $result;
    }
}
