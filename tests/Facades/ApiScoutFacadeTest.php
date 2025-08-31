<?php

namespace jcnghm\ApiScout\Tests\Facades;

use jcnghm\ApiScout\Tests\TestCase;
use jcnghm\ApiScout\Facades\ApiScout;
use jcnghm\ApiScout\ApiScoutResult;

class ApiScoutFacadeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->app['config']->set('api-scout.endpoints', [
            'users' => [
                'url' => 'https://jsonplaceholder.typicode.com/users',
                'method' => 'GET',
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ],
        ]);
    }

    public function test_facade_analyze_method()
    {
        $result = ApiScout::analyze('users');
        
        $this->assertInstanceOf(ApiScoutResult::class, $result);
        $this->assertEquals('users', $result->getEndpointKey());
    }

    public function test_facade_analyze_all_method()
    {
        $results = ApiScout::analyzeAll();
        
        $this->assertIsArray($results);
        $this->assertArrayHasKey('users', $results);
        $this->assertInstanceOf(ApiScoutResult::class, $results['users']);
    }

    public function test_facade_get_endpoints_method()
    {
        $endpoints = ApiScout::getEndpoints();
        
        $this->assertIsArray($endpoints);
        $this->assertContains('users', $endpoints);
    }

    public function test_facade_add_endpoint_method()
    {
        $new = [
            'url' => 'https://jsonplaceholder.typicode.com/posts',
            'method' => 'GET',
            'headers' => [
                'Accept' => 'application/json',
            ],
        ];
        
        $result = ApiScout::addEndpoint('posts', $new);
        
        $this->assertInstanceOf(\jcnghm\ApiScout\ApiScout::class, $result);
        
        $endpoints = ApiScout::getEndpoints();
        $this->assertContains('posts', $endpoints);
    }

    public function test_facade_fluent_interface()
    {
        $result = ApiScout::addEndpoint('test1', [
            'url' => 'https://api.example.com/test1',
            'method' => 'GET',
        ])->addEndpoint('test2', [
            'url' => 'https://api.example.com/test2',
            'method' => 'GET',
        ]);
        
        $this->assertInstanceOf(\jcnghm\ApiScout\ApiScout::class, $result);
        
        $endpoints = ApiScout::getEndpoints();
        $this->assertContains('test1', $endpoints);
        $this->assertContains('test2', $endpoints);
    }

    public function test_facade_with_authentication()
    {
        $protected = [
            'url' => 'https://api.example.com/protected',
            'method' => 'GET',
            'headers' => [
                'Accept' => 'application/json',
            ],
            'auth' => [
                'type' => 'bearer',
                'token' => 'test-token-123',
            ],
        ];
        
        ApiScout::addEndpoint('protected', $protected);
        
        $endpoints = ApiScout::getEndpoints();
        $this->assertContains('protected', $endpoints);
    }
}
