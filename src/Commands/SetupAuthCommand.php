<?php

namespace jcnghm\ApiScout\Commands;

use Illuminate\Console\Command;
use jcnghm\ApiScout\Services\AuthenticationService;

class SetupAuthCommand extends Command
{
    protected $signature = 'api-scout:setup-auth 
                          {endpoint : The endpoint key to set up authentication for}
                          {--test : Test the authentication after setup}
                          {--type= : Authentication type (token_endpoint, bearer, basic, api_key)}
                          {--token-endpoint= : Token endpoint URL for token_endpoint auth}
                          {--client-id= : Client ID for OAuth2}
                          {--client-secret= : Client secret for OAuth2}
                          {--username= : Username for basic auth or password grant}
                          {--password= : Password for basic auth or password grant}
                          {--api-key= : API key for api_key auth}
                          {--header= : Header name for API key (default: X-API-Key)}
                          {--grant-type= : Grant type for OAuth2 (client_credentials, password)}
                          {--method=POST : HTTP method for token endpoint}
                          {--auth-type=form : Credential format (form, json, query)}
                          {--token-path=access_token : Path to token in response}
                          {--expires-in-path=expires_in : Path to expires_in in response}
                          {--token-type-path=token_type : Path to token_type in response}
                          {--token-key=default : Unique key for token caching}
                          {--interactive : Force interactive mode even if options are provided}';

    protected $description = 'Set up authentication for an API endpoint';

    public function handle()
    {
        $endpoint_key = $this->argument('endpoint');
        $config = config('api-scout');
        
        if (!isset($config['endpoints'][$endpoint_key])) {
            $this->error("Endpoint '{$endpoint_key}' not found in configuration.");
            return Command::FAILURE;
        }

        $endpoint = $config['endpoints'][$endpoint_key];
        
        $this->info("Setting up authentication for endpoint: {$endpoint_key}");
        $this->newLine();

        // Check if we have enough options to proceed non-interactively
        $auth_type = $this->option('type');
        $is_interactive = $this->option('interactive') || !$this->hasRequiredOptions($auth_type);

        if ($is_interactive) {
            $auth_type = $this->choice(
                'What type of authentication does this API use?',
                [
                    'token_endpoint' => 'Token Endpoint (OAuth2, JWT, etc.)',
                    'bearer' => 'Bearer Token (static)',
                    'basic' => 'Basic Authentication',
                    'api_key' => 'API Key',
                    'none' => 'No Authentication',
                ]
            );
        }

        if ($auth_type === 'none') {
            $this->info('No authentication needed for this endpoint.');
            return Command::SUCCESS;
        }

        $auth_config = $is_interactive 
            ? $this->getAuthConfig($auth_type)
            : $this->getAuthConfigFromOptions($auth_type);
        
        if ($auth_config === null) {
            $this->error('Authentication setup cancelled.');
            return Command::FAILURE;
        }

        $this->updateEndpointConfig($is_interactive, $auth_config);

        $this->info('Authentication configuration updated successfully!');
        $this->newLine();

        if ($this->option('test')) {
            $this->testAuthentication($is_interactive, $auth_config);
        }

        return Command::SUCCESS;
    }

    protected function hasRequiredOptions(string $auth_type): bool
    {
        if (!$auth_type) {
            return false;
        }

        switch ($auth_type) {
            case 'token_endpoint':
                return $this->option('token-endpoint') && 
                       ($this->option('client-id') || $this->option('username'));
            case 'bearer':
                return $this->option('password');
            case 'basic':
                return $this->option('username') && $this->option('password');
            case 'api_key':
                return $this->option('api-key');
            default:
                return false;
        }
    }

    protected function getAuthConfigFromOptions(string $auth_type): ?array
    {
        switch ($auth_type) {
            case 'token_endpoint':
                return $this->buildTokenEndpointConfig();
            case 'bearer':
                return $this->buildBearerConfig();
            case 'basic':
                return $this->buildBasicConfig();
            case 'api_key':
                return $this->buildApiKeyConfig();
            default:
                return null;
        }
    }

    protected function buildTokenEndpointConfig(): array
    {
        $credentials = [];
        $grant_type = $this->option('grant-type') ?: 'client_credentials';
        $credentials['grant_type'] = $grant_type;

        if ($grant_type === 'client_credentials') {
            $credentials['client_id'] = $this->option('client-id');
            $credentials['client_secret'] = $this->option('client-secret');
        } elseif ($grant_type === 'password') {
            $credentials['username'] = $this->option('username');
            $credentials['password'] = $this->option('password');
            $credentials['client_id'] = $this->option('client-id');
            $credentials['client_secret'] = $this->option('client-secret');
        }

        return [
            'type' => 'token_endpoint',
            'token_endpoint' => $this->option('token-endpoint'),
            'method' => $this->option('method'),
            'auth_type' => $this->option('auth-type'),
            'credentials' => $credentials,
            'token_path' => $this->option('token-path'),
            'expires_in_path' => $this->option('expires-in-path'),
            'token_type_path' => $this->option('token-type-path'),
            'token_key' => $this->option('token-key'),
        ];
    }

    protected function buildBearerConfig(): array
    {
        return [
            'type' => 'bearer',
            'token' => $this->option('password'), // Using password option for bearer token
        ];
    }

    protected function buildBasicConfig(): array
    {
        return [
            'type' => 'basic',
            'username' => $this->option('username'),
            'password' => $this->option('password'),
        ];
    }

    protected function buildApiKeyConfig(): array
    {
        return [
            'type' => 'api_key',
            'key' => $this->option('api-key'),
            'header' => $this->option('header'),
        ];
    }

    protected function getAuthConfig(string $auth_type): ?array
    {
        switch ($auth_type) {
            case 'token_endpoint':
                return $this->setupTokenEndpointAuth();
            case 'bearer':
                return $this->setupBearerAuth();
            case 'basic':
                return $this->setupBasicAuth();
            case 'api_key':
                return $this->setupApiKeyAuth();
            default:
                return null;
        }
    }

    protected function setupTokenEndpointAuth(): ?array
    {
        $this->info('Setting up Token Endpoint Authentication');
        $this->newLine();

        $token_endpoint = $this->ask('Token endpoint URL (e.g., https://api.example.com/oauth/token)');
        if (!$token_endpoint) {
            return null;
        }

        $method = $this->choice('HTTP method for token request', ['POST', 'GET'], 'POST');
        $auth_type = $this->choice('Credential format', ['form', 'json', 'query'], 'form');

        $this->info('Enter credentials for token request:');
        $credentials = [];
        
        $grant_type = $this->choice('Grant type', [
            'client_credentials' => 'Client Credentials',
            'password' => 'Password Grant',
            'authorization_code' => 'Authorization Code',
        ], 'client_credentials');
        
        $credentials['grant_type'] = $grant_type;

        if ($grant_type === 'client_credentials') {
            $credentials['client_id'] = $this->ask('Client ID');
            $credentials['client_secret'] = $this->secret('Client Secret');
        } elseif ($grant_type === 'password') {
            $credentials['username'] = $this->ask('Username');
            $credentials['password'] = $this->secret('Password');
            $credentials['client_id'] = $this->ask('Client ID');
            $credentials['client_secret'] = $this->secret('Client Secret');
        }

        $token_path = $this->ask('Token path in response (default: access_token)', 'access_token');
        $expires_in_path = $this->ask('Expires in path in response (default: expires_in)', 'expires_in');
        $token_type_path = $this->ask('Token type path in response (default: token_type)', 'token_type');

        return [
            'type' => 'token_endpoint',
            'token_endpoint' => $token_endpoint,
            'method' => $method,
            'auth_type' => $auth_type,
            'credentials' => $credentials,
            'token_path' => $token_path,
            'expires_in_path' => $expires_in_path,
            'token_type_path' => $token_type_path,
        ];
    }

    protected function setupBearerAuth(): ?array
    {
        $this->info('Setting up Bearer Token Authentication');
        $this->newLine();

        $token = $this->secret('Bearer Token');
        if (!$token) {
            return null;
        }

        return [
            'type' => 'bearer',
            'token' => $token,
        ];
    }

    protected function setupBasicAuth(): ?array
    {
        $this->info('Setting up Basic Authentication');
        $this->newLine();

        $username = $this->ask('Username');
        if (!$username) {
            return null;
        }

        $password = $this->secret('Password');
        if (!$password) {
            return null;
        }

        return [
            'type' => 'basic',
            'username' => $username,
            'password' => $password,
        ];
    }

    protected function setupApiKeyAuth(): ?array
    {
        $this->info('Setting up API Key Authentication');
        $this->newLine();

        $key = $this->secret('API Key');
        if (!$key) {
            return null;
        }

        $header = $this->ask('Header name (default: X-API-Key)', 'X-API-Key');

        return [
            'type' => 'api_key',
            'key' => $key,
            'header' => $header,
        ];
    }

    protected function updateEndpointConfig(string $endpoint_key, array $auth_config): void
    {
        $config_path = config_path('api-scout.php');
        $config = require $config_path;

        $config['endpoints'][$endpoint_key]['auth'] = $auth_config;

        // Write the updated configuration back to the file
        $config_content = "<?php\n\nreturn " . var_export($config, true) . ";\n";
        file_put_contents($config_path, $config_content);

        $this->info("Configuration updated in: {$config_path}");
    }

    protected function testAuthentication(string $endpoint_key, array $auth_config): void
    {
        $this->newLine();
        $this->info('Testing authentication...');

        try {
            $service = new AuthenticationService(config('api-scout'));
            
            // Create a mock endpoint config for testing
            $test_endpoint = [
                'url' => 'https://httpbin.org/bearer', // Test endpoint
                'auth' => $auth_config,
            ];

            $headers = $service->getAuthHeaders($test_endpoint);
            
            if (!empty($headers)) {
                $this->info('✓ Authentication headers generated successfully');
                $this->line('Headers: ' . json_encode($headers, JSON_PRETTY_PRINT));
            } else {
                $this->warn('⚠ No authentication headers generated');
            }

        } catch (\Exception $e) {
            $this->error('✗ Authentication test failed: ' . $e->getMessage());
        }
    }
}
