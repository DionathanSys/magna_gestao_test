<?php

namespace App\Services\DocumentoFrete\Actions;

use Illuminate\Support\Facades\Log;

class Import
{
    public function handle(array $data): void
    {
        // Implement the logic to import the document freight data.
        // This could involve parsing a file, validating the data,
        // and saving it to the database.

        // Example:
        // $documentFrete = new Models\DocumentoFrete($data);
        // $documentFrete->save();

        // Log the import action
        Log::info('Documento Frete imported successfully', ['data' => $data]);
    }
}
