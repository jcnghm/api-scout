<?php

namespace jcnghm\ApiScout\Tests\Services;

use jcnghm\ApiScout\Tests\TestCase;
use jcnghm\ApiScout\Services\AuthenticationService;
use jcnghm\ApiScout\Exceptions\ApiScoutException;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Middleware;

class AuthenticationServiceTest extends TestCase
{
    protected AuthenticationService $auth_service;
    protected array $container = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->auth_service = new AuthenticationService();
    }

    public function test_get_auth_headers_returns_empty_array_for_no_auth()
    {
        $endpoint = ['url' => 'https://example.com/api'];
        $headers = $this->auth_service->getAuthHeaders($endpoint);
        
        $this->assertEmpty($headers);
    }

    public function test_get_auth_headers_for_bearer_token()
    {
        $endpoint = [
            'url' => 'https://example.com/api',
            'auth' => [
                'type' => 'bearer',
                'token' => 'test-token-123',
            ]
        ];

        $headers = $this->auth_service->getAuthHeaders($endpoint);
        
        $this->assertEquals(['Authorization' => 'Bearer test-token-123'], $headers);
    }

    public function test_get_auth_headers_for_basic_auth()
    {
        $endpoint = [
            'url' => 'https://example.com/api',
            'auth' => [
                'type' => 'basic',
                'username' => 'testuser',
                'password' => 'testpass',
            ]
        ];

        $headers = $this->auth_service->getAuthHeaders($endpoint);
        $expected_auth = 'Basic ' . base64_encode('testuser:testpass');
        
        $this->assertEquals(['Authorization' => $expected_auth], $headers);
    }

    public function test_get_auth_headers_for_api_key()
    {
        $endpoint = [
            'url' => 'https://example.com/api',
            'auth' => [
                'type' => 'api_key',
                'key' => 'test-api-key',
                'header' => 'X-API-Key',
            ]
        ];

        $headers = $this->auth_service->getAuthHeaders($endpoint);
        
        $this->assertEquals(['X-API-Key' => 'test-api-key'], $headers);
    }

    public function test_get_auth_headers_for_api_key_with_default_header()
    {
        $endpoint = [
            'url' => 'https://example.com/api',
            'auth' => [
                'type' => 'api_key',
                'key' => 'test-api-key',
            ]
        ];

        $headers = $this->auth_service->getAuthHeaders($endpoint);
        
        $this->assertEquals(['X-API-Key' => 'test-api-key'], $headers);
    }

    public function test_token_endpoint_authentication_with_mock_client()
    {
        // Create a mock response for token endpoint
        $mock_response = new Response(200, [], json_encode([
            'access_token' => 'mock-token-123',
            'expires_in' => 3600,
            'token_type' => 'Bearer'
        ]));

        $mock = new MockHandler([$mock_response]);
        $handler_stack = HandlerStack::create($mock);
        
        // Add middleware to capture requests
        $handler_stack->push(Middleware::history($this->container));
        
        $client = new Client(['handler' => $handler_stack]);
        
        // Create auth service with mock client
        $auth_service = new AuthenticationService();
        $reflection = new \ReflectionClass($auth_service);
        $client_property = $reflection->getProperty('client');
        $client_property->setAccessible(true);
        $client_property->setValue($auth_service, $client);

        $endpoint = [
            'url' => 'https://example.com/api',
            'auth' => [
                'type' => 'token_endpoint',
                'token_endpoint' => 'https://example.com/oauth/token',
                'method' => 'POST',
                'auth_type' => 'form',
                'credentials' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => 'test-client',
                    'client_secret' => 'test-secret',
                ],
                'token_path' => 'access_token',
                'expires_in_path' => 'expires_in',
                'token_type_path' => 'token_type',
            ]
        ];

        $headers = $auth_service->getAuthHeaders($endpoint);
        
        $this->assertEquals(['Authorization' => 'Bearer mock-token-123'], $headers);
        
        // Verify the token request was made
        $this->assertCount(1, $this->container);
        $request = $this->container[0]['request'];
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('https://example.com/oauth/token', (string) $request->getUri());
    }

    public function test_token_endpoint_authentication_with_json_credentials()
    {
        $mock_response = new Response(200, [], json_encode([
            'access_token' => 'mock-token-123',
            'expires_in' => 3600,
            'token_type' => 'Bearer'
        ]));

        $mock = new MockHandler([$mock_response]);
        $handler_stack = HandlerStack::create($mock);
        $handler_stack->push(Middleware::history($this->container));
        
        $client = new Client(['handler' => $handler_stack]);
        
        $auth_service = new AuthenticationService();
        $reflection = new \ReflectionClass($auth_service);
        $client_property = $reflection->getProperty('client');
        $client_property->setAccessible(true);
        $client_property->setValue($auth_service, $client);

        $endpoint = [
            'url' => 'https://example.com/api',
            'auth' => [
                'type' => 'token_endpoint',
                'token_endpoint' => 'https://example.com/oauth/token',
                'method' => 'POST',
                'auth_type' => 'json',
                'credentials' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => 'test-client',
                    'client_secret' => 'test-secret',
                ],
            ]
        ];

        $headers = $auth_service->getAuthHeaders($endpoint);
        
        $this->assertEquals(['Authorization' => 'Bearer mock-token-123'], $headers);
        
        // Verify JSON content type was set
        $request = $this->container[0]['request'];
        $this->assertEquals('application/json', $request->getHeaderLine('Content-Type'));
    }

    public function test_token_endpoint_authentication_with_nested_response()
    {
        $mock_response = new Response(200, [], json_encode([
            'data' => [
                'access_token' => 'mock-token-123',
                'expires_in' => 3600,
                'token_type' => 'Bearer'
            ]
        ]));

        $mock = new MockHandler([$mock_response]);
        $handler_stack = HandlerStack::create($mock);
        $handler_stack->push(Middleware::history($this->container));
        
        $client = new Client(['handler' => $handler_stack]);
        
        $auth_service = new AuthenticationService();
        $reflection = new \ReflectionClass($auth_service);
        $client_property = $reflection->getProperty('client');
        $client_property->setAccessible(true);
        $client_property->setValue($auth_service, $client);

        $endpoint = [
            'url' => 'https://example.com/api',
            'auth' => [
                'type' => 'token_endpoint',
                'token_endpoint' => 'https://example.com/oauth/token',
                'method' => 'POST',
                'auth_type' => 'form',
                'credentials' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => 'test-client',
                    'client_secret' => 'test-secret',
                ],
                'token_path' => 'data.access_token',
                'expires_in_path' => 'data.expires_in',
                'token_type_path' => 'data.token_type',
            ]
        ];

        $headers = $auth_service->getAuthHeaders($endpoint);
        
        $this->assertEquals(['Authorization' => 'Bearer mock-token-123'], $headers);
    }

    public function test_token_endpoint_authentication_with_password_grant()
    {
        $mock_response = new Response(200, [], json_encode([
            'access_token' => 'mock-token-123',
            'expires_in' => 3600,
            'token_type' => 'Bearer'
        ]));

        $mock = new MockHandler([$mock_response]);
        $handler_stack = HandlerStack::create($mock);
        $handler_stack->push(Middleware::history($this->container));
        
        $client = new Client(['handler' => $handler_stack]);
        
        $auth_service = new AuthenticationService();
        $reflection = new \ReflectionClass($auth_service);
        $client_property = $reflection->getProperty('client');
        $client_property->setAccessible(true);
        $client_property->setValue($auth_service, $client);

        $endpoint = [
            'url' => 'https://example.com/api',
            'auth' => [
                'type' => 'token_endpoint',
                'token_endpoint' => 'https://example.com/oauth/token',
                'method' => 'POST',
                'auth_type' => 'form',
                'credentials' => [
                    'grant_type' => 'password',
                    'username' => 'testuser',
                    'password' => 'testpass',
                    'client_id' => 'test-client',
                    'client_secret' => 'test-secret',
                ],
            ]
        ];

        $headers = $auth_service->getAuthHeaders($endpoint);
        
        $this->assertEquals(['Authorization' => 'Bearer mock-token-123'], $headers);
    }

    public function test_token_caching_and_reuse()
    {
        $mock_response = new Response(200, [], json_encode([
            'access_token' => 'mock-token-123',
            'expires_in' => 3600,
            'token_type' => 'Bearer'
        ]));

        $mock = new MockHandler([$mock_response, $mock_response]); // Two responses for two calls
        $handler_stack = HandlerStack::create($mock);
        $handler_stack->push(Middleware::history($this->container));
        
        $client = new Client(['handler' => $handler_stack]);
        
        $auth_service = new AuthenticationService();
        $reflection = new \ReflectionClass($auth_service);
        $client_property = $reflection->getProperty('client');
        $client_property->setAccessible(true);
        $client_property->setValue($auth_service, $client);

        $endpoint = [
            'url' => 'https://example.com/api',
            'auth' => [
                'type' => 'token_endpoint',
                'token_endpoint' => 'https://example.com/oauth/token',
                'method' => 'POST',
                'auth_type' => 'form',
                'credentials' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => 'test-client',
                    'client_secret' => 'test-secret',
                ],
                'token_key' => 'test-key',
            ]
        ];

        // First call - should make token request
        $headers1 = $auth_service->getAuthHeaders($endpoint);
        $this->assertEquals(['Authorization' => 'Bearer mock-token-123'], $headers1);
        $this->assertCount(1, $this->container);

        // Second call - should reuse cached token
        $headers2 = $auth_service->getAuthHeaders($endpoint);
        $this->assertEquals(['Authorization' => 'Bearer mock-token-123'], $headers2);
        $this->assertCount(1, $this->container); // Still only one request
    }

    // TODO FIX THIS TEST
    // public function test_token_expiration_and_refresh()
    // {
    //     $mock_response = new Response(200, [], json_encode([
    //         'access_token' => 'mock-token-123',
    //         'expires_in' => 1, // Very short expiration
    //         'token_type' => 'Bearer'
    //     ]));

    //     $mock = new MockHandler([$mock_response, $mock_response]); // Two responses for two calls
    //     $handler_stack = HandlerStack::create($mock);
    //     $handler_stack->push(Middleware::history($this->container));
        
    //     $client = new Client(['handler' => $handler_stack]);
        
    //     $auth_service = new AuthenticationService();
    //     $reflection = new \ReflectionClass($auth_service);
    //     $client_property = $reflection->getProperty('client');
    //     $client_property->setAccessible(true);
    //     $client_property->setValue($auth_service, $client);

    //     $endpoint = [
    //         'url' => 'https://example.com/api',
    //         'auth' => [
    //             'type' => 'token_endpoint',
    //             'token_endpoint' => 'https://example.com/oauth/token',
    //             'method' => 'POST',
    //             'auth_type' => 'form',
    //             'credentials' => [
    //                 'grant_type' => 'client_credentials',
    //                 'client_id' => 'test-client',
    //                 'client_secret' => 'test-secret',
    //             ],
    //             'token_path' => 'access_token',
    //             'expires_in_path' => 'expires_in',
    //             'token_type_path' => 'token_type',
    //         ]
    //     ];

    //     // First call
    //     $headers1 = $auth_service->getAuthHeaders($endpoint);
    //     $this->assertEquals(['Authorization' => 'Bearer mock-token-123'], $headers1);
    //     $this->assertCount(1, $this->container);

    //     // Manually clear tokens to simulate expiration
    //     $auth_service->clearTokens();

    //     // Second call - should refresh token
    //     $headers2 = $auth_service->getAuthHeaders($endpoint);
    //     $this->assertEquals(['Authorization' => 'Bearer mock-token-123'], $headers2);
    //     $this->assertCount(2, $this->container); // Two requests made
    // }

    public function test_token_not_found_in_response()
    {
        $mock_response = new Response(200, [], json_encode([
            'error' => 'Invalid credentials'
        ]));

        $mock = new MockHandler([$mock_response]);
        $handler_stack = HandlerStack::create($mock);
        
        $client = new Client(['handler' => $handler_stack]);
        
        $auth_service = new AuthenticationService();
        $reflection = new \ReflectionClass($auth_service);
        $client_property = $reflection->getProperty('client');
        $client_property->setAccessible(true);
        $client_property->setValue($auth_service, $client);

        $endpoint = [
            'url' => 'https://example.com/api',
            'auth' => [
                'type' => 'token_endpoint',
                'token_endpoint' => 'https://example.com/oauth/token',
                'method' => 'POST',
                'auth_type' => 'form',
                'credentials' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => 'test-client',
                    'client_secret' => 'test-secret',
                ],
            ]
        ];

        $this->expectException(ApiScoutException::class);
        $this->expectExceptionMessage('Token not found in response at path: access_token');
        
        $auth_service->getAuthHeaders($endpoint);
    }

    public function test_invalid_json_response()
    {
        $mock_response = new Response(200, [], 'invalid json');

        $mock = new MockHandler([$mock_response]);
        $handler_stack = HandlerStack::create($mock);
        
        $client = new Client(['handler' => $handler_stack]);
        
        $auth_service = new AuthenticationService();
        $reflection = new \ReflectionClass($auth_service);
        $client_property = $reflection->getProperty('client');
        $client_property->setAccessible(true);
        $client_property->setValue($auth_service, $client);

        $endpoint = [
            'url' => 'https://example.com/api',
            'auth' => [
                'type' => 'token_endpoint',
                'token_endpoint' => 'https://example.com/oauth/token',
                'method' => 'POST',
                'auth_type' => 'form',
                'credentials' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => 'test-client',
                    'client_secret' => 'test-secret',
                ],
            ]
        ];

        $this->expectException(ApiScoutException::class);
        $this->expectExceptionMessage('Invalid JSON response from token endpoint');
        
        $auth_service->getAuthHeaders($endpoint);
    }

    // TODO FIX THIS TEST
    // public function test_clear_tokens()
    // {
    //     $mock_response = new Response(200, [], json_encode([
    //         'access_token' => 'mock-token-123',
    //         'expires_in' => 3600,
    //         'token_type' => 'Bearer'
    //     ]));

    //     $mock = new MockHandler([$mock_response, $mock_response]); // Two responses for two calls
    //     $handler_stack = HandlerStack::create($mock);
    //     $handler_stack->push(Middleware::history($this->container));
        
    //     $client = new Client(['handler' => $handler_stack]);
        
    //     $auth_service = new AuthenticationService();
    //     $reflection = new \ReflectionClass($auth_service);
    //     $client_property = $reflection->getProperty('client');
    //     $client_property->setAccessible(true);
    //     $client_property->setValue($auth_service, $client);

    //     $endpoint = [
    //         'url' => 'https://example.com/api',
    //         'auth' => [
    //             'type' => 'token_endpoint',
    //             'token_endpoint' => 'https://example.com/oauth/token',
    //             'method' => 'POST',
    //             'auth_type' => 'form',
    //             'credentials' => [
    //                 'grant_type' => 'client_credentials',
    //                 'client_id' => 'test-client',
    //                 'client_secret' => 'test-secret',
    //             ],
    //             'token_key' => 'test-key',
    //             'token_path' => 'access_token',
    //             'expires_in_path' => 'expires_in',
    //             'token_type_path' => 'token_type',
    //         ]
    //     ];

    //     // First call - should make token request
    //     $headers1 = $auth_service->getAuthHeaders($endpoint);
    //     $this->assertCount(1, $this->container);

    //     // Clear tokens
    //     $auth_service->clearTokens();

    //     // Second call - should make another token request
    //     $headers2 = $auth_service->getAuthHeaders($endpoint);
    //     $this->assertCount(2, $this->container);
    // }

    public function test_get_token_info()
    {
        $mock_response = new Response(200, [], json_encode([
            'access_token' => 'mock-token-123',
            'expires_in' => 3600,
            'token_type' => 'Bearer'
        ]));

        $mock = new MockHandler([$mock_response]);
        $handler_stack = HandlerStack::create($mock);
        
        $client = new Client(['handler' => $handler_stack]);
        
        $auth_service = new AuthenticationService();
        $reflection = new \ReflectionClass($auth_service);
        $client_property = $reflection->getProperty('client');
        $client_property->setAccessible(true);
        $client_property->setValue($auth_service, $client);

        $endpoint = [
            'url' => 'https://example.com/api',
            'auth' => [
                'type' => 'token_endpoint',
                'token_endpoint' => 'https://example.com/oauth/token',
                'method' => 'POST',
                'auth_type' => 'form',
                'credentials' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => 'test-client',
                    'client_secret' => 'test-secret',
                ],
                'token_key' => 'test-key',
            ]
        ];

        // Get token info before authentication
        $token_info = $auth_service->getTokenInfo('test-key');
        $this->assertNull($token_info);

        // Authenticate
        $auth_service->getAuthHeaders($endpoint);

        // Get token info after authentication
        $token_info = $auth_service->getTokenInfo('test-key');
        $this->assertNotNull($token_info);
        $this->assertEquals('mock-token-123', $token_info['token']);
        $this->assertEquals('Bearer', $token_info['token_type']);
        $this->assertEquals(3600, $token_info['expires_in']);
    }
}