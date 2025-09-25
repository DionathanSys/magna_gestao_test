<?php

namespace App\Services\Carga;

use App\Models;
use App\Traits\UserCheckTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CargaService
{
    use UserCheckTrait;

    public Models\CargaViagem $cargaViagem;

    public function __construct()
    {
        $this->cargaViagem = new Models\CargaViagem();
    }

    public function create(?Models\Integrado $integrado, Models\Viagem $viagem): ?Models\CargaViagem
    {

        // Lógica
        /**
         * Cargas podem ser criadas/atualizadas em dois momentos:
         * 1. Quando a viagem é criada através do importador de viagens, onde pode ou não possuir integrado
         * 2. Quando a viagem é atualizada através do importador de viagens, onde pode ou não possuir integrado
         * 3. Quando o integrado é vinculado à viagem no momento de conferência manual da viagem
         *
         * 01 viagem pode ter 1 ou mais integrados (cargas)
         */
        try {

            $cargaViagem = $this->cargaViagem
                ->where('viagem_id', $viagem->id)
                ->where('integrado_id', null)
                ->first();

            if ($cargaViagem) {
                Log::info("Carga de viagem existente para a viagem ID {$viagem->id} porém sem integrado, atualizando integrado ID " . $integrado->id ?? 'null');
                $cargaViagem->update([
                    'integrado_id' => $integrado->id ?? null,
                    'updated_by'   => $this->getUserIdChecked(),
                ]);
                return $cargaViagem;

            } else {
                Log::info("Será criado nova carga de viagem para a viagem ID {$viagem->id} com integrado ID {$integrado->id}");
            }

            return $this->cargaViagem->query()->updateOrCreate(
                [
                    'viagem_id'    => $viagem->id,
                    'integrado_id' => $integrado->id ?? null,
                ],
                [
                    'viagem_id'     => $viagem->id,
                    'integrado_id'  => $integrado->id ?? null,
                    'created_by'    => $this->getUserIdChecked(),
                    'updated_by'    => $this->getUserIdChecked(),
                ]
            );

        } catch (\Exception $e) {
            Log::error('Erro ao criar carga de viagem: ' . $e->getMessage(), [
                'metodo'       => __METHOD__. ' - ' . __LINE__,
                'viagem_id'    => $viagem->id,
                'integrado_id' => $integrado->id ?? null,
            ]);
            return null;
        }
    }
}
