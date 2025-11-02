<?php

namespace App\Services\Pneus\Actions;

use App\Models;
use App\Enum;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CreatePneu
{

    public $message     = '';
    public $hasError    = false;
    public $errors      = [];
    
    public function handle(array $data): ?Models\Pneu
    {
        $this->validate($data);

        if ($this->hasError) {
            return null;
        }

        return Models\Pneu::create($data);
    }

    private function validate(array $data): void
    {
        $validator = Validator::make($data, [
            'numero_fogo'       => 'required|integer|unique:pneus,numero_fogo',
            'medida'            => 'required',
            'marca'             => 'required',
            'modelo'            => 'nullable',
            'valor'             => 'nullable|numeric|min:0',
            'desenho_pneu_id'   => 'required|exists:desenhos_pneu,id',
            'local'             => 'required|in:' . implode(',', array_column(Enum\Pneu\LocalPneuEnum::cases(), 'value')),
            'status'            => 'required|in:' . implode(',', array_column(Enum\Pneu\StatusPneuEnum::cases(), 'value')),
            'ciclo_vida'        => 'required|integer|min:0',
            'data_aquisicao'    => 'required|date',
        ]);

        if($validator->fails()) {
            $this->hasError = true;
            $this->message = 'Erro de validação ao criar pneu.';
            $this->errors = $validator->errors()->all();

            Log::error('Erro de validação ao criar pneu', [
                'metodo' => __METHOD__ . '@' . __LINE__,
                'data' => $data,
                'errors' => $this->errors
            ]);
        }
    }
}
