<?php

namespace App\Imports;

use App\Contracts\XlsxImportInterface;
use Illuminate\Support\Facades\Log;

abstract class BaseXlsxImport implements XlsxImportInterface
{
    public function validateColumns(array $firstRow): void
    {
        $required = static::columns();

        Log::debug(__METHOD__, [
            'required_columns' => $required,
            'first_row' => $firstRow
        ]);

        foreach ($required as $col) {
            if (!in_array($col, $firstRow)) {
                throw new \Exception("Coluna obrigatória '$col' não encontrada no relatório.");
            }
        }
    }

    public function mapRowData(array $row): array
    {
        $map = static::columnMap();
        $dadosConvertidos = [];

        Log::debug(__METHOD__, [
            'map' => $map,
            'row' => $row
        ]);

        foreach ($row as $colunaRelatorio => $valor) {
            if (!isset($map[$colunaRelatorio])) {
                continue; // Ignora colunas não mapeadas
            }

            $config = $map[$colunaRelatorio];
            $columnName = $config['column'];
            $type = $config['type'] ?? 'string';

            try {
                // Processa o valor baseado no tipo
                switch ($type) {
                    case 'relationship':
                        $dadosConvertidos[$columnName] = $this->processRelationship($valor, $config);
                        break;

                    case 'boolean':
                        $dadosConvertidos[$columnName] = $this->processBoolean($valor, $config);
                        break;

                    case 'enum':
                        $dadosConvertidos[$columnName] = $this->processEnum($valor, $config);
                        break;

                    case 'date':
                        $dadosConvertidos[$columnName] = $this->processDate($valor, $config);
                        break;

                    case 'decimal':
                        $dadosConvertidos[$columnName] = $this->processDecimal($valor, $config);
                        break;

                    case 'string':
                    default:
                        $dadosConvertidos[$columnName] = $this->processString($valor, $config);
                        break;
                }
            } catch (\Exception $e) {
                throw new \Exception("Erro na coluna '{$colunaRelatorio}': " . $e->getMessage());
            }
        }

        return $dadosConvertidos;
    }

    protected function processRelationship($valor, array $config)
    {
        if (empty($valor) && ($config['nullable'] ?? false)) {
            return null;
        }

        if (empty($valor) && ($config['required'] ?? true)) {
            throw new \Exception("Valor obrigatório não pode estar vazio");
        }

        $model = $config['model'];
        $searchColumn = $config['search_column'] ?? 'id';

        $record = $model::where($searchColumn, $valor)->first();

        if (!$record) {
            throw new \Exception("Registro não encontrado para {$searchColumn}: '{$valor}'");
        }

        return $record->id;
    }

    protected function processBoolean($valor, array $config)
    {
        // Verifica se o valor está vazio e se há um valor padrão
        if (empty($valor) && isset($config['default'])) {
            return $config['default'];
        }

        // Converte string para boolean
        if (is_string($valor)) {
            $valor = strtolower(trim($valor));
            return in_array($valor, ['1', 'true', 'sim', 's', 'yes', 'y']);
        }

        return (bool) $valor;
    }

    protected function processEnum($valor, array $config)
    {
        if (empty($valor) && ($config['nullable'] ?? false)) {
            return null;
        }

        $enumClass = $config['enum_class'];

        // Verifica se o valor é válido no enum
        if (!in_array($valor, array_column($enumClass::cases(), 'value'))) {
            throw new \Exception("Valor '{$valor}' não é válido para o enum");
        }

        return $valor;
    }

    protected function processDate($valor, array $config)
    {
        if (empty($valor) && ($config['nullable'] ?? false)) {
            return null;
        }

        try {
            // Se o valor é um número (formato serial do Excel)
            if (is_numeric($valor)) {
                return $this->convertExcelSerialToDate($valor);
            }

            // Se o valor é uma string, tenta converter pelo formato especificado
            $format = $config['format'] ?? 'Y-m-d';
            $date = \DateTime::createFromFormat($format, $valor);

            if (!$date) {
                // Se falhou, tenta outros formatos comuns
                $commonFormats = ['d/m/Y', 'm/d/Y', 'Y-m-d', 'd-m-Y'];
                foreach ($commonFormats as $tryFormat) {
                    $date = \DateTime::createFromFormat($tryFormat, $valor);
                    if ($date) break;
                }
            }

            if (!$date) {
                throw new \Exception("Data inválida. Formato esperado: {$format}");
            }

            return $date->format($format);
            // return $date->format('Y-m-d');
        } catch (\Exception $e) {
            throw new \Exception("Erro ao processar data: " . $e->getMessage());
        }
    }

    protected function processDecimal($valor, array $config)
    {
        if (empty($valor) && ($config['nullable'] ?? false)) {
            return null;
        }

        if (empty($valor) && isset($config['default'])) {
            return $config['default'];
        }

        // Remove caracteres não numéricos exceto vírgula e ponto
        $valor = preg_replace('/[^\d,.-]/', '', $valor);

        // Converte vírgula para ponto (formato brasileiro)
        $valor = str_replace(',', '.', $valor);

        $numeroDecimal = (float) $valor;

        // Validação de valor mínimo
        if (isset($config['min_value']) && $numeroDecimal < $config['min_value']) {
            throw new \Exception("Valor deve ser maior ou igual a {$config['min_value']}");
        }

        // Validação de valor máximo
        if (isset($config['max_value']) && $numeroDecimal > $config['max_value']) {
            throw new \Exception("Valor deve ser menor ou igual a {$config['max_value']}");
        }

        return $numeroDecimal;
    }

    protected function processString($valor, array $config)
    {
        if (empty($valor) && ($config['nullable'] ?? false)) {
            return null;
        }

        if (empty($valor) && isset($config['default'])) {
            return $config['default'];
        }

        $string = trim($valor);

        // Validação de comprimento máximo
        if (isset($config['max_length']) && strlen($string) > $config['max_length']) {
            throw new \Exception("Texto muito longo. Máximo {$config['max_length']} caracteres");
        }

        return $string;
    }

    /**
     * Converte número serial do Excel para data
     */
    private function convertExcelSerialToDate($serialNumber)
    {
        // Excel considera 1 de janeiro de 1900 como dia 1
        // Mas há um bug histórico: Excel considera 1900 como ano bissexto (não é)
        // Por isso subtraímos 2 dias ao invés de 1

        if ($serialNumber < 1) {
            throw new \Exception("Número serial inválido: {$serialNumber}");
        }

        // Ajuste para o bug do Excel com 1900
        if ($serialNumber > 59) {
            $serialNumber -= 1; // Compensa o dia 29/02/1900 que não existe
        }

        // Data base: 30 de dezembro de 1899 (para compensar o dia 1 = 1/1/1900)
        $baseDate = new \DateTime('1899-12-31');
        $baseDate->add(new \DateInterval('P' . intval($serialNumber) . 'D'));

        return $baseDate->format('Y-m-d');
    }
}
