<?php

namespace App\Services\Checklist;

use App\{Models, Services, Enum};
use App\Traits\ServiceResponseTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ChecklistService
{
    use ServiceResponseTrait;

    public function registrarChecklist(array $data)
    {
        DB::beginTransaction();

        try {
            $action = new Actions\CriarChecklist();
            $checklist = $action->handle($data);

            Log::debug(__METHOD__. ' - ' . __LINE__, [
                'checklist' => $checklist,
            ]);

            $this->setSuccess('Checklist registrado com sucesso.');

            $service = new Services\Veiculo\VeiculoService();
            $service->setDataUltimoChecklist($data['veiculo_id'], $data['data_referencia']);

            Log::debug("Atualizado data do último checklist");
            Log::debug("Pendencias encontradas: " . $checklist->pendencias_count);

            if ($checklist->pendencias_count > 0) {
                $action = new Actions\AgendarPendenciasChecklist();
                $action->handle($checklist->id, $checklist->veiculo_id, $checklist->pendencias);
                
            }

            $this->setSuccess('Data do último checklist atualizada com sucesso.');

            DB::commit();

            return $checklist;
        } catch (\Exception $e) {
            DB::rollback();
            $this->setError('Erro ao registrar checklist: ' . $e->getMessage());
            Log::error('Erro ao registrar checklist', [
                'error' => $e->getMessage(),
                'data' => $data,
                'user_id' => Auth::id(),
            ]);
        }
    }
}
