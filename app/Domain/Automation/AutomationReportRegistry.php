<?php

namespace App\Domain\Automation;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class AutomationReportRegistry
{
    public function get(string $reportKey): AutomationReportDefinition
    {
        $config = data_get(config('automation.reports', []), $reportKey);

        if (! is_array($config)) {
            throw new InvalidArgumentException("Relatorio de automacao nao configurado: {$reportKey}");
        }

        return AutomationReportDefinition::fromConfig($reportKey, $config);
    }

    /**
     * @return list<string>
     */
    public function keys(): array
    {
        return array_keys((array) config('automation.reports', []));
    }

    /**
     * @throws ValidationException
     */
    public function validateParameters(AutomationReportDefinition $definition, array $parameters): void
    {
        if ($definition->parameterRules === []) {
            return;
        }

        Validator::make($parameters, $definition->parameterRules)->validate();
    }
}
