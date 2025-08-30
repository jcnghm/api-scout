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
        $sampleData = $this->result->getSampleData();
        
        $this->assertIsArray($sampleData);
        $this->assertCount(3, $sampleData);
        $this->assertArrayHasKey('id', $sampleData[0]);
        $this->assertArrayHasKey('name', $sampleData[0]);
        $this->assertArrayHasKey('email', $sampleData[0]);
        $this->assertArrayHasKey('age', $sampleData[0]);
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
        $fieldNames = $this->result->getFieldNames();
        
        $this->assertIsArray($fieldNames);
        $this->assertCount(4, $fieldNames);
        $this->assertContains('id', $fieldNames);
        $this->assertContains('name', $fieldNames);
        $this->assertContains('email', $fieldNames);
        $this->assertContains('age', $fieldNames);
    }

    public function test_get_fields_by_type()
    {
        $integerFields = $this->result->getFieldsByType('integer');
        
        $this->assertIsArray($integerFields);
        $this->assertCount(2, $integerFields);
        $this->assertArrayHasKey('id', $integerFields);
        $this->assertArrayHasKey('age', $integerFields);
        
        $stringFields = $this->result->getFieldsByType('string');
        $this->assertCount(1, $stringFields);
        $this->assertArrayHasKey('name', $stringFields);
        
        $emailFields = $this->result->getFieldsByType('email');
        $this->assertCount(1, $emailFields);
        $this->assertArrayHasKey('email', $emailFields);
    }

    public function test_get_nullable_fields()
    {
        $nullableFields = $this->result->getNullableFields();
        
        $this->assertIsArray($nullableFields);
        $this->assertCount(1, $nullableFields);
        $this->assertArrayHasKey('age', $nullableFields);
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
        $objectAnalysis = [
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
        
        $objectResult = new ApiScoutResult('user', $objectAnalysis);
        
        $this->assertFalse($objectResult->isArray());
        $this->assertTrue($objectResult->isObject());
        $this->assertEquals(1, $objectResult->getTotalRecords());
        
        $summary = $objectResult->getSummary();
        $this->assertEquals('object', $summary['type']);
    }

    public function test_empty_analysis()
    {
        $emptyAnalysis = [
            'is_array' => false,
            'is_object' => false,
            'total_records' => 0,
            'fields' => [],
            'sample_data' => null,
            'analyzed_at' => '2023-12-25T10:30:00Z',
        ];
        
        $emptyResult = new ApiScoutResult('empty', $emptyAnalysis);
        
        $this->assertFalse($emptyResult->isArray());
        $this->assertFalse($emptyResult->isObject());
        $this->assertEquals(0, $emptyResult->getTotalRecords());
        $this->assertEmpty($emptyResult->getFields());
        $this->assertNull($emptyResult->getSampleData());
        
        $summary = $emptyResult->getSummary();
        $this->assertEquals('unknown', $summary['type']);
        $this->assertEquals(0, $summary['field_count']);
    }

    public function test_missing_analysis_fields()
    {
        $incompleteAnalysis = [
            'is_array' => true,
            'is_object' => false,
            // Missing other fields
        ];
        
        $incompleteResult = new ApiScoutResult('incomplete', $incompleteAnalysis);
        
        $this->assertEquals(0, $incompleteResult->getTotalRecords());
        $this->assertEmpty($incompleteResult->getFields());
        $this->assertNull($incompleteResult->getSampleData());
        
        $summary = $incompleteResult->getSummary();
        $this->assertEquals('array', $summary['type']);
        $this->assertEquals(0, $summary['field_count']);
        $this->assertNull($summary['analyzed_at']);
    }
}
