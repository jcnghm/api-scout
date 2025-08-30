<?php

namespace Jcnghm\ApiScout\Commands;

use Illuminate\Console\Command;
use Jcnghm\ApiScout\Facades\ApiScout;

class GenerateComponentsCommand extends Command
{
    protected $signature = 'api-scout:generate 
                          {endpoint? : The endpoint key to generate components for}
                          {--all : Generate components for all configured endpoints}
                          {--livewire : Generate only Livewire components}
                          {--blade : Generate only Blade components}
                          {--force : Overwrite existing components}';

    protected $description = 'Generate Livewire and Blade components for API endpoints';

    public function handle()
    {
        if ($this->option('all')) {
            $this->generateAll();
        } elseif ($endpoint = $this->argument('endpoint')) {
            $this->generateSingle($endpoint);
        } else {
            $this->showEndpointSelection();
        }

        return Command::SUCCESS;
    }

    protected function generateAll()
    {
        $endpoints = ApiScout::getEndpoints();
        
        if (empty($endpoints)) {
            $this->error('No endpoints configured. Please add endpoints to your api-scout.php config file.');
            return;
        }

        $this->info('Generating components for all endpoints...');
        $this->newLine();

        $success_count = 0;
        $total_count = count($endpoints);

        foreach ($endpoints as $endpoint) {
            $this->line("Generating components for: {$endpoint}");
            
            if ($this->generateComponents($endpoint)) {
                $this->info('✓ Success');
                $success_count++;
            } else {
                $this->error('✗ Failed');
            }
        }

        $this->newLine();
        $this->info("Generated components for {$success_count}/{$total_count} endpoints.");
    }

    protected function generateSingle(string $endpoint)
    {
        $this->info("Generating components for endpoint: {$endpoint}");
        
        if ($this->generateComponents($endpoint)) {
            $this->info('✓ Components generated successfully!');
            $this->showGeneratedFiles($endpoint);
        } else {
            $this->error('✗ Failed to generate components.');
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

        $endpoint = $this->choice('Select an endpoint to generate components for:', $endpoints);
        $this->generateSingle($endpoint);
    }

    protected function generateComponents(string $endpoint): bool
    {
        try {
            // Get generation options from command options
            $options = $this->getGenerationOptions();
            
            // First analyze the endpoint
            $result = ApiScout::analyze($endpoint);
            
            // Generate components
            $success = $result->generateComponents($options);
            
            return $success;
            
        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");
            return false;
        }
    }

    protected function getGenerationOptions(): array
    {
        $options = [];

        // Determine what to generate based on options
        if ($this->option('livewire') && !$this->option('blade')) {
            $options['generate_blade'] = false;
            $options['generate_livewire'] = true;
        } elseif ($this->option('blade') && !$this->option('livewire')) {
            $options['generate_blade'] = true;
            $options['generate_livewire'] = false;
        } else {
            // Default: generate both
            $options['generate_blade'] = true;
            $options['generate_livewire'] = true;
        }

        // Force overwrite if specified
        $options['force'] = $this->option('force');

        return $options;
    }

    protected function showGeneratedFiles(string $endpoint)
    {
        $this->newLine();
        $this->info('Generated files:');
        
        $config = config('api-scout.components', []);
        
        if ($this->shouldGenerate('blade')) {
            $viewPath = resource_path('views/api-scout');
            $fileName = \Illuminate\Support\Str::kebab($endpoint) . '.blade.php';
            $this->line("• Blade component: {$viewPath}/{$fileName}");
        }

        if ($this->shouldGenerate('livewire')) {
            $componentName = \Illuminate\Support\Str::studly($endpoint);
            $outputPath = base_path($config['output_path'] ?? 'app/Http/Livewire/ApiScout');
            $viewPath = resource_path('views/livewire/api-scout');
            $viewFile = \Illuminate\Support\Str::kebab($endpoint) . '.blade.php';
            
            $this->line("• Livewire class: {$outputPath}/{$componentName}.php");
            $this->line("• Livewire view: {$viewPath}/{$viewFile}");
        }

        $this->newLine();
        $this->info('Usage examples:');
        
        if ($this->shouldGenerate('blade')) {
            $this->line("Blade: @include('api-scout." . \Illuminate\Support\Str::kebab($endpoint) . "', ['data' => \$apiData])");
        }
        
        if ($this->shouldGenerate('livewire')) {
            $componentName = 'api-scout.' . \Illuminate\Support\Str::kebab($endpoint);
            $this->line("Livewire: <livewire:{$componentName} />");
        }
    }

    protected function shouldGenerate(string $type): bool
    {
        if ($type === 'blade') {
            return !$this->option('livewire') || $this->option('blade');
        }
        
        if ($type === 'livewire') {
            return !$this->option('blade') || $this->option('livewire');
        }
        
        return true;
    }
}