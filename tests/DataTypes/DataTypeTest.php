<?php

namespace jcnghm\ApiScout\Tests\DataTypes;

use jcnghm\ApiScout\Tests\TestCase;
use jcnghm\ApiScout\DataTypes\DataType;

class DataTypeTest extends TestCase
{
    public function test_all_data_types_are_defined()
    {
        $expectedTypes = [
            'null', 'boolean', 'integer', 'float', 'string', 'email', 'url', 
            'uuid', 'datetime', 'date', 'numeric_string', 'json_string', 
            'base64', 'array', 'object', 'unknown'
        ];

        foreach ($expectedTypes as $type) {
            $this->assertTrue(DataType::tryFrom($type) !== null, "DataType {$type} should be defined");
        }
    }

    public function test_enum_values_match_expected_strings()
    {
        $this->assertEquals('null', DataType::NULL->value);
        $this->assertEquals('boolean', DataType::BOOLEAN->value);
        $this->assertEquals('integer', DataType::INTEGER->value);
        $this->assertEquals('float', DataType::FLOAT->value);
        $this->assertEquals('string', DataType::STRING->value);
        $this->assertEquals('email', DataType::EMAIL->value);
        $this->assertEquals('url', DataType::URL->value);
        $this->assertEquals('uuid', DataType::UUID->value);
        $this->assertEquals('datetime', DataType::DATETIME->value);
        $this->assertEquals('date', DataType::DATE->value);
        $this->assertEquals('numeric_string', DataType::NUMERIC_STRING->value);
        $this->assertEquals('json_string', DataType::JSON_STRING->value);
        $this->assertEquals('base64', DataType::BASE64->value);
        $this->assertEquals('array', DataType::ARRAY->value);
        $this->assertEquals('object', DataType::OBJECT->value);
        $this->assertEquals('unknown', DataType::UNKNOWN->value);
    }

    public function test_get_human_readable_returns_correct_labels()
    {
        $this->assertEquals('Number (Integer)', DataType::INTEGER->getHumanReadable());
        $this->assertEquals('Number (Decimal)', DataType::FLOAT->getHumanReadable());
        $this->assertEquals('True/False', DataType::BOOLEAN->getHumanReadable());
        $this->assertEquals('Email Address', DataType::EMAIL->getHumanReadable());
        $this->assertEquals('Website URL', DataType::URL->getHumanReadable());
        $this->assertEquals('Unique ID', DataType::UUID->getHumanReadable());
        $this->assertEquals('Date & Time', DataType::DATETIME->getHumanReadable());
        $this->assertEquals('Date', DataType::DATE->getHumanReadable());
        $this->assertEquals('Number (as Text)', DataType::NUMERIC_STRING->getHumanReadable());
        $this->assertEquals('JSON Data', DataType::JSON_STRING->getHumanReadable());
        $this->assertEquals('Encoded Data', DataType::BASE64->getHumanReadable());
        $this->assertEquals('List', DataType::ARRAY->getHumanReadable());
        $this->assertEquals('Object', DataType::OBJECT->getHumanReadable());
        $this->assertEquals('Empty', DataType::NULL->getHumanReadable());
    }

    public function test_get_human_readable_falls_back_to_ucfirst_for_unknown_types()
    {
        $this->assertEquals('String', DataType::STRING->getHumanReadable());
        $this->assertEquals('Unknown', DataType::UNKNOWN->getHumanReadable());
    }

    public function test_enum_can_be_created_from_string()
    {
        $this->assertEquals(DataType::INTEGER, DataType::from('integer'));
        $this->assertEquals(DataType::EMAIL, DataType::from('email'));
        $this->assertEquals(DataType::ARRAY, DataType::from('array'));
    }

    public function test_enum_try_from_returns_null_for_invalid_string()
    {
        $this->assertNull(DataType::tryFrom('invalid_type'));
        $this->assertNull(DataType::tryFrom(''));
    }

    public function test_enum_cases_can_be_iterated()
    {
        $cases = DataType::cases();
        $this->assertIsArray($cases);
        $this->assertGreaterThan(0, count($cases));
        
        foreach ($cases as $case) {
            $this->assertInstanceOf(DataType::class, $case);
            $this->assertIsString($case->value);
        }
    }
}
