<?php

namespace App\Services\Import\Importers;

use App\Contracts\ExcelImportInterface;
use App\Models\EstoqueProduto;
use App\Models\ImportLog;
use App\Services\Estoque\EstoqueProdutoService;
use App\Services\Import\Importers\Concerns\NormalizesEstoqueImportValues;
use App\Traits\ServiceResponseTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class EstoqueMovimentacaoDiariaImporter implements ExcelImportInterface
{
    use NormalizesEstoqueImportValues;
    use ServiceResponseTrait;

    private ?int $importLogId = null;

    private ?Carbon $dataMovimento = null;

    public function __construct(
        private EstoqueProdutoService $produtoService,
    ) {}

    public function setImportContext(int $importLogId): void
    {
        $this->importLogId = $importLogId;
        $options = ImportLog::query()->find($importLogId)?->options ?? [];

        if (is_string($options)) {
            $options = json_decode($options, true) ?: [];
        }

        $dataMovimento = $options['data_movimento'] ?? null;
        $this->dataMovimento = $dataMovimento ? Carbon::parse($dataMovimento) : now()->subDay();
    }

    public function getRequiredColumns(): array
    {
        return ['codigo', 'entrada', 'saida'];
    }

    public function getRequiredColumnAliases(): array
    {
        return [
            'codigo' => ['CdProduto', 'CdgProduto', 'CodProduto', 'CodigoProduto', 'CdigoProduto', 'cdProduto', 'codigo', 'Codigo', 'Cdigo'],
            'entrada' => ['QtdEntradaD1', 'QuantidadeEntradaD1', 'EntradaD1', 'QtdEntradaDia', 'QuantidadeEntradaDia', 'EntradaDia', 'Entrada', 'Entrou', 'QuantidadeEntrada', 'QtdeEntrada', 'QtdEntrada', 'entrada'],
            'saida' => ['QtdSadaD1', 'QtdSaidaD1', 'QuantidadeSadaD1', 'QuantidadeSaidaD1', 'SaidaD1', 'SadaD1', 'QtdSadaDia', 'QtdSaidaDia', 'QuantidadeSadaDia', 'QuantidadeSaidaDia', 'SaidaDia', 'SadaDia', 'Saida', 'Sada', 'Saiu', 'QuantidadeSaida', 'QuantidadeSada', 'QtdeSaida', 'QtdSaida', 'saida'],
        ];
    }

    public function getOptionalColumns(): array
    {
        return ['Nome', 'Produto'];
    }

    public function shouldSkipRow(array $row): bool
    {
        return blank($this->value($row, $this->getRequiredColumnAliases()['codigo']));
    }

    public function validate(array $row, int $rowNumber): array
    {
        $transformedData = $this->transform($row);
        $validator = Validator::make($transformedData, [
            'codigo' => 'required|string',
            'quantidade_entrada' => 'numeric|min:0',
            'quantidade_saida' => 'numeric|min:0',
        ], [
            'codigo.required' => "O código do produto é obrigatório na linha {$rowNumber}.",
        ]);

        $errors = $validator->fails() ? $validator->errors()->all() : [];

        if ($transformedData['quantidade_entrada'] <= 0 && $transformedData['quantidade_saida'] <= 0) {
            $errors[] = "A linha {$rowNumber} precisa ter entrada ou saída maior que zero.";
        }

        return $errors;
    }

    public function transform(array $row): array
    {
        return [
            'codigo' => $this->normalizeIdentifier($this->value($row, $this->getRequiredColumnAliases()['codigo'])),
            'nome' => $this->normalizeString($this->value($row, ['Nome', 'Produto', 'Descricao', 'Descrio'])),
            'quantidade_entrada' => $this->toDecimal($this->value($row, $this->getRequiredColumnAliases()['entrada'])),
            'quantidade_saida' => $this->toDecimal($this->value($row, $this->getRequiredColumnAliases()['saida'])),
        ];
    }

    public function process(array $data, int $rowNumber): mixed
    {
        if ($this->importLogId === null) {
            $this->setError('Contexto de importação não definido para movimentação de estoque.');

            return null;
        }

        $errors = $this->validate($data, $rowNumber);

        if (! empty($errors)) {
            $this->setError("Erros de validação na linha {$rowNumber}.", $errors);

            return null;
        }

        $transformedData = $this->transform($data);
        $produto = EstoqueProduto::query()->firstOrCreate([
            'codigo' => $transformedData['codigo'],
        ], [
            'nome' => $transformedData['nome'] ?: 'Produto '.$transformedData['codigo'],
            'ativo' => true,
        ]);

        if ($transformedData['nome'] && $produto->nome !== $transformedData['nome']) {
            $produto->forceFill(['nome' => $transformedData['nome']])->save();
        }

        return $this->produtoService->registrarMovimento(
            $produto,
            $this->dataMovimento ?? now()->subDay(),
            $transformedData['quantidade_entrada'],
            $transformedData['quantidade_saida'],
            $this->importLogId,
        );
    }
}
