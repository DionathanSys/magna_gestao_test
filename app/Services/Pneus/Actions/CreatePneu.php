<?php

namespace App\Services\Pneus\Actions;

use App\Enum;
use App\Models;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CreatePneu
{
    public $message = '';

    public $hasError = false;

    public $errors = [];

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
            'numero_fogo' => 'required|integer|unique:pneus,numero_fogo',
            'medida' => 'nullable|required_without:pneu_medida_id',
            'marca' => 'nullable|required_without:pneu_marca_id',
            'modelo' => 'nullable',
            'valor' => 'nullable|numeric|min:0',
            'desenho_pneu_id' => 'required|exists:desenhos_pneu,id',
            'local' => 'nullable|in:'.implode(',', array_column(Enum\Pneu\LocalPneuEnum::cases(), 'value')),
            'status' => 'required|in:'.implode(',', array_column(Enum\Pneu\StatusPneuEnum::cases(), 'value')),
            'ciclo_vida' => 'required|integer|min:0',
            'data_aquisicao' => 'required|date',
            'pneu_marca_id' => 'nullable|exists:pneu_marcas,id',
            'pneu_modelo_id' => [
                'nullable',
                Rule::exists('pneu_modelos', 'id')->where(function ($query) use ($data) {
                    if (! empty($data['pneu_marca_id'])) {
                        $query->where('pneu_marca_id', $data['pneu_marca_id']);
                    }
                }),
            ],
            'pneu_medida_id' => 'nullable|exists:pneu_medidas,id',
            'pneu_local_id' => 'nullable|exists:pneu_locais,id',
            'fornecedor_compra_id' => 'nullable|exists:parceiros,id',
            'sulco_inicial' => 'nullable|numeric|min:0',
            'recapavel' => 'nullable|boolean',
            'limite_recapagens' => 'nullable|integer|min:0|max:9',
        ]);

        if ($validator->fails()) {
            $this->hasError = true;
            $this->message = 'Erro de validação ao cadastrar pneu.';
            $this->errors = $validator->errors()->all();

            Log::error('Erro de validação ao cadastrar pneu', [
                'metodo' => __METHOD__.'@'.__LINE__,
                'data' => $data,
                'errors' => $this->errors,
            ]);
        }
    }
}
