<?php

namespace App\Contracts;

interface ExcelImportInterface
{
    public function validate(array $row, int $rowNumber): array;
    public function transform(array $row): array;
    public function process(array $transformedData): mixed;
    public function getRequiredColumns(): array;
    public function getOptionalColumns(): array;
}
