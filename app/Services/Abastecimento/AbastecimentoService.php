<?php

namespace App\Services\Abastecimento;

use App\Models;
use App\Traits\ServiceResponseTrait;
use Illuminate\Support\Facades\Log;

class AbastecimentoService
{
    use ServiceResponseTrait;

    public function criar(array $data): ?Models\Abastecimento
    {
         try {
            $action = new Action\CriarAbastecimento();
            $abastecimento = $action->handle($data);

            if($action->hasErrors) {
                $this->setError('Erro ao criar abastecimento', $action->errors);
                return null;
            }

            $this->setSuccess('Abastecimento criado com sucesso');
            return $abastecimento;

         } catch (\Exception $e) {
            Log::error('Erro ao criar abastecimento: ' . $e->getMessage());
            $this->setError('Erro interno ao criar abastecimento');
            return null;
         }
    } 

}