<?php

namespace jcnghm\ApiScout\DataTypes;

enum DataType: string
{
    case NULL = 'null';
    case BOOLEAN = 'boolean';
    case INTEGER = 'integer';
    case FLOAT = 'float';
    case STRING = 'string';
    case EMAIL = 'email';
    case URL = 'url';
    case UUID = 'uuid';
    case DATETIME = 'datetime';
    case DATE = 'date';
    case NUMERIC_STRING = 'numeric_string';
    case JSON_STRING = 'json_string';
    case BASE64 = 'base64';
    case ARRAY = 'array';
    case OBJECT = 'object';
    case UNKNOWN = 'unknown';

    public function getHumanReadable(): string
    {
        return match ($this) {
            self::INTEGER => 'Number (Integer)',
            self::FLOAT => 'Number (Decimal)',
            self::BOOLEAN => 'True/False',
            self::EMAIL => 'Email Address',
            self::URL => 'Website URL',
            self::UUID => 'Unique ID',
            self::DATETIME => 'Date & Time',
            self::DATE => 'Date',
            self::NUMERIC_STRING => 'Number (as Text)',
            self::JSON_STRING => 'JSON Data',
            self::BASE64 => 'Encoded Data',
            self::ARRAY => 'List',
            self::OBJECT => 'Object',
            self::NULL => 'Empty',
            default => ucfirst($this->value)
        };
    }
}
