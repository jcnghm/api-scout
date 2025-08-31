<?php

namespace jcnghm\ApiScout\Tests;

use jcnghm\ApiScout\Tests\TestCase;
use jcnghm\ApiScout\ApiScoutResult;

class ApiScoutResultTest extends TestCase
{
    protected ApiScoutResult $result;
    protected array $analysis;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->analysis = [
            'is_array' => true,
            'is_object' => false,
            'total_records' => 3,
            'fields' => [
                'id' => [
                    'type' => \jcnghm\ApiScout\DataTypes\DataType::INTEGER,
                    'nullable' => false,
                    'example' => 1,
                ],
                'name' => [
                    'type' => \jcnghm\ApiScout\DataTypes\DataType::STRING,
                    'nullable' => false,
                    'example' => 'John Doe',
                ],
                'email' => [
                    'type' => \jcnghm\ApiScout\DataTypes\DataType::EMAIL,
                    'nullable' => false,
                    'example' => 'john@example.com',
                ],
                'age' => [
                    'type' => \jcnghm\ApiScout\DataTypes\DataType::INTEGER,
                    'nullable' => true,
                    'example' => 30,
                ],
            ],
            'sample_data' => [
                ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com', 'age' => 30],
                ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com', 'age' => null],
                ['id' => 3, 'name' => 'Bob Johnson', 'email' => 'bob@example.com', 'age' => 25],
            ],
            'analyzed_at' => '2023-12-25T10:30:00Z',
        ];
        
        $this->result = new ApiScoutResult('users', $this->analysis);
    }

    public function test_get_endpoint_key()
    {
        $this->assertEquals('users', $this->result->getEndpointKey());
    }

    public function test_get_analysis()
    {
        $analysis = $this->result->getAnalysis();
        
        $this->assertIsArray($analysis);
        $this->assertEquals($this->analysis, $analysis);
    }

    public function test_get_fields()
    {
        $fields = $this->result->getFields();
        
        $this->assertIsArray($fields);
        $this->assertCount(4, $fields);
        $this->assertArrayHasKey('id', $fields);
        $this->assertArrayHasKey('name', $fields);
        $this->assertArrayHasKey('email', $fields);
        $this->assertArrayHasKey('age', $fields);
    }

    public function test_get_sample_data()
    {
        $sample_data = $this->result->getSampleData();
        
        $this->assertIsArray($sample_data);
        $this->assertCount(3, $sample_data);
        $this->assertArrayHasKey('id', $sample_data[0]);
        $this->assertArrayHasKey('name', $sample_data[0]);
        $this->assertArrayHasKey('email', $sample_data[0]);
        $this->assertArrayHasKey('age', $sample_data[0]);
    }

    public function test_is_array()
    {
        $this->assertTrue($this->result->isArray());
    }

    public function test_is_object()
    {
        $this->assertFalse($this->result->isObject());
    }

    public function test_get_total_records()
    {
        $this->assertEquals(3, $this->result->getTotalRecords());
    }

    public function test_get_summary()
    {
        $summary = $this->result->getSummary();
        
        $this->assertIsArray($summary);
        $this->assertEquals('users', $summary['endpoint']);
        $this->assertEquals('array', $summary['type']);
        $this->assertEquals(3, $summary['total_records']);
        $this->assertEquals(4, $summary['field_count']);
        $this->assertEquals('2023-12-25T10:30:00Z', $summary['analyzed_at']);
    }

    public function test_get_field_names()
    {
        $field_names = $this->result->getFieldNames();
        
        $this->assertIsArray($field_names);
        $this->assertCount(4, $field_names);
        $this->assertContains('id', $field_names);
        $this->assertContains('name', $field_names);
        $this->assertContains('email', $field_names);
        $this->assertContains('age', $field_names);
    }

    public function test_get_fields_by_type()
    {
        $integer_fields = $this->result->getFieldsByType('integer');
        
        $this->assertIsArray($integer_fields);
        $this->assertCount(2, $integer_fields);
        $this->assertArrayHasKey('id', $integer_fields);
        $this->assertArrayHasKey('age', $integer_fields);
        
        $string_fields = $this->result->getFieldsByType('string');
        $this->assertCount(1, $string_fields);
        $this->assertArrayHasKey('name', $string_fields);
        
        $email_fields = $this->result->getFieldsByType('email');
        $this->assertCount(1, $email_fields);
        $this->assertArrayHasKey('email', $email_fields);
    }

    public function test_get_nullable_fields()
    {
        $nullable_fields = $this->result->getNullableFields();
        
        $this->assertIsArray($nullable_fields);
        $this->assertCount(1, $nullable_fields);
        $this->assertArrayHasKey('age', $nullable_fields);
    }

    public function test_to_array()
    {
        $array = $this->result->toArray();
        
        $this->assertIsArray($array);
        $this->assertArrayHasKey('endpoint_key', $array);
        $this->assertArrayHasKey('analysis', $array);
        $this->assertArrayHasKey('summary', $array);
        
        $this->assertEquals('users', $array['endpoint_key']);
        $this->assertEquals($this->analysis, $array['analysis']);
        $this->assertEquals($this->result->getSummary(), $array['summary']);
    }

    public function test_to_json()
    {
        $json = $this->result->toJson();
        
        $this->assertIsString($json);
        $this->assertJson($json);
        
        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertEquals('users', $decoded['endpoint_key']);
    }

    public function test_to_json_with_options()
    {
        $json = $this->result->toJson(JSON_PRETTY_PRINT);
        
        $this->assertIsString($json);
        $this->assertJson($json);
        $this->assertStringContainsString("\n", $json); // Pretty printed
    }

    public function test_object_response_type()
    {
        $object_analysis = [
            'is_array' => false,
            'is_object' => true,
            'total_records' => 1,
            'fields' => [
                'id' => [
                    'type' => \jcnghm\ApiScout\DataTypes\DataType::INTEGER,
                    'nullable' => false,
                    'example' => 1,
                ],
                'name' => [
                    'type' => \jcnghm\ApiScout\DataTypes\DataType::STRING,
                    'nullable' => false,
                    'example' => 'John Doe',
                ],
            ],
            'sample_data' => [
                'id' => 1,
                'name' => 'John Doe',
            ],
            'analyzed_at' => '2023-12-25T10:30:00Z',
        ];
        
        $object_result = new ApiScoutResult('user', $object_analysis);
        
        $this->assertFalse($object_result->isArray());
        $this->assertTrue($object_result->isObject());
        $this->assertEquals(1, $object_result->getTotalRecords());
        
        $summary = $object_result->getSummary();
        $this->assertEquals('object', $summary['type']);
    }

    public function test_empty_analysis()
    {
        $empty_analysis = [
            'is_array' => false,
            'is_object' => false,
            'total_records' => 0,
            'fields' => [],
            'sample_data' => null,
            'analyzed_at' => '2023-12-25T10:30:00Z',
        ];
        
        $empty_result = new ApiScoutResult('empty', $empty_analysis);
        
        $this->assertFalse($empty_result->isArray());
        $this->assertFalse($empty_result->isObject());
        $this->assertEquals(0, $empty_result->getTotalRecords());
        $this->assertEmpty($empty_result->getFields());
        $this->assertNull($empty_result->getSampleData());
        
        $summary = $empty_result->getSummary();
        $this->assertEquals('unknown', $summary['type']);
        $this->assertEquals(0, $summary['field_count']);
    }

    public function test_missing_analysis_fields()
    {
        $incomplete_analysis = [
            'is_array' => true,
            'is_object' => false,
            // Missing other fields
        ];
        
        $incomplete_result = new ApiScoutResult('incomplete', $incomplete_analysis);
        
        $this->assertEquals(0, $incomplete_result->getTotalRecords());
        $this->assertEmpty($incomplete_result->getFields());
        $this->assertNull($incomplete_result->getSampleData());
        
        $summary = $incomplete_result->getSummary();
        $this->assertEquals('array', $summary['type']);
        $this->assertEquals(0, $summary['field_count']);
        $this->assertNull($summary['analyzed_at']);
    }
}