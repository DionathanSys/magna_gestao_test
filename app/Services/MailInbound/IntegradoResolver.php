<?php

namespace App\Services\MailInbound;

use App\Models\Integrado;
use App\Services\MailInbound\Support\DocumentIdentity;

class IntegradoResolver
{
    /**
     * @param  array<string, mixed>  $parsedDocument
     */
    public function resolve(array $parsedDocument): ?Integrado
    {
        $cnpj = DocumentIdentity::normalizeDigits($parsedDocument['destinatario_cnpj'] ?? null);

        if (! $cnpj) {
            return null;
        }

        return Integrado::query()->where('cnpj', $cnpj)->first();
    }
}
