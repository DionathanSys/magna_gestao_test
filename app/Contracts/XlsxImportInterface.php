<?php

namespace App\Contracts;

interface XlsxImportInterface
{
    /**
     * Retorna um array com os nomes das colunas esperadas.
     */
    public static function columns(): array;

    /**
     * Retorna um array mapeando as colunas do arquivo XLSX para os campos do modelo.
     */
    public static function columnMap(): array;

    /**
     * Valida se todas as colunas obrigatórias estão presentes.
     */
    public function validateColumns(array $firstRow): void;

    /**
     * Processa uma linha do arquivo XLSX.
     */
    public function processRow(array $row): void;

    /**
     * Mapeia os dados de uma linha para o formato esperado pelo modelo.
     */
    public function mapRowData(array $row): array;



}
