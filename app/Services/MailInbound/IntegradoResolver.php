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
        $documento = DocumentIdentity::normalizeDigits($parsedDocument['destinatario_documento'] ?? null);

        if (! $documento) {
            return null;
        }

        return Integrado::query()->where('documento', $documento)->first();
    }
}
