<?php

namespace App\Services\ItemOrdemServico;

use App\Models;
use App\Traits\ServiceResponseTrait;
use Illuminate\Support\Facades\Log;

class ItemOrdemServicoService
{
    use ServiceResponseTrait;

    public function __construct()
    {
        // Constructor logic if needed
    }

    public function create(array $data): ?Models\ItemOrdemServico
    {
        try {
            $item = (new Actions\CriarItem())->handle($data);
            $this->setSuccess('Item criado com sucesso!');
            return $item;
        } catch (\Exception $e) {
            dd($e->getMessage());
            Log::error(__METHOD__.'-'.__LINE__, [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
           $this->setError($e->getMessage());
           return null;
        }
    }

    public function update(int $itemId, array $data): ?Models\ItemOrdemServico
    {
        try {
            $item = Models\ItemOrdemServico::query()->findOrFail($itemId, 'id');
            $item = (new Actions\AtualizarItem($item))->handle($data);
            $this->setSuccess('Item atualizado com sucesso!');
            return $item;
        } catch (\Exception $e) {
            Log::error(__METHOD__, [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
           $this->setError($e->getMessage());
           return null;
        }
    }
}
