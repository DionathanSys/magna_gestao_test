<?php

namespace App\Services\Abastecimento;

use App\{Models, Services};
use App\Traits\ServiceResponseTrait;
use Illuminate\Support\Facades\Log;

class AbastecimentoService
{
    use ServiceResponseTrait;

    protected Services\HistoricoQuilometragem\HistoricoQuilometragemService $historicoQuilometragemService;

    public function criar(array $data): ?Models\Abastecimento
    {
         try {

            $action = new Action\CriarAbastecimento();
            $abastecimento = $action->handle($data);

            if($action->hasErrors) {
                $this->setError('Erro ao criar abastecimento', $action->errors);
                return null;
            }

            Log::info('Abastecimento criado com sucesso ID: '. $abastecimento->id_abastecimento ?? 'null', [
                'metodo'        => __METHOD__.'@'.__LINE__,
                'abastecimento' => $abastecimento,
            ]);

            $this->setSuccess('Abastecimento criado com sucesso');

            //TODO: Mover para Evento e Listener
            $dataQuilometragem = [
                'veiculo_id'        => $abastecimento->veiculo_id,
                'data_referencia'   => $abastecimento->data_abastecimento,
                'quilometragem'     => $abastecimento->quilometragem,
            ];
            
            Log::debug('Registrando histórico de quilometragem após abastecimento', [
                'dataQuilometragem' => $dataQuilometragem,
            ]);

            $this->historicoQuilometragemService->registrar($dataQuilometragem);

            return $abastecimento;

         } catch (\Exception $e) {
            Log::error('Erro ao criar abastecimento: ' . $e->getMessage(), [
                'metodo' => __METHOD__,
                'data'   => $data,
            ]);
            $this->setError('Erro interno ao criar abastecimento');
            return null;
         }
    } 

}