<?php

namespace App\Rules;

use App\Services\Veiculo\VeiculoService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class VeiculoExistsRule implements ValidationRule
{
    protected VeiculoService $veiculoService;

    public function __construct()
    {
        $this->veiculoService = new VeiculoService;
    }

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->veiculoService->getVeiculoIdByPlaca($value)) {
            $fail('O veículo com a placa :input não foi encontrado.');
        }
    }
}

// Não utilizado devido à utilização da regra 'exists' diretamente no validador do Laravel.
