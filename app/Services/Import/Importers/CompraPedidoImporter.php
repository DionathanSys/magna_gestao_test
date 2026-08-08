<?php

namespace App\Services\Import\Importers;

use App\Contracts\ExcelImportInterface;
use App\Models\CompraPedido;
use App\Models\EstoqueProduto;
use App\Models\ImportLog;
use App\Services\Import\Importers\Concerns\NormalizesEstoqueImportValues;
use App\Traits\ServiceResponseTrait;
use Illuminate\Support\Facades\Validator;

class CompraPedidoImporter implements ExcelImportInterface
{
    use NormalizesEstoqueImportValues;
    use ServiceResponseTrait;

    private ?string $numeroPedido = null;

    public function setImportContext(int $importLogId): void
    {
        $options = ImportLog::query()->find($importLogId)?->options ?? [];

        if (is_string($options)) {
            $options = json_decode($options, true) ?: [];
        }

        $this->numeroPedido = $this->normalizeIdentifier($options['numero_pedido'] ?? null);
    }

    public function getRequiredColumns(): array
    {
        return ['produto', 'descricao', 'quantidade'];
    }

    public function getRequiredColumnAliases(): array
    {
        return [
            'produto' => ['Produto', 'CdProduto', 'CdgProduto', 'CodProduto', 'CodigoProduto', 'CdigoProduto'],
            'descricao' => ['DescrioProduto', 'DescricaoProduto', 'Descrio', 'Descricao', 'Nome'],
            'quantidade' => ['Quantidade', 'Qtd', 'Qtde'],
        ];
    }

    public function getOptionalColumns(): array
    {
        return ['QtdPendente', 'VlrUnitrio', 'VlrTotal', 'UnidadePadro', 'NomeFantasia'];
    }

    public function shouldSkipRow(array $row): bool
    {
        return blank($this->value($row, $this->getRequiredColumnAliases()['produto']));
    }

    public function validate(array $row, int $rowNumber): array
    {
        $transformedData = $this->transform($row);

        $validator = Validator::make($transformedData, [
            'codigo' => 'required|string',
            'nome' => 'required|string',
            'quantidade_pedida' => 'required|numeric|min:0.0001',
        ], [
            'codigo.required' => "O código do produto é obrigatório na linha {$rowNumber}.",
            'nome.required' => "A descrição do produto é obrigatória na linha {$rowNumber}.",
            'quantidade_pedida.min' => "A quantidade do item precisa ser maior que zero na linha {$rowNumber}.",
        ]);

        return $validator->fails() ? $validator->errors()->all() : [];
    }

    public function transform(array $row): array
    {
        return [
            'codigo' => $this->normalizeIdentifier($this->value($row, $this->getRequiredColumnAliases()['produto'])),
            'nome' => $this->normalizeString($this->value($row, $this->getRequiredColumnAliases()['descricao'])),
            'quantidade_pedida' => $this->toDecimal($this->value($row, $this->getRequiredColumnAliases()['quantidade'])),
        ];
    }

    public function process(array $data, int $rowNumber): mixed
    {
        if (blank($this->numeroPedido)) {
            $this->setError('Número do pedido não informado para a importação.');

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
            'nome' => $transformedData['nome'],
            'ativo' => true,
        ]);

        if ($produto->nome !== $transformedData['nome']) {
            $produto->forceFill(['nome' => $transformedData['nome']])->save();
        }

        $pedido = CompraPedido::query()->firstOrCreate([
            'numero' => $this->numeroPedido,
        ], [
            'status' => 'aberto',
        ]);

        $item = $pedido->itens()->updateOrCreate([
            'estoque_produto_id' => $produto->id,
        ], [
            'quantidade_pedida' => $transformedData['quantidade_pedida'],
        ]);

        $pedido->refresh()->atualizarAtendimento();

        return $item;
    }
}
