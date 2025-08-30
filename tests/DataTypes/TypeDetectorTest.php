<?php

namespace jcnghm\ApiScout\Tests\DataTypes;

use jcnghm\ApiScout\Tests\TestCase;
use jcnghm\ApiScout\DataTypes\TypeDetector;
use jcnghm\ApiScout\DataTypes\DataType;

class TypeDetectorTest extends TestCase
{
    protected TypeDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new TypeDetector();
    }

    public function test_detects_null_type()
    {
        $this->assertEquals(DataType::NULL, $this->detector->detectType(null));
    }

    public function test_detects_boolean_type()
    {
        $this->assertEquals(DataType::BOOLEAN, $this->detector->detectType(true));
        $this->assertEquals(DataType::BOOLEAN, $this->detector->detectType(false));
    }

    public function test_detects_integer_type()
    {
        $this->assertEquals(DataType::INTEGER, $this->detector->detectType(42));
        $this->assertEquals(DataType::INTEGER, $this->detector->detectType(0));
        $this->assertEquals(DataType::INTEGER, $this->detector->detectType(-42));
    }

    public function test_detects_float_type()
    {
        $this->assertEquals(DataType::FLOAT, $this->detector->detectType(3.14));
        $this->assertEquals(DataType::FLOAT, $this->detector->detectType(0.0));
        $this->assertEquals(DataType::FLOAT, $this->detector->detectType(-3.14));
    }

    public function test_detects_string_types()
    {
        $this->assertEquals(DataType::STRING, $this->detector->detectType('hello world'));
        $this->assertEquals(DataType::EMAIL, $this->detector->detectType('test@example.com'));
        $this->assertEquals(DataType::URL, $this->detector->detectType('https://example.com'));
        $this->assertEquals(DataType::UUID, $this->detector->detectType('550e8400-e29b-41d4-a716-446655440000'));
        $this->assertEquals(DataType::DATETIME, $this->detector->detectType('2023-12-25 10:30:00'));
        $this->assertEquals(DataType::DATE, $this->detector->detectType('2023-12-25'));
        $this->assertEquals(DataType::NUMERIC_STRING, $this->detector->detectType('123'));
        $this->assertEquals(DataType::JSON_STRING, $this->detector->detectType('{"key": "value"}'));
        $this->assertEquals(DataType::BASE64, $this->detector->detectType('SGVsbG8gV29ybGQ='));
    }

    public function test_detects_array_types()
    {
        $this->assertEquals(DataType::ARRAY, $this->detector->detectType([1, 2, 3]));
        $this->assertEquals(DataType::ARRAY, $this->detector->detectType([]));
        $this->assertEquals(DataType::OBJECT, $this->detector->detectType(['key' => 'value']));
    }

    public function test_detects_unknown_type()
    {
        $resource = fopen('php://memory', 'r');
        $this->assertEquals(DataType::UNKNOWN, $this->detector->detectType($resource));
        fclose($resource);
    }

    public function test_email_detection()
    {
        $validEmails = [
            'test@example.com',
            'user.name@domain.co.uk',
            'user+tag@example.org',
            '123@example.com',
        ];

        $invalidEmails = [
            'not-an-email',
            '@example.com',
            'test@',
            'test.example.com',
        ];

        foreach ($validEmails as $email) {
            $this->assertEquals(DataType::EMAIL, $this->detector->detectType($email), "Failed to detect email: {$email}");
        }

        foreach ($invalidEmails as $email) {
            $this->assertEquals(DataType::STRING, $this->detector->detectType($email), "Incorrectly detected as email: {$email}");
        }
    }

    public function test_url_detection()
    {
        $validUrls = [
            'https://example.com',
            'http://example.com/path',
            'https://sub.example.com:8080/path?param=value',
            'ftp://example.com',
        ];

        $invalidUrls = [
            'not-a-url',
            'example.com',
            'http://',
            'https://',
        ];

        foreach ($validUrls as $url) {
            $this->assertEquals(DataType::URL, $this->detector->detectType($url), "Failed to detect URL: {$url}");
        }

        foreach ($invalidUrls as $url) {
            $this->assertEquals(DataType::STRING, $this->detector->detectType($url), "Incorrectly detected as URL: {$url}");
        }
    }

    public function test_uuid_detection()
    {
        $validUuids = [
            '550e8400-e29b-41d4-a716-446655440000',
            '550e8400-e29b-41d4-a716-446655440000',
            '550E8400-E29B-41D4-A716-446655440000', // Case insensitive
        ];

        $invalidUuids = [
            'not-a-uuid',
            '550e8400-e29b-41d4-a716-44665544000', // Too short
            '550e8400-e29b-41d4-a716-4466554400000', // Too long
            '550e8400-e29b-41d4-a716-44665544000g', // Invalid character
        ];

        foreach ($validUuids as $uuid) {
            $this->assertEquals(DataType::UUID, $this->detector->detectType($uuid), "Failed to detect UUID: {$uuid}");
        }

        foreach ($invalidUuids as $uuid) {
            $this->assertEquals(DataType::STRING, $this->detector->detectType($uuid), "Incorrectly detected as UUID: {$uuid}");
        }
    }

    public function test_datetime_detection()
    {
        $validDateTimes = [
            '2023-12-25 10:30:00',
            '2023-12-25T10:30:00',
            '2023-12-25T10:30:00Z',
            '2023-12-25T10:30:00+00:00',
        ];

        $invalidDateTimes = [
            'not-a-datetime',
            '2023-13-25 10:30:00', // Invalid month
            '2023-12-25 25:30:00', // Invalid hour
            '1970-01-01 00:00:00', // Unix epoch (should be filtered out)
        ];

        foreach ($validDateTimes as $datetime) {
            $this->assertEquals(DataType::DATETIME, $this->detector->detectType($datetime), "Failed to detect datetime: {$datetime}");
        }

        foreach ($invalidDateTimes as $datetime) {
            $this->assertEquals(DataType::STRING, $this->detector->detectType($datetime), "Incorrectly detected as datetime: {$datetime}");
        }
    }

    public function test_date_detection()
    {
        $validDates = [
            '2023-12-25',
            '2024-01-01',
            '2023-02-28',
            '2024-02-29', // Leap year
        ];

        $invalidDates = [
            'not-a-date',
            '2023-13-25', // Invalid month
            '2023-12-32', // Invalid day
        ];

        $validDateTimes = [
            '2023-12-25 10:30:00', // Includes time
            '2023-12-25T10:30:00Z', // ISO format
            '2023-12-25 10:30:00.123', // With milliseconds
        ];

        foreach ($validDates as $date) {
            $this->assertEquals(DataType::DATE, $this->detector->detectType($date), "Failed to detect date: {$date}");
        }

        foreach ($invalidDates as $date) {
            $this->assertEquals(DataType::STRING, $this->detector->detectType($date), "Incorrectly detected as date: {$date}");
        }

        foreach ($validDateTimes as $datetime) {
            $this->assertEquals(DataType::DATETIME, $this->detector->detectType($datetime), "Failed to detect datetime: {$datetime}");
        }
    }

    public function test_numeric_string_detection()
    {
        $numericStrings = [
            '123',
            '0',
            '-123',
            '123.45',
            '0.0',
        ];

        foreach ($numericStrings as $numeric) {
            $this->assertEquals(DataType::NUMERIC_STRING, $this->detector->detectType($numeric), "Failed to detect numeric string: {$numeric}");
        }
    }

    public function test_json_string_detection()
    {
        $validJsonStrings = [
            '{"key": "value"}',
            '{"number": 123, "boolean": true, "null": null}',
            '[1, 2, 3]',
            '[]',
            '{}',
        ];

        $invalidJsonStrings = [
            'not-json',
            '{"key": "value"', // Missing closing brace
            '[1, 2, 3', // Missing closing bracket
            '{"key": value}', // Missing quotes
        ];

        foreach ($validJsonStrings as $json) {
            $this->assertEquals(DataType::JSON_STRING, $this->detector->detectType($json), "Failed to detect JSON string: {$json}");
        }

        foreach ($invalidJsonStrings as $json) {
            $this->assertEquals(DataType::STRING, $this->detector->detectType($json), "Incorrectly detected as JSON string: {$json}");
        }
    }

    public function test_base64_detection()
    {
        $validBase64 = [
            'SGVsbG8gV29ybGQ=', // "Hello World"
            'dGVzdA==', // "test"
            'YWJjZGVmZ2hpamtsbW5vcHFyc3R1dnd4eXo=', // "abcdefghijklmnopqrstuvwxyz"
        ];

        $invalidBase64 = [
            'not-base64',
            'SGVsbG8gV29ybGQ', // Missing padding
            'SGVsbG8gV29ybGQ==', // Extra padding
            'SGVsbG8gV29ybGQ!', // Invalid character
        ];

        foreach ($validBase64 as $base64) {
            $this->assertEquals(DataType::BASE64, $this->detector->detectType($base64), "Failed to detect base64: {$base64}");
        }

        foreach ($invalidBase64 as $base64) {
            $this->assertEquals(DataType::STRING, $this->detector->detectType($base64), "Incorrectly detected as base64: {$base64}");
        }
    }

    public function test_get_human_type_returns_correct_labels()
    {
        $this->assertEquals('Number (Integer)', $this->detector->getHumanType(DataType::INTEGER));
        $this->assertEquals('Number (Decimal)', $this->detector->getHumanType(DataType::FLOAT));
        $this->assertEquals('True/False', $this->detector->getHumanType(DataType::BOOLEAN));
        $this->assertEquals('Email Address', $this->detector->getHumanType(DataType::EMAIL));
        $this->assertEquals('Website URL', $this->detector->getHumanType(DataType::URL));
        $this->assertEquals('Unique ID', $this->detector->getHumanType(DataType::UUID));
        $this->assertEquals('Date & Time', $this->detector->getHumanType(DataType::DATETIME));
        $this->assertEquals('Date', $this->detector->getHumanType(DataType::DATE));
        $this->assertEquals('Number (as Text)', $this->detector->getHumanType(DataType::NUMERIC_STRING));
        $this->assertEquals('JSON Data', $this->detector->getHumanType(DataType::JSON_STRING));
        $this->assertEquals('Encoded Data', $this->detector->getHumanType(DataType::BASE64));
        $this->assertEquals('List', $this->detector->getHumanType(DataType::ARRAY));
        $this->assertEquals('Object', $this->detector->getHumanType(DataType::OBJECT));
        $this->assertEquals('Empty', $this->detector->getHumanType(DataType::NULL));
    }
}
