<?php

namespace jcnghm\ApiScout\DataTypes;

class TypeDetector
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'strict_types' => false,
        ], $config);
    }

    public function detectType($value): DataType
    {
        if (is_null($value)) {
            return DataType::NULL;
        }

        if (is_bool($value)) {
            return DataType::BOOLEAN;
        }

        if (is_int($value)) {
            return DataType::INTEGER;
        }

        if (is_float($value)) {
            return DataType::FLOAT;
        }

        if (is_string($value)) {
            return $this->detectStringType($value);
        }

        if (is_array($value)) {
            return $this->detectArrayType($value);
        }

        return DataType::UNKNOWN;
    }

    protected function detectStringType(string $value): DataType
    {
        if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return DataType::EMAIL;
        }

        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return DataType::URL;
        }

        if ($this->isUuid($value)) {
            return DataType::UUID;
        }

        if ($this->isDate($value)) {
            return DataType::DATE;
        }

        if ($this->isDateTime($value)) {
            return DataType::DATETIME;
        }

        if (is_numeric($value)) {
            return DataType::NUMERIC_STRING;
        }

        if ($this->isJsonString($value)) {
            return DataType::JSON_STRING;
        }

        if ($this->isBase64($value)) {
            return DataType::BASE64;
        }

        return DataType::STRING;
    }

    protected function detectArrayType(array $value): DataType
    {
        if (empty($value)) {
            return DataType::ARRAY;
        }

        if (array_is_list($value)) {
            return DataType::ARRAY;
        }

        return DataType::OBJECT;
    }

    protected function isUuid(string $value): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value) === 1;
    }

    protected function isDateTime(string $value): bool
    {
        if ($this->isDate($value)) {
            return false;
        }

        if (is_numeric($value)) {
            return false;
        }
        
        try {
            $date = new \DateTime($value);
            $formatted = $date->format('Y-m-d H:i:s');
            $date_only = $date->format('Y-m-d');
            
            return $formatted !== $date_only . ' 00:00:00';
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function isDate(string $value): bool
    {
        // Check for YYYY-MM-DD format exactly
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
            $date = \DateTime::createFromFormat('Y-m-d', $value);
            return $date !== false && $date->format('Y-m-d') === $value;
        }
        return false;
    }

    protected function isJsonString(string $value): bool
    {
        if (strlen($value) < 2) {
            return false;
        }

        json_decode($value);
        return json_last_error() === JSON_ERROR_NONE;
    }

    protected function isBase64(string $value): bool
    {
        if (strlen($value) < 4) {
            return false;
        }

        return base64_encode(base64_decode($value, true)) === $value;
    }

    public function getHumanType(DataType $type): string
    {
        return $type->getHumanReadable();
    }
}