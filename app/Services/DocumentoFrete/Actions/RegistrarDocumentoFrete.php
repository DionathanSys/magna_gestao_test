<?php

namespace App\Services\DocumentoFrete\Actions;

use App\Models;
use Illuminate\Support\Facades\Validator;

class RegistrarDocumentoFrete
{

    public function handle(array $data): ?Models\DocumentoFrete
    {
        $this->validate($data);
        return Models\DocumentoFrete::create($data);
    }

    private function validate(array $data): void
    {
        Validator::make($data, [
            'veiculo_id' => 'required|exists:veiculos,id',
            'integrado_id' => 'required|exists:integrados,id',
            'numero_documento' => 'required|string|max:50',
            'data_emissao' => 'required|date',
            'valor_total' => 'required|numeric|min:0',
            'valor_icms' => 'nullable|numeric|min:0',
        ])->validate();
    }
}
