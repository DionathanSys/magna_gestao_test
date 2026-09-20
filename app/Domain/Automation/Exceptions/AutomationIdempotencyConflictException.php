<?php

namespace App\Domain\Automation\Exceptions;

use RuntimeException;

class AutomationIdempotencyConflictException extends RuntimeException
{
    public function __construct(string $idempotencyKey)
    {
        parent::__construct("A chave de idempotencia ja foi utilizada com outro conteudo: {$idempotencyKey}");
    }
}
