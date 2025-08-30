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

    /**
     * Get authentication headers for an endpoint
     */
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

    /**
     * Handle token endpoint authentication
     */
    protected function getTokenEndpointHeaders(array $auth): array
    {
        $token_key = $auth['token_key'] ?? 'default';
        
        // Check if we already have a valid token
        if (isset($this->tokens[$token_key]) && !$this->isTokenExpired($this->tokens[$token_key])) {
            return ['Authorization' => 'Bearer ' . $this->tokens[$token_key]['token']];
        }

        // Get a new token
        $token = $this->fetchToken($auth);
        $this->tokens[$token_key] = $token;

        return ['Authorization' => 'Bearer ' . $token['token']];
    }

    /**
     * Fetch a token from the authentication endpoint
     */
    protected function fetchToken(array $auth): array
    {
        $token_endpoint = $auth['token_endpoint'];
        $credentials = $auth['credentials'] ?? [];
        $method = $auth['method'] ?? 'POST';
        $headers = $auth['headers'] ?? ['Content-Type' => 'application/json'];

        $options = [
            'headers' => $headers,
        ];

        // Add credentials based on the auth type
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

    /**
     * Extract token from the authentication response
     */
    protected function extractTokenFromResponse(array $data, array $auth): array
    {
        $tokenPath = $auth['token_path'] ?? 'access_token';
        $expiresInPath = $auth['expires_in_path'] ?? 'expires_in';
        $tokenTypePath = $auth['token_type_path'] ?? 'token_type';

        $token = $this->getNestedValue($data, $tokenPath);
        
        if (!$token) {
            throw new ApiScoutException("Token not found in response at path: {$tokenPath}");
        }

        $expiresIn = $this->getNestedValue($data, $expiresInPath);
        $tokenType = $this->getNestedValue($data, $tokenTypePath) ?? 'Bearer';

        return [
            'token' => $token,
            'token_type' => $tokenType,
            'expires_in' => $expiresIn,
            'expires_at' => $expiresIn ? time() + $expiresIn : null,
        ];
    }

    /**
     * Get a nested value from an array using dot notation
     */
    protected function getNestedValue(array $array, string $path)
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

    /**
     * Check if a token is expired
     */
    protected function isTokenExpired(array $token): bool
    {
        if (!isset($token['expires_at'])) {
            return false; // No expiration, consider it valid
        }

        // Add a 30-second buffer to refresh before actual expiration
        return time() >= ($token['expires_at'] - 30);
    }

    /**
     * Clear cached tokens
     */
    public function clearTokens(): void
    {
        $this->tokens = [];
    }

    /**
     * Get cached token info
     */
    public function getTokenInfo(string $tokenKey = 'default'): ?array
    {
        return $this->tokens[$tokenKey] ?? null;
    }
}
