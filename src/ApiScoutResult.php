<?php

namespace jcnghm\ApiScout;

use jcnghm\ApiScout\Services\ComponentGenerator;

class ApiScoutResult
{

    public function __construct(
        protected string $endpointKey, protected array $analysis
    ){}

    public function getEndpointKey(): string
    {
        return $this->endpointKey;
    }

    public function getAnalysis(): array
    {
        return $this->analysis;
    }

    public function getFields(): array
    {
        return $this->analysis['fields'] ?? [];
    }

    public function getSampleData(): mixed
    {
        return $this->analysis['sample_data'] ?? null;
    }

    public function isArray(): bool
    {
        return $this->analysis['is_array'] ?? false;
    }

    public function isObject(): bool
    {
        return $this->analysis['is_object'] ?? false;
    }

    public function getTotalRecords(): int
    {
        return $this->analysis['total_records'] ?? 0;
    }

    public function generateComponents(array $options = []): bool
    {
        $generator = app(ComponentGenerator::class);
        return $generator->generate($this, $options);
    }

    public function getSummary(): array
    {
        return [
            'endpoint' => $this->endpointKey,
            'type' => $this->isArray() ? 'array' : ($this->isObject() ? 'object' : 'unknown'),
            'total_records' => $this->getTotalRecords(),
            'field_count' => count($this->getFields()),
            'analyzed_at' => $this->analysis['analyzed_at'] ?? null,
        ];
    }

    public function getFieldNames(): array
    {
        return array_keys($this->getFields());
    }

    public function getFieldsByType(string $type): array
    {
        return array_filter($this->getFields(), function ($field) use ($type) {
            return $field['type']->value === $type;
        });
    }

    public function getNullableFields(): array
    {
        return array_filter($this->getFields(), function ($field) {
            return $field['nullable'] ?? false;
        });
    }

    public function toArray(): array
    {
        return [
            'endpoint_key' => $this->endpointKey,
            'analysis' => $this->analysis,
            'summary' => $this->getSummary(),
        ];
    }

    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }
}