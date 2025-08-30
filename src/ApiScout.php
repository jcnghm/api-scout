<?php

namespace jcnghm\ApiScout;

use jcnghm\ApiScout\Services\EndpointAnalyzer;
use jcnghm\ApiScout\Services\ComponentGenerator;
use jcnghm\ApiScout\Exceptions\ApiScoutException;

class ApiScout
{
    protected EndpointAnalyzer $analyzer;
    protected ComponentGenerator $generator;
    protected array $config;

    public function __construct()
    {
        $this->config = config('api-scout');
        $this->analyzer = new EndpointAnalyzer($this->config);
        $this->generator = new ComponentGenerator($this->config);
    }

    public function analyze(string $key): ApiScoutResult
    {
        $endpoint = $this->getEndpointConfig($key);
        
        $analysis = $this->analyzer->analyze($endpoint);
        
        return new ApiScoutResult($key, $analysis);
    }

    public function analyzeAll(): array
    {
        $results = [];
        
        foreach ($this->config['endpoints'] as $key => $endpoint) {
            $results[$key] = $this->analyze($key);
        }
        
        return $results;
    }

    public function generateComponents(string $key, array $options = []): bool
    {
        $result = $this->analyze($key);
        
        return $this->generator->generate($result, $options);
    }

    protected function getEndpointConfig(string $key): array
    {
        if (!isset($this->config['endpoints'][$key])) {
            throw new ApiScoutException("Endpoint '{$key}' not found in configuration");
        }

        return array_merge([
            'method' => 'GET',
            'headers' => ['Accept' => 'application/json'],
            'auth' => null,
        ], $this->config['endpoints'][$key]);
    }

    public function addEndpoint(string $key, array $config): self
    {
        $this->config['endpoints'][$key] = $config;
        
        return $this;
    }

    public function getEndpoints(): array
    {
        return array_keys($this->config['endpoints']);
    }
}