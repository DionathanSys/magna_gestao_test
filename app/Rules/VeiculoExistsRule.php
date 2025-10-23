<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class VeiculoExistsRule implements ValidationRule
{   
    protected \App\Services\Veiculo\VeiculoService $veiculoService;

    public function __construct()
    {
        $this->veiculoService = new \App\Services\Veiculo\VeiculoService();
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->veiculoService->getVeiculoIdByPlaca($value)) {
            $fail("O veículo com a placa :input não foi encontrado.");
        }
    }
}


//Não utilizado devido à utilização da regra 'exists' diretamente no validador do Laravel.