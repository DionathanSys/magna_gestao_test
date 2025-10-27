<?php

namespace App\Services\HistoricoQuilometragem;

use App\{Models, Services};
use App\Traits\ServiceResponseTrait;
use Illuminate\Support\Facades\Log;

class HistoricoQuilometragemService
{
    use ServiceResponseTrait;

    public function registrar(array $data): ?Models\HistoricoQuilometragem
    {
        try {
            $action = new Action\RegistrarQuilometragem();
            $quilometragem = $action->handle($data);

            if ($action->hasErrors) {
                $this->setError('Erro ao registrar quilometragem', $action->errors);
                return null;
            }

            Log::info('Quilometragem registrada com sucesso ID: ' . $quilometragem->id ?? 'null', [
                'metodo'        => __METHOD__ . '@' . __LINE__,
                'quilometragem' => $quilometragem,
            ]);
            
            $this->setSuccess('Quilometragem registrada com sucesso');

            return $quilometragem;

        } catch (\Exception $e) {
            Log::error('Erro ao registrar quilometragem: ' . $e->getMessage(), [
                'metodo' => __METHOD__.'@'.__LINE__,
                'data'   => $data,
            ]);
            $this->setError('Erro interno ao registrar quilometragem');
            return null;
        }
    }
}