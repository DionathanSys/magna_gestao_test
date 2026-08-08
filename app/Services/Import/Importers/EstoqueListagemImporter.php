<?php

namespace App\Services\Import\Importers;

use App\Contracts\ExcelImportInterface;
use App\Models\EstoqueProduto;
use App\Services\Estoque\EstoqueProdutoService;
use App\Services\Import\Importers\Concerns\NormalizesEstoqueImportValues;
use App\Traits\ServiceResponseTrait;
use Illuminate\Support\Facades\Validator;

class EstoqueListagemImporter implements ExcelImportInterface
{
    use NormalizesEstoqueImportValues;
    use ServiceResponseTrait;

    public function __construct(
        private EstoqueProdutoService $produtoService,
    ) {}

    public function getRequiredColumns(): array
    {
        return ['codigo', 'nome', 'saldo'];
    }

    public function getRequiredColumnAliases(): array
    {
        return [
            'codigo' => ['CdProduto', 'CdgProduto', 'CodProduto', 'CodigoProduto', 'CdigoProduto', 'cdProduto', 'codigo', 'Codigo', 'Cdigo'],
            'nome' => ['Nome', 'Produto', 'Descricao', 'Descrio', 'nome'],
            'saldo' => ['Estoque', 'Saldo', 'EstoqueAtual', 'saldo'],
        ];
    }

    public function getOptionalColumns(): array
    {
        return ['CustoReposicao', 'ValorEstoque'];
    }

    public function shouldSkipRow(array $row): bool
    {
        return blank($this->value($row, $this->getRequiredColumnAliases()['codigo']));
    }

    public function validate(array $row, int $rowNumber): array
    {
        $validator = Validator::make($this->transform($row), [
            'codigo' => 'required|string',
            'nome' => 'required|string',
        ], [
            'codigo.required' => "O código do produto é obrigatório na linha {$rowNumber}.",
            'nome.required' => "O nome do produto é obrigatório na linha {$rowNumber}.",
        ]);

        return $validator->fails() ? $validator->errors()->all() : [];
    }

    public function transform(array $row): array
    {
        return [
            'codigo' => $this->normalizeIdentifier($this->value($row, $this->getRequiredColumnAliases()['codigo'])),
            'nome' => $this->normalizeString($this->value($row, $this->getRequiredColumnAliases()['nome'])),
            'saldo' => $this->toDecimal($this->value($row, $this->getRequiredColumnAliases()['saldo'])),
            'valor_reposicao_centavos' => $this->toCents($this->value($row, ['CustoReposio', 'CustoReposicao', 'ValorReposicao', 'ValorReposio', 'VlrReposicao', 'VlrReposio'])),
            'custo_total_centavos' => $this->toCents($this->value($row, ['ValorEstoque', 'CustoTotal', 'Custototal', 'Custo', 'CustoTotalR', 'VlrCustoTotal'])),
        ];
    }

    public function process(array $data, int $rowNumber): ?EstoqueProduto
    {
        $errors = $this->validate($data, $rowNumber);

        if (! empty($errors)) {
            $this->setError("Erros de validação na linha {$rowNumber}.", $errors);

            return null;
        }

        $transformedData = $this->transform($data);

        $produto = EstoqueProduto::query()->updateOrCreate([
            'codigo' => $transformedData['codigo'],
        ], [
            'nome' => $transformedData['nome'],
            'saldo' => $transformedData['saldo'],
            'valor_reposicao_centavos' => $transformedData['valor_reposicao_centavos'],
            'custo_total_centavos' => $transformedData['custo_total_centavos'],
            'ativo' => true,
        ]);

        $this->produtoService->atualizarIndicadores($produto);

        return $produto;
    }
}
