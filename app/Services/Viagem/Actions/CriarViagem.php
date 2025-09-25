<?php

namespace App\Services\Viagem\Actions;

use App\Models;
use App\Traits\UserCheckTrait;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CriarViagem
{
    use UserCheckTrait;

    protected array $allowedFields = [
        'veiculo_id',
        'numero_viagem',
        'documento_transporte',
        'km_rodado',
        'km_pago',
        'km_cadastro',
        'km_cobrar',
        'motivo_divergencia',
        'data_competencia',
        'data_inicio',
        'data_fim',
        'conferido',
        'created_by',
        'updated_by',
    ];

    public function handle(array $data): ?Models\Viagem
    {
        // Filtra apenas campos permitidos
        $filteredData = collect($data)->only($this->allowedFields)->toArray();

        $this->validate($filteredData);

        $data = array_merge($filteredData, [
                'created_by' => $this->getUserIdChecked(),
                'updated_by' => $this->getUserIdChecked(),
            ]);

        Log::debug('Criando nova viagem.', $data);
        
        $viagem = Models\Viagem::create(
            $data
        );

        return $viagem;
    }

    private function validate(array $data): bool
    {
        Validator::make($data, [
            'veiculo_id'            => 'required|exists:veiculos,id',
            'numero_viagem'         => 'required|string|unique:viagens,numero_viagem',
            'documento_transporte'  => 'nullable|string',
            'km_rodado'             => 'required|numeric|min:0',
            'km_pago'               => 'required|numeric|min:0',
            'km_cadastro'           => 'required|numeric|min:0',
            'km_cobrar'             => 'required|numeric|min:0',
            'motivo_divergencia'    => 'required|string',
            'data_competencia'      => 'required|date',
            'data_inicio'           => 'required|date',
            'data_fim'              => 'required|date|after_or_equal:data_inicio',
            'conferido'             => 'boolean',
        ])->validate();

        Log::debug('Validação de criação de viagem concluída com sucesso.', $data['numero_viagem'] ?? []);

        return true;
    }
}
