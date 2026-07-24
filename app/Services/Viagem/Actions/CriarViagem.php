<?php

namespace App\Services\Viagem\Actions;

use App\Models;
use App\Services\ViagemNumberService;
use App\Traits\UserCheckTrait;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class CriarViagem
{
    use UserCheckTrait;

    protected static ?array $viagensColumns = null;

    public array $errors = [];

    public bool $hasError = false;

    protected array $allowedFields = [
        'veiculo_id',
        'unidade_negocio',
        'cliente',
        'numero_viagem',
        'numero_interno',
        'documento_transporte',
        'km_rodado',
        'km_pago',
        'data_competencia',
        'data_inicio',
        'data_fim',
        'total_destinos',
        'conferido',
        'ignorar',
        'possui_pendencia',
        'pendencias',
        'motorista1',
        'motorista2',
        'created_by',
        'updated_by',
    ];

    public function handle(array $data): ?Models\Viagem
    {
        // Filtra apenas campos permitidos
        $filteredData = Arr::only($data, $this->allowedFields);

        $this->validate($filteredData);

        if ($this->hasError === false) {
            if (empty($filteredData['numero_interno'])) {
                $numeroInterno = (new ViagemNumberService)->next();
                $filteredData['numero_interno'] = $numeroInterno['numero_viagem'];
            }

            $data = [
                ...$this->normalizeForPersistence($filteredData),
                'created_by' => $this->getUserIdChecked(),
                'updated_by' => $this->getUserIdChecked(),
            ];

            Log::info(__METHOD__.'@'.__LINE__, [
                'data' => $data,
            ]);

            $viagem = Models\Viagem::create($data);

            return $viagem;
        }

        return null;
    }

    private function validate(array $data): void
    {
        $validator = Validator::make($data, [
            'veiculo_id' => 'required|exists:veiculos,id',
            'unidade_negocio' => 'required|string',
            'cliente' => 'nullable|string',
            'numero_viagem' => 'required|string|unique:viagens,numero_viagem',
            'numero_interno' => 'nullable|string|unique:viagens,numero_interno',
            'documento_transporte' => 'nullable|string',
            'km_rodado' => 'nullable|numeric|min:0',
            'km_pago' => 'nullable|numeric|min:0',
            'data_competencia' => 'required|date',
            'data_inicio' => 'required|date',
            'data_fim' => 'required|date|after_or_equal:data_inicio',
            'total_destinos' => 'nullable|integer|min:0',
            'conferido' => 'boolean',
            'ignorar' => 'boolean',
            'possui_pendencia' => 'boolean',
            'pendencias' => 'nullable|array',
            'motorista1' => 'nullable|string',
            'motorista2' => 'nullable|string',
        ], [
            'veiculo_id.required' => 'O campo Veículo é obrigatório.',
            'veiculo_id.exists' => 'Veículo não encontrado.',
            'unidade_negocio.required' => 'O campo Unidade de Negócio é obrigatório.',
            'unidade_negocio.string' => 'O campo Unidade de Negócio deve ser um texto válido.',
            'cliente.string' => 'O campo Cliente deve ser um texto válido.',
            'numero_viagem.required' => 'O campo Viagem é obrigatório.',
            'numero_viagem.string' => 'O campo Viagem deve ser um texto válido.',
            'numero_viagem.unique' => 'O número da Viagem já está em uso.',
            'numero_interno.string' => 'O número interno da viagem deve ser um texto válido.',
            'numero_interno.unique' => 'O número interno da viagem já está em uso.',
            'km_rodado.numeric' => 'O campo Km Rodado deve ser um número válido.',
            'km_rodado.min' => 'O campo Km Rodado deve ser maior ou igual a 0.',
            'km_pago.numeric' => 'O campo Km Pago deve ser um número válido.',
            'km_pago.min' => 'O campo Km Pago deve ser maior ou igual a 0.',
            'data_competencia.required' => 'O campo Data Competência é obrigatório.',
            'data_competencia.date' => 'O campo Data Competência deve ser uma data válida.',
            'data_inicio.required' => 'O campo Data Início é obrigatório.',
            'data_inicio.date' => 'O campo Data Início deve ser uma data válida.',
            'data_fim.required' => 'O campo Data Fim é obrigatório.',
            'data_fim.date' => 'O campo Data Fim deve ser uma data válida.',
            'data_fim.after_or_equal' => 'O campo Data Fim deve ser uma data posterior ou igual à Data Início.',
            'conferido.boolean' => 'O campo Conferido deve ser verdadeiro ou falso.',
            'ignorar.boolean' => 'O campo Ignorar deve ser verdadeiro ou falso.',
            'created_by.required' => 'O campo Criado Por é obrigatório.',
            'created_by.exists' => 'Usuário Criador não encontrado.',
            'updated_by.required' => 'O campo Atualizado Por é obrigatório.',
            'updated_by.exists' => 'Usuário Atualizador não encontrado.',
            'motorista1.string' => 'O campo Motorista 1 deve ser um texto válido.',
            'motorista2.string' => 'O campo Motorista 2 deve ser um texto válido.',
        ]);

        if ($validator->fails()) {
            Log::error(__METHOD__.'@'.__LINE__, [
                'error' => $validator->errors()->all(),
                'data' => $data,
            ]);
            $this->errors = $validator->errors()->all();
            $this->hasError = true;

            return;
        }

    }

    private function normalizeForPersistence(array $data): array
    {
        $columns = self::$viagensColumns ??= Schema::getColumnListing('viagens');

        if (! in_array('numero_interno', $columns, true) && in_array('numero_viagem_interno', $columns, true)) {
            $data['numero_viagem_interno'] = $data['numero_interno'] ?? null;
            unset($data['numero_interno']);
        }

        if (! in_array('total_destinos', $columns, true) && in_array('qtde_destino_viagem', $columns, true)) {
            $data['qtde_destino_viagem'] = $data['total_destinos'] ?? null;
            unset($data['total_destinos']);
        }

        if (! in_array('ignorar', $columns, true) && in_array('ignorar_viagem', $columns, true)) {
            $data['ignorar_viagem'] = $data['ignorar'] ?? false;
            unset($data['ignorar']);
        }

        if (! in_array('pendencias', $columns, true) && in_array('divergencias', $columns, true)) {
            $data['divergencias'] = $data['pendencias'] ?? [];
            unset($data['pendencias']);
        }

        if (array_key_exists('pendencias', $data) && ! in_array('pendencias', $columns, true)) {
            unset($data['pendencias']);
        }

        if (array_key_exists('motorista1', $data) && ! in_array('motorista1', $columns, true)) {
            unset($data['motorista1']);
        }

        if (array_key_exists('motorista2', $data) && ! in_array('motorista2', $columns, true)) {
            unset($data['motorista2']);
        }

        return $data;
    }
}
