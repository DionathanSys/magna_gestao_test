<?php

namespace App\Services\ItemOrdemServico\Actions;

use App\Models;
use Illuminate\Support\Facades\Validator;

class AtualizarItem
{

    public function __construct(protected Models\ItemOrdemServico $itemOrdemServico)
    {
    }

    public function handle(array $data): Models\ItemOrdemServico
    {
        $this->validate($data);

        $this->itemOrdemServico->update($data);

        return $this->itemOrdemServico;
    }

    protected function validate(array $data): void
    {
        // Validator::make($data, [
        //     'ordem_servico_id' => 'required|exists:ordens_servico,id',
        //     'servico_id' => 'required|exists:servicos,id',
        //     'observacao' => 'nullable|string|max:255',
        // ])->validate();
    }
}
