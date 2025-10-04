<?php

namespace App\Filament\Resources\Checklists\Pages;

use App\Services;
use App\Services\NotificacaoService as notify;
use App\Filament\Resources\Checklists\ChecklistResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CreateChecklist extends CreateRecord
{
    protected static string $resource = ChecklistResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        Log::debug(__METHOD__. ' - ' . __LINE__, [
            'data' => $data,
        ]);
        
        $service = new Services\Checklist\ChecklistService();
        $checklist = $service->registrarChecklist($data);
        if($service->hasError()) {
            notify::error('Erro ao registrar checklist.');
            Log::error('Erro ao registrar checklist', [
                'error' => $service->getData(),
                'data' => $data,
                'user_id' => Auth::id(),
            ]);
            $this->halt();
        }

        notify::success('Checklist registrado com sucesso.');
        return $checklist;
    }

}
