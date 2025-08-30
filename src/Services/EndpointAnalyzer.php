<?php

namespace jcnghm\ApiScout\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use jcnghm\ApiScout\Exceptions\ApiScoutException;
use jcnghm\ApiScout\DataTypes\TypeDetector;

class EndpointAnalyzer
{
    protected Client $client;
    protected TypeDetector $typeDetector;
    protected AuthenticationService $authService;
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->client = new Client([
            'timeout' => $config['timeout'] ?? 30,
            'connect_timeout' => $config['connect_timeout'] ?? 10,
        ]);
        $this->typeDetector = new TypeDetector($config['type_detection'] ?? []);
        $this->authService = new AuthenticationService($config);
    }

    public function analyze(array $endpoint): array
    {
        try {
            $response = $this->makeRequest($endpoint);
            $data = json_decode($response->getBody()->getContents(), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new ApiScoutException('Invalid JSON response from endpoint');
            }

            return $this->analyzeStructure($data);
            
        } catch (RequestException $e) {
            throw new ApiScoutException(
                "Failed to analyze endpoint: " . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    protected function makeRequest(array $endpoint): \Psr\Http\Message\ResponseInterface
    {
        $options = [
            'headers' => $endpoint['headers'] ?? [],
        ];

        // Get authentication headers from the authentication service
        $authHeaders = $this->authService->getAuthHeaders($endpoint);
        $options['headers'] = array_merge($options['headers'], $authHeaders);

        return $this->client->request(
            $endpoint['method'] ?? 'GET',
            $endpoint['url'],
            $options
        );
    }

    protected function analyzeStructure($data): array
    {
        return [
            'is_array' => is_array($data) && array_is_list($data),
            'is_object' => is_array($data) && !array_is_list($data),
            'total_records' => $this->getTotalRecords($data),
            'fields' => $this->analyzeFields($data),
            'sample_data' => $this->getSampleData($data),
            'analyzed_at' => now()->toISOString(),
        ];
    }

    protected function getTotalRecords($data): int
    {
        if (is_array($data) && array_is_list($data)) {
            return count($data);
        }
        
        return 1;
    }

    protected function analyzeFields($data): array
    {
        if (is_array($data) && array_is_list($data)) {
            $sampleSize = min(
                count($data), 
                $this->config['type_detection']['sample_size'] ?? 5
            );
            
            $fields = [];
            for ($i = 0; $i < $sampleSize; $i++) {
                $itemFields = $this->analyzeObject($data[$i]);
                $fields = $this->mergeFields($fields, $itemFields);
            }
            
            return $fields;
        } else {
            return $this->analyzeObject($data);
        }
    }

    protected function analyzeObject($object): array
    {
        if (!is_array($object)) {
            return [];
        }

        $fields = [];
        foreach ($object as $key => $value) {
            $fields[$key] = [
                'type' => $this->typeDetector->detectType($value),
                'nullable' => is_null($value),
                'example' => $this->getExampleValue($value),
            ];

            if (is_array($value)) {
                $fields[$key]['nested'] = $this->analyzeStructure($value);
            }
        }

        return $fields;
    }

    protected function mergeFields(array $existing, array $new): array
    {
        foreach ($new as $key => $field) {
            if (!isset($existing[$key])) {
                $existing[$key] = $field;
            } else {
                // Field is nullable if either existing or new field is nullable
                if ($field['nullable'] || $existing[$key]['nullable']) {
                    $existing[$key]['nullable'] = true;
                }
                
                if (is_null($existing[$key]['example']) && !is_null($field['example'])) {
                    $existing[$key]['example'] = $field['example'];
                }
            }
        }

        // Mark fields that don't appear in all records as nullable
        foreach ($existing as $key => $field) {
            if (!isset($new[$key])) {
                $existing[$key]['nullable'] = true;
            }
        }

        return $existing;
    }

    protected function getSampleData($data): mixed
    {
        if (is_array($data) && array_is_list($data)) {
            return array_slice($data, 0, 3);
        }
        
        return $data;
    }

    protected function getExampleValue($value): mixed
    {
        if (is_string($value) && strlen($value) > 50) {
            return substr($value, 0, 50) . '...';
        }
        
        return $value;
    }

    /**
     * Get the authentication service instance
     */
    public function getAuthService(): AuthenticationService
    {
        return $this->authService;
    }
}