<?php

namespace jcnghm\ApiScout\Commands;

use Illuminate\Console\Command;
use jcnghm\ApiScout\Facades\ApiScout;
use jcnghm\ApiScout\DataTypes\TypeDetector;

class AnalyzeApiCommand extends Command
{
    protected $signature = 'api-scout:analyze 
                          {endpoint? : The endpoint key to analyze}
                          {--all : Analyze all configured endpoints}
                          {--json : Output results as JSON}';

    protected $description = 'Analyze API endpoints and display their structure';

    public function handle()
    {
        if ($this->option('all')) {
            $this->analyzeAll();
        } elseif ($endpoint = $this->argument('endpoint')) {
            $this->analyzeSingle($endpoint);
        } else {
            $this->showEndpointSelection();
        }

        return Command::SUCCESS;
    }

    protected function analyzeAll()
    {
        $endpoints = ApiScout::getEndpoints();
        
        if (empty($endpoints)) {
            $this->error('No endpoints configured. Please add endpoints to your api-scout.php config file.');
            return;
        }

        $this->info('Analyzing all endpoints...');
        $this->newLine();

        $results = [];
        foreach ($endpoints as $endpoint) {
            $this->line("Analyzing: {$endpoint}");
            try {
                $result = ApiScout::analyze($endpoint);
                $results[$endpoint] = $result;
                $this->info('✓ Success');
            } catch (\Exception $e) {
                $this->error("✗ Failed: {$e->getMessage()}");
                continue;
            }
        }

        $this->newLine();
        
        if ($this->option('json')) {
            $this->line(json_encode(array_map(fn($r) => $r->toArray(), $results), JSON_PRETTY_PRINT));
        } else {
            foreach ($results as $endpoint => $result) {
                $this->displayResult($result);
                $this->newLine();
            }
        }
    }

    protected function analyzeSingle(string $endpoint)
    {
        $this->info("Analyzing endpoint: {$endpoint}");
        
        try {
            $result = ApiScout::analyze($endpoint);
            
            if ($this->option('json')) {
                $this->line($result->toJson(JSON_PRETTY_PRINT));
            } else {
                $this->displayResult($result);
            }
            
        } catch (\Exception $e) {
            $this->error("Failed to analyze endpoint: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    protected function showEndpointSelection()
    {
        $endpoints = ApiScout::getEndpoints();
        
        if (empty($endpoints)) {
            $this->error('No endpoints configured. Please add endpoints to your api-scout.php config file.');
            $this->newLine();
            $this->info('Example configuration:');
            $this->line("'endpoints' => [");
            $this->line("    'users' => [");
            $this->line("        'url' => 'https://api.example.com/users',");
            $this->line("        'method' => 'GET',");
            $this->line("    ],");
            $this->line("],");
            return;
        }

        $endpoint = $this->choice('Select an endpoint to analyze:', $endpoints);
        $this->analyzeSingle($endpoint);
    }

    protected function displayResult($result)
    {
        $summary = $result->getSummary();
        $type_detector = new TypeDetector();
        
        $this->info("=== {$summary['endpoint']} ===");
        $this->line("Type: " . ucfirst($summary['type']));
        $this->line("Records: {$summary['total_records']}");
        $this->line("Fields: {$summary['field_count']}");
        $this->line("Analyzed: {$summary['analyzed_at']}");
        $this->newLine();

        if (!empty($result->getFields())) {
            $this->info('Fields:');
            
            $headers = ['Field', 'Type', 'Nullable', 'Example'];
            $rows = [];
            
            foreach ($result->getFields() as $fieldName => $field) {
                $rows[] = [
                    $fieldName,
                    $type_detector->getHumanType($field['type']),
                    $field['nullable'] ? 'Yes' : 'No',
                    $this->formatExample($field['example'] ?? null)
                ];
            }
            
            $this->table($headers, $rows);
        }
    }

    protected function formatExample($example): string
    {
        if (is_null($example)) {
            return 'null';
        }
        
        if (is_bool($example)) {
            return $example ? 'true' : 'false';
        }
        
        if (is_array($example)) {
            return json_encode($example);
        }
        
        if (is_string($example) && strlen($example) > 30) {
            return substr($example, 0, 30) . '...';
        }
        
        return (string) $example;
    }
}