<?php

namespace jcnghm\ApiScout\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use jcnghm\ApiScout\Exceptions\ApiScoutException;

class AuthenticationService
{
    protected Client $client;
    protected array $tokens = [];

    public function __construct(array $config = [])
    {
        $this->client = new Client([
            'timeout' => $config['timeout'] ?? 30,
            'connect_timeout' => $config['connect_timeout'] ?? 10,
        ]);
    }

    public function getAuthHeaders(array $endpoint): array
    {
        $auth = $endpoint['auth'] ?? null;
        
        if (!$auth) {
            return [];
        }

        switch ($auth['type'] ?? 'bearer') {
            case 'token_endpoint':
                return $this->getTokenEndpointHeaders($auth);
            case 'bearer':
                return ['Authorization' => 'Bearer ' . $auth['token']];
            case 'basic':
                return ['Authorization' => 'Basic ' . base64_encode($auth['username'] . ':' . $auth['password'])];
            case 'api_key':
                return [$auth['header'] ?? 'X-API-Key' => $auth['key']];
            default:
                return [];
        }
    }

    protected function getTokenEndpointHeaders(array $auth): array
    {
        $token_key = $auth['token_key'] ?? 'default';
        
        if (isset($this->tokens[$token_key]) && !$this->isTokenExpired($this->tokens[$token_key])) {
            return ['Authorization' => 'Bearer ' . $this->tokens[$token_key]['token']];
        }

        $token = $this->fetchToken($auth);
        $this->tokens[$token_key] = $token;

        return ['Authorization' => 'Bearer ' . $token['token']];
    }

    protected function fetchToken(array $auth): array
    {
        $token_endpoint = $auth['token_endpoint'];
        $credentials = $auth['credentials'] ?? [];
        $method = $auth['method'] ?? 'POST';
        $headers = $auth['headers'] ?? ['Content-Type' => 'application/json'];

        $options = [
            'headers' => $headers,
        ];

        if (isset($auth['auth_type'])) {
            switch ($auth['auth_type']) {
                case 'form':
                    $options['form_params'] = $credentials;
                    break;
                case 'json':
                    $options['json'] = $credentials;
                    break;
                case 'query':
                    $options['query'] = $credentials;
                    break;
                default:
                    $options['json'] = $credentials;
            }
        } else {
            $options['json'] = $credentials;
        }

        try {
            $response = $this->client->request($method, $token_endpoint, $options);
            $data = json_decode($response->getBody()->getContents(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new ApiScoutException('Invalid JSON response from token endpoint');
            }

            return $this->extractTokenFromResponse($data, $auth);

        } catch (RequestException $e) {
            throw new ApiScoutException(
                "Failed to authenticate: " . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    protected function extractTokenFromResponse(array $data, array $auth): array
    {
        $token_path = $auth['token_path'] ?? 'access_token';
        $expires_in_path = $auth['expires_in_path'] ?? 'expires_in';
        $token_type_path = $auth['token_type_path'] ?? 'token_type';

        $token = $this->getNestedValue($data, $token_path);
        
        if (!$token) {
            throw new ApiScoutException("Token not found in response at path: {$token_path}");
        }

        $expires_in = $this->getNestedValue($data, $expires_in_path);
        $token_type = $this->getNestedValue($data, $token_type_path) ?? 'Bearer';

        return [
            'token' => $token,
            'token_type' => $token_type,
            'expires_in' => $expires_in,
            'expires_at' => $expires_in ? time() + $expires_in : null,
        ];
    }

    protected function getNestedValue(array $array, string $path): mixed
    {
        $keys = explode('.', $path);
        $value = $array;

        foreach ($keys as $key) {
            if (!isset($value[$key])) {
                return null;
            }
            $value = $value[$key];
        }

        return $value;
    }

    protected function isTokenExpired(array $token): bool
    {
        if (!isset($token['expires_at'])) {
            return false;
        }

        return time() >= ($token['expires_at'] - 30);
    }

    public function clearTokens(): void
    {
        $this->tokens = [];
    }

    public function getTokenInfo(string $key = 'default'): ?array
    {
        return $this->tokens[$key] ?? null;
    }
}
