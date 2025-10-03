<?php

namespace App\Services\Checklist;

use App\Traits\ServiceResponseTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ChecklistService
{
    use ServiceResponseTrait;

    public function registrarChecklist(array $data)
    {
        try {
            $action = new Actions\CriarChecklist();
            $checklist = $action->handle($data);
            $this->setSuccess('Checklist registrado com sucesso.');
            return $checklist;
        } catch (\Exception $e) {
            $this->setError('Erro ao registrar checklist: ' . $e->getMessage());
            Log::error('Erro ao registrar checklist', [
                'error' => $e->getMessage(),
                'data' => $data,
                'user_id' => Auth::id(),
            ]);
        }
    }
}
