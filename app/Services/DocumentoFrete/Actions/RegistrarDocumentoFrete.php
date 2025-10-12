<?php

namespace App\Services\DocumentoFrete\Actions;

use App\{Models, Enum};
use Illuminate\Support\Facades\Log;
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

        $validate = Validator::make($data, [
            'veiculo_id'            => 'required|exists:veiculos,id',
            'parceiro_destino'      => 'required|string|max:200',
            'parceiro_origem'       => 'required|string|max:200',
            'numero_documento'      => 'required|string|max:50',
            'documento_transporte'  => 'nullable|integer',
            'data_emissao'          => 'required|date',
            'valor_total'           => 'required|numeric|min:0',
            'valor_icms'            => 'nullable|numeric|min:0',
            'tipo_documento'        => 'required',
        ],[
            'veiculo_id.required'           => 'O campo Veículo é obrigatório.',
            'veiculo_id.exists'             => 'O veículo informado não existe.',
            'parceiro_destino.required'     => 'O campo Parceiro Destino é obrigatório.',
            'parceiro_destino.string'       => 'O campo Parceiro Destino deve ser uma string.',
            'parceiro_destino.max'          => 'O campo Parceiro Destino deve ter no máximo 200 caracteres.',
            'parceiro_origem.required'      => 'O campo Parceiro Origem é obrigatório.',
            'parceiro_origem.string'        => 'O campo Parceiro Origem deve ser uma string.',
            'parceiro_origem.max'           => 'O campo Parceiro Origem deve ter no máximo 200 caracteres.',
            'numero_documento.required'     => 'O campo Número do Documento é obrigatório.',
            'numero_documento.string'       => 'O campo Número do Documento deve ser uma string.',
            'numero_documento.max'          => 'O campo Número do Documento deve ter no máximo 50 caracteres.',
            'documento_transporte.integer'  => 'O campo Documento de Transporte deve ser um número inteiro.',
            'data_emissao.required'         => 'O campo Data de Emissão é obrigatório.',
            'data_emissao.date'             => 'O campo Data de Emissão deve ser uma data válida.',
            'valor_total.required'          => 'O campo Valor Total é obrigatório.',
            'valor_total.numeric'           => 'O campo Valor Total deve ser um número.',
            'valor_total.min'               => 'O campo Valor Total deve ser no mínimo 0.',
            'valor_icms.numeric'            => 'O campo Valor do ICMS deve ser um número.',
            'valor_icms.min'                => 'O campo Valor do ICMS deve ser no mínimo 0.',
        ]);

        if($validate->fails()) {
            Log::error('Erro ao validar os dados para registrar o documento de frete.', [
                'metodo' => __METHOD__.'@'.__LINE__,
                'errors' => $validate->errors(),
                'data' => $data
            ]);
            throw new \InvalidArgumentException($validate->errors()->first());
        }

        if ($this->existsDocumento($data)) {
            Log::error('Documento de frete já cadastrado.', [
                'metodo' => __METHOD__.'@'.__LINE__,
                'data' => $data,
            ]);
            throw new \InvalidArgumentException('Documento de frete já cadastrado para este veículo e parceiro de origem.');
        }

    }

    private function existsDocumento(array $data): bool
    {
        $documento = Models\DocumentoFrete::query()
            ->where('veiculo_id', $data['veiculo_id'])
            ->where('numero_documento', $data['numero_documento'])
            ->where('parceiro_origem', $data['parceiro_origem'])
            ->first();

        return $documento !== null;
    }
}
