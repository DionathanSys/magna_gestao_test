<?php

namespace App\Domain\Automation;

final readonly class AutomationReportDefinition
{
    public function __construct(
        public string $key,
        public string $collector,
        public string $collectorVersion,
        public string $schemaVersion,
        public ?string $defaultUnidadeNegocio,
        public int $resultPageLimit,
        public array $parameterRules,
        public array $fields,
    ) {}

    public static function fromConfig(string $key, array $config): self
    {
        return new self(
            key: $key,
            collector: (string) ($config['collector'] ?? $key),
            collectorVersion: (string) ($config['collector_version'] ?? '1.0.0'),
            schemaVersion: (string) ($config['schema_version'] ?? '1.0'),
            defaultUnidadeNegocio: filled($config['default_unidade_negocio'] ?? null)
                ? (string) $config['default_unidade_negocio']
                : null,
            resultPageLimit: max(1, (int) ($config['result_page_limit'] ?? 500)),
            parameterRules: (array) ($config['parameter_rules'] ?? []),
            fields: array_values((array) ($config['fields'] ?? [])),
        );
    }
}
