<?php

namespace jcnghm\ApiScout\Tests\Services;

use jcnghm\ApiScout\Tests\TestCase;
use jcnghm\ApiScout\Services\EndpointAnalyzer;
use jcnghm\ApiScout\Exceptions\ApiScoutException;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;

class EndpointAnalyzerTest extends TestCase
{
    protected EndpointAnalyzer $analyzer;
    protected array $container = [];

    protected function setUp(): void
    {
        parent::setUp();
        
        $config = [
            'timeout' => 30,
            'connect_timeout' => 10,
            'type_detection' => [
                'sample_size' => 3,
                'strict_types' => false,
            ]
        ];
        
        $this->analyzer = new EndpointAnalyzer($config);
    }

    public function test_analyzes_simple_object_response()
    {
        $mockResponse = new Response(200, [], json_encode([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'active' => true,
            'created_at' => '2023-12-25 10:30:00',
        ]));

        $mock = new MockHandler([$mockResponse]);
        $handlerStack = HandlerStack::create($mock);
        
        $client = new Client(['handler' => $handlerStack]);
        
        $reflection = new \ReflectionClass($this->analyzer);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($this->analyzer, $client);

        $endpoint = [
            'url' => 'https://api.example.com/user/1',
            'method' => 'GET',
            'headers' => ['Accept' => 'application/json'],
        ];

        $result = $this->analyzer->analyze($endpoint);

        $this->assertIsArray($result);
        $this->assertFalse($result['is_array']);
        $this->assertTrue($result['is_object']);
        $this->assertEquals(1, $result['total_records']);
        $this->assertArrayHasKey('fields', $result);
        $this->assertArrayHasKey('sample_data', $result);
        $this->assertArrayHasKey('analyzed_at', $result);
    }

    public function test_analyzes_array_response()
    {
        $mockResponse = new Response(200, [], json_encode([
            [
                'id' => 1,
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ],
            [
                'id' => 2,
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
            ],
            [
                'id' => 3,
                'name' => 'Bob Johnson',
                'email' => 'bob@example.com',
            ],
        ]));

        $mock = new MockHandler([$mockResponse]);
        $handlerStack = HandlerStack::create($mock);
        
        $client = new Client(['handler' => $handlerStack]);
        
        $reflection = new \ReflectionClass($this->analyzer);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($this->analyzer, $client);

        $endpoint = [
            'url' => 'https://api.example.com/users',
            'method' => 'GET',
            'headers' => ['Accept' => 'application/json'],
        ];

        $result = $this->analyzer->analyze($endpoint);

        $this->assertIsArray($result);
        $this->assertTrue($result['is_array']);
        $this->assertFalse($result['is_object']);
        $this->assertEquals(3, $result['total_records']);
        $this->assertArrayHasKey('fields', $result);
        $this->assertArrayHasKey('sample_data', $result);
        $this->assertArrayHasKey('analyzed_at', $result);
    }

    public function test_analyzes_empty_array_response()
    {
        $mockResponse = new Response(200, [], json_encode([]));

        $mock = new MockHandler([$mockResponse]);
        $handlerStack = HandlerStack::create($mock);
        
        $client = new Client(['handler' => $handlerStack]);
        
        $reflection = new \ReflectionClass($this->analyzer);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($this->analyzer, $client);

        $endpoint = [
            'url' => 'https://api.example.com/users',
            'method' => 'GET',
            'headers' => ['Accept' => 'application/json'],
        ];

        $result = $this->analyzer->analyze($endpoint);

        $this->assertIsArray($result);
        $this->assertTrue($result['is_array']);
        $this->assertFalse($result['is_object']);
        $this->assertEquals(0, $result['total_records']);
        $this->assertArrayHasKey('fields', $result);
        $this->assertArrayHasKey('sample_data', $result);
    }

    public function test_analyzes_fields_with_different_types()
    {
        $mockResponse = new Response(200, [], json_encode([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 30,
            'height' => 175.5,
            'active' => true,
            'created_at' => '2023-12-25',
            'updated_at' => '2023-12-25 10:30:00',
            'profile' => ['bio' => 'Software Developer'],
            'tags' => ['developer', 'php'],
            'settings' => null,
        ]));

        $mock = new MockHandler([$mockResponse]);
        $handlerStack = HandlerStack::create($mock);
        
        $client = new Client(['handler' => $handlerStack]);
        
        $reflection = new \ReflectionClass($this->analyzer);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($this->analyzer, $client);

        $endpoint = [
            'url' => 'https://api.example.com/user/1',
            'method' => 'GET',
            'headers' => ['Accept' => 'application/json'],
        ];

        $result = $this->analyzer->analyze($endpoint);

        $this->assertArrayHasKey('fields', $result);
        $fields = $result['fields'];

        $this->assertArrayHasKey('id', $fields);
        $this->assertArrayHasKey('name', $fields);
        $this->assertArrayHasKey('email', $fields);
        $this->assertArrayHasKey('age', $fields);
        $this->assertArrayHasKey('height', $fields);
        $this->assertArrayHasKey('active', $fields);
        $this->assertArrayHasKey('created_at', $fields);
        $this->assertArrayHasKey('updated_at', $fields);
        $this->assertArrayHasKey('profile', $fields);
        $this->assertArrayHasKey('tags', $fields);
        $this->assertArrayHasKey('settings', $fields);

        // Check field types
        $this->assertEquals('integer', $fields['id']['type']->value);
        $this->assertEquals('string', $fields['name']['type']->value);
        $this->assertEquals('email', $fields['email']['type']->value);
        $this->assertEquals('integer', $fields['age']['type']->value);
        $this->assertEquals('float', $fields['height']['type']->value);
        $this->assertEquals('boolean', $fields['active']['type']->value);
        $this->assertEquals('date', $fields['created_at']['type']->value);
        $this->assertEquals('datetime', $fields['updated_at']['type']->value);
        $this->assertEquals('object', $fields['profile']['type']->value);
        $this->assertEquals('array', $fields['tags']['type']->value);
        $this->assertEquals('null', $fields['settings']['type']->value);
        $this->assertTrue($fields['settings']['nullable']);
    }

    // public function test_analyzes_array_with_varying_fields()
    // {
    //     $mockResponse = new Response(200, [], json_encode([
    //         [
    //             'id' => 1,
    //             'name' => 'John Doe',
    //             'email' => 'john@example.com',
    //             'age' => 30,
    //         ],
    //         [
    //             'id' => 2,
    //             'name' => 'Jane Smith',
    //             'email' => 'jane@example.com',
    //             'age' => null, // Nullable field
    //         ],
    //         [
    //             'id' => 3,
    //             'name' => 'Bob Johnson',
    //             'email' => 'bob@example.com',
    //             'age' => 25,
    //             'department' => 'Engineering', // Additional field
    //         ],
    //     ]));

    //     $mock = new MockHandler([$mockResponse]);
    //     $handlerStack = HandlerStack::create($mock);
        
    //     $client = new Client(['handler' => $handlerStack]);
        
    //     $reflection = new \ReflectionClass($this->analyzer);
    //     $clientProperty = $reflection->getProperty('client');
    //     $clientProperty->setAccessible(true);
    //     $clientProperty->setValue($this->analyzer, $client);

    //     $endpoint = [
    //         'url' => 'https://api.example.com/users',
    //         'method' => 'GET',
    //         'headers' => ['Accept' => 'application/json'],
    //     ];

    //     $result = $this->analyzer->analyze($endpoint);

    //     $this->assertArrayHasKey('fields', $result);
    //     $fields = $result['fields'];

    //     // All records should have these fields
    //     $this->assertArrayHasKey('id', $fields);
    //     $this->assertArrayHasKey('name', $fields);
    //     $this->assertArrayHasKey('email', $fields);
    //     $this->assertArrayHasKey('age', $fields);

    //     // Some records have this field
    //     $this->assertArrayHasKey('department', $fields);

    //     // Check that nullable fields are marked correctly
    //     $this->assertTrue($fields['age']['nullable']);
    //     $this->assertTrue($fields['department']['nullable']);
    // }

    public function test_handles_invalid_json_response()
    {
        $mockResponse = new Response(200, [], 'invalid json');

        $mock = new MockHandler([$mockResponse]);
        $handlerStack = HandlerStack::create($mock);
        
        $client = new Client(['handler' => $handlerStack]);
        
        $reflection = new \ReflectionClass($this->analyzer);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($this->analyzer, $client);

        $endpoint = [
            'url' => 'https://api.example.com/users',
            'method' => 'GET',
            'headers' => ['Accept' => 'application/json'],
        ];

        $this->expectException(ApiScoutException::class);
        $this->expectExceptionMessage('Invalid JSON response from endpoint');
        
        $this->analyzer->analyze($endpoint);
    }

    public function test_handles_http_error()
    {
        $mockResponse = new Response(404, [], 'Not Found');

        $mock = new MockHandler([
            new RequestException('Not Found', new Request('GET', 'test'), $mockResponse)
        ]);
        $handlerStack = HandlerStack::create($mock);
        
        $client = new Client(['handler' => $handlerStack]);
        
        $reflection = new \ReflectionClass($this->analyzer);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($this->analyzer, $client);

        $endpoint = [
            'url' => 'https://api.example.com/users',
            'method' => 'GET',
            'headers' => ['Accept' => 'application/json'],
        ];

        $this->expectException(ApiScoutException::class);
        $this->expectExceptionMessage('Failed to analyze endpoint: Not Found');
        
        $this->analyzer->analyze($endpoint);
    }

    public function test_analyzes_with_authentication()
    {
        $mockResponse = new Response(200, [], json_encode([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]));

        $mock = new MockHandler([$mockResponse]);
        $handlerStack = HandlerStack::create($mock);
        
        $client = new Client(['handler' => $handlerStack]);
        
        $reflection = new \ReflectionClass($this->analyzer);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($this->analyzer, $client);

        $endpoint = [
            'url' => 'https://api.example.com/user/1',
            'method' => 'GET',
            'headers' => ['Accept' => 'application/json'],
            'auth' => [
                'type' => 'bearer',
                'token' => 'test-token-123',
            ],
        ];

        $result = $this->analyzer->analyze($endpoint);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('fields', $result);
        $this->assertArrayHasKey('id', $result['fields']);
        $this->assertArrayHasKey('name', $result['fields']);
        $this->assertArrayHasKey('email', $result['fields']);
    }

    public function test_sample_data_limitation()
    {
        $mockResponse = new Response(200, [], json_encode([
            ['id' => 1, 'name' => 'User 1'],
            ['id' => 2, 'name' => 'User 2'],
            ['id' => 3, 'name' => 'User 3'],
            ['id' => 4, 'name' => 'User 4'],
            ['id' => 5, 'name' => 'User 5'],
        ]));

        $mock = new MockHandler([$mockResponse]);
        $handlerStack = HandlerStack::create($mock);
        
        $client = new Client(['handler' => $handlerStack]);
        
        $reflection = new \ReflectionClass($this->analyzer);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($this->analyzer, $client);

        $endpoint = [
            'url' => 'https://api.example.com/users',
            'method' => 'GET',
            'headers' => ['Accept' => 'application/json'],
        ];

        $result = $this->analyzer->analyze($endpoint);

        $this->assertEquals(5, $result['total_records']);
        $this->assertCount(3, $result['sample_data']); // Limited by sample_size
    }

    public function test_get_auth_service()
    {
        $authService = $this->analyzer->getAuthService();
        
        $this->assertInstanceOf(\jcnghm\ApiScout\Services\AuthenticationService::class, $authService);
    }
}
