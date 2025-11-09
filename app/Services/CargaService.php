<?php

namespace App\Services;

use App\Models\CargaViagem;
use App\Models\Integrado;
use App\Models\Viagem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CargaService
{
    public CargaViagem $cargaViagem;

    public function __construct()
    {
        $this->cargaViagem = new CargaViagem();
    }

    public function create(?Integrado $integrado, Viagem $viagem): ?CargaViagem
    {

        try {

            $cargaViagem = $this->cargaViagem
                ->where('viagem_id', $viagem->id)
                ->where('integrado_id', null)
                ->first();

            if ($cargaViagem) {
                Log::info("Carga de viagem já existe para a viagem ID {$viagem->id}, atualizando integrado ID " . $integrado->id ?? 'null');
                $cargaViagem->update([
                    'integrado_id' => $integrado->id ?? null,
                    'updated_by'   => Auth::user()->id,
                ]);
                return $cargaViagem;

            } else {
                Log::info("Será criado nova carga de viagem para a viagem ID {$viagem->id} com integrado ID {$integrado->id}");
            }

            return $this->cargaViagem->query()->updateOrCreate(
                [
                    'viagem_id'    => $viagem->id,
                    'integrado_id' => $integrado->id ?? 0,
                ],
                [
                    'viagem_id'     => $viagem->id,
                    'integrado_id'  => $integrado->id ?? null,
                    'created_by'    => Auth::user()->id,
                    'updated_by'    => Auth::user()->id,
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

    public static function incluirCargaViagem(int $integrado_id, Viagem $viagem): ?CargaViagem
    {
        try {
            $cargaViagem = new self();
            $itegrado = Integrado::find($integrado_id);

            return $cargaViagem->create($itegrado, $viagem);

        } catch (\Exception $e) {
            Log::error('Erro ao incluir carga de viagem: ' . $e->getMessage(), [
                'metodo'       => __METHOD__. ' - ' . __LINE__,
                'viagem_id'    => $viagem->id,
                'integrado_id' => $integrado_id,
            ]);
            return null;
        }
        
    }

    
}
