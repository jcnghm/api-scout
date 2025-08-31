<?php

namespace jcnghm\ApiScout\Tests;

use jcnghm\ApiScout\Tests\TestCase;
use jcnghm\ApiScout\ApiScout;
use jcnghm\ApiScout\ApiScoutResult;
use jcnghm\ApiScout\Exceptions\ApiScoutException;

class ApiScoutTest extends TestCase
{
    protected ApiScout $api_scout;

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
            'posts' => [
                'url' => 'https://jsonplaceholder.typicode.com/posts',
                'method' => 'GET',
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ],
        ]);
        
        $this->api_scout = new ApiScout();
    }

    public function test_get_endpoints()
    {
        $endpoints = $this->api_scout->getEndpoints();
        
        $this->assertIsArray($endpoints);
        $this->assertCount(2, $endpoints);
        $this->assertContains('users', $endpoints);
        $this->assertContains('posts', $endpoints);
    }

    public function test_analyze_nonexistent_endpoint()
    {
        $this->expectException(ApiScoutException::class);
        $this->expectExceptionMessage("Endpoint 'nonexistent' not found in configuration");
        
        $this->api_scout->analyze('nonexistent');
    }

    public function test_add_endpoint()
    {
        $new_endpoint = [
            'url' => 'https://jsonplaceholder.typicode.com/comments',
            'method' => 'GET',
            'headers' => [
                'Accept' => 'application/json',
            ],
        ];
        
        $this->api_scout->addEndpoint('comments', $new_endpoint);
        
        $endpoints = $this->api_scout->getEndpoints();
        $this->assertContains('comments', $endpoints);
    }

    public function test_add_endpoint_with_authentication()
    {
        $new_endpoint = [
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
        
        $this->api_scout->addEndpoint('protected', $new_endpoint);
        
        $endpoints = $this->api_scout->getEndpoints();
        $this->assertContains('protected', $endpoints);
    }

    public function test_endpoint_configuration_merging()
    {
        $endpoint = [
            'url' => 'https://api.example.com/test',
            // Missing method and headers
        ];
        
        $this->api_scout->addEndpoint('test', $endpoint);
        
        // The endpoint should be added with default values
        $endpoints = $this->api_scout->getEndpoints();
        $this->assertContains('test', $endpoints);
    }

    public function test_analyze_with_default_configuration()
    {
        // Test that endpoints work with default configuration
        $endpoints = $this->api_scout->getEndpoints();
        $this->assertIsArray($endpoints);
        $this->assertCount(2, $endpoints);
    }

    public function test_analyze_with_custom_configuration()
    {
        $this->app['config']->set('api-scout.endpoints.custom', [
            'url' => 'https://jsonplaceholder.typicode.com/users/1',
            'method' => 'GET',
            'headers' => [
                'Accept' => 'application/json',
                'Custom-Header' => 'custom-value',
            ],
        ]);
        
        // Create a new ApiScout instance to pick up the updated config
        $api_scout = new ApiScout();
        $endpoints = $api_scout->getEndpoints();
        $this->assertContains('custom', $endpoints);
    }

    public function test_analyze_all_with_empty_configuration()
    {
        $this->app['config']->set('api-scout.endpoints', []);
        
        $api_scout = new ApiScout();
        $results = $api_scout->analyzeAll();
        
        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    public function test_fluent_interface_for_add_endpoint()
    {
        $result = $this->api_scout
            ->addEndpoint('fluent', [
                'url' => 'https://jsonplaceholder.typicode.com/users/1',
                'method' => 'GET',
            ])
            ->addEndpoint('fluent2', [
                'url' => 'https://jsonplaceholder.typicode.com/users/2',
                'method' => 'GET',
            ]);
        
        $this->assertInstanceOf(ApiScout::class, $result);
        
        $endpoints = $this->api_scout->getEndpoints();
        $this->assertContains('fluent', $endpoints);
        $this->assertContains('fluent2', $endpoints);
    }

    public function test_endpoint_configuration_defaults()
    {
        $minimal_endpoint = [
            'url' => 'https://api.example.com/minimal',
        ];
        
        $this->api_scout->addEndpoint('minimal', $minimal_endpoint);
        
        $endpoints = $this->api_scout->getEndpoints();
        $this->assertContains('minimal', $endpoints);
    }

    public function test_analyze_with_post_method()
    {
        $post_endpoint = [
            'url' => 'https://jsonplaceholder.typicode.com/posts',
            'method' => 'POST',
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ];
        
        $this->api_scout->addEndpoint('post-test', $post_endpoint);
        
        $endpoints = $this->api_scout->getEndpoints();
        $this->assertContains('post-test', $endpoints);
    }

    public function test_analyze_with_custom_headers()
    {
        $custom_headers_endpoint = [
            'url' => 'https://jsonplaceholder.typicode.com/users',
            'method' => 'GET',
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => 'ApiScout/1.0',
                'X-Custom-Header' => 'custom-value',
            ],
        ];
        
        $this->api_scout->addEndpoint('custom-headers', $custom_headers_endpoint);
        
        $endpoints = $this->api_scout->getEndpoints();
        $this->assertContains('custom-headers', $endpoints);
    }
}