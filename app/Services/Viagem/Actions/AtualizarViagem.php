<?php

namespace App\Services\Viagem\Actions;

use App\Models;
use App\Traits\UserCheckTrait;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class AtualizarViagem
{
    use UserCheckTrait;

    protected static ?array $viagensColumns = null;

    protected array $allowedFields = [
        'veiculo_id',
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

    public function __construct(protected Models\Viagem $viagem) {}

    public function handle(array $data): ?Models\Viagem
    {
        // Filtra apenas campos permitidos
        $filteredData = collect($data)->only($this->allowedFields)->toArray();

        $this->validate($filteredData);

        $data = array_merge($filteredData, [
            'updated_by' => $this->getUserIdChecked(),
        ]);

        $data = $this->normalizeForPersistence($data);

        $this->viagem->update($data);

        return $this->viagem;
    }

    private function validate(array $data): bool
    {
        Validator::make($data, [
            'veiculo_id'            => 'required|exists:veiculos,id',
            'numero_viagem'         => 'required|string',
            'numero_interno'        => 'nullable|string|unique:viagens,numero_interno,' . $this->viagem->id,
            'documento_transporte'  => 'nullable|string',
            'km_rodado'             => 'nullable|numeric|min:0',
            'km_pago'               => 'nullable|numeric|min:0',
            'data_competencia'      => 'required|date',
            'data_inicio'           => 'required|date',
            'data_fim'              => 'required|date|after_or_equal:data_inicio',
            'total_destinos'        => 'nullable|integer|min:0',
            'conferido'             => 'boolean',
            'ignorar'               => 'boolean',
            'possui_pendencia'      => 'boolean',
            'pendencias'            => 'nullable|array',
            'motorista1'            => 'nullable|string',
            'motorista2'            => 'nullable|string',
        ], [
            'veiculo_id.required'           => 'O campo Veículo é obrigatório.',
            'veiculo_id.exists'             => 'Veículo não encontrado.',
            'numero_viagem.required'        => 'O campo Viagem é obrigatório.',
            'numero_viagem.string'          => 'O campo Viagem deve ser um texto válido.',
            'numero_interno.string'         => 'O número interno da viagem deve ser um texto válido.',
            'numero_interno.unique'         => 'O número interno da viagem já está em uso.',
            'km_rodado.numeric'             => 'O campo Km Rodado deve ser um número válido.',
            'km_rodado.min'                 => 'O campo Km Rodado deve ser maior ou igual a 0.',
            'km_pago.numeric'               => 'O campo Km Pago deve ser um número válido.',
            'km_pago.min'                   => 'O campo Km Pago deve ser maior ou igual a 0.',
            'data_competencia.required'     => 'O campo Data Competência é obrigatório.',
            'data_competencia.date'         => 'O campo Data Competência deve ser uma data válida.',
            'data_inicio.required'          => 'O campo Data Início é obrigatório.',
            'data_inicio.date'              => 'O campo Data Início deve ser uma data válida.',
            'data_fim.required'             => 'O campo Data Fim é obrigatório.',
            'data_fim.date'                 => 'O campo Data Fim deve ser uma data válida.',
            'data_fim.after_or_equal'       => 'O campo Data Fim deve ser uma data posterior ou igual à Data Início.',
            'conferido.boolean'             => 'O campo Conferido deve ser verdadeiro ou falso.',
            'ignorar.boolean'               => 'O campo Ignorar deve ser verdadeiro ou falso.',
            'motorista1.string'             => 'O campo Motorista 1 deve ser um texto válido.',
            'motorista2.string'             => 'O campo Motorista 2 deve ser um texto válido.',
        ])->validate();

        if ($this->viagem->conferida) {
            throw new \Exception("Viagem Nº {$this->viagem->numero_viagem} já conferida, não pode ser atualizada.");
        }

        return true;
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
