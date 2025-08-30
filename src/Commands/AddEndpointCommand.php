<?php

namespace jcnghm\ApiScout\Commands;

use Illuminate\Console\Command;
use jcnghm\ApiScout\Facades\ApiScout;

class AddEndpointCommand extends Command
{
    protected $signature = 'api-scout:add-endpoint 
                          {key : The endpoint key/name}
                          {url : The endpoint URL}
                          {--method=GET : HTTP method}
                          {--type= : Authentication type (token_endpoint, bearer, basic, api_key)}
                          {--token-endpoint= : Token endpoint URL for token_endpoint auth}
                          {--client-id= : Client ID for OAuth2}
                          {--client-secret= : Client secret for OAuth2}
                          {--username= : Username for basic auth or password grant}
                          {--password= : Password for basic auth or password grant}
                          {--api-key= : API key for api_key auth}
                          {--header= : Header name for API key (default: X-API-Key)}
                          {--grant-type= : Grant type for OAuth2 (client_credentials, password)}
                          {--auth-method=POST : HTTP method for token endpoint}
                          {--auth-type=form : Credential format (form, json, query)}
                          {--token-path=access_token : Path to token in response}
                          {--expires-in-path=expires_in : Path to expires_in in response}
                          {--token-type-path=token_type : Path to token_type in response}
                          {--token-key=default : Unique key for token caching}
                          {--test : Test the endpoint after adding}';

    protected $description = 'Add a new API endpoint with optional authentication';

    public function handle()
    {
        $key = $this->argument('key');
        $url = $this->argument('url');
        $method = $this->option('method');

        $this->info("Adding endpoint: {$key}");
        $this->line("URL: {$url}");
        $this->line("Method: {$method}");
        $this->newLine();

        $config = [
            'url' => $url,
            'method' => $method,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ];

        $auth_type = $this->option('type');
        if ($auth_type) {
            $auth_config = $this->buildAuthConfig($auth_type);
            if ($auth_config) {
                $config['auth'] = $auth_config;
                $this->info("Authentication type: {$auth_type}");
            }
        }

        try {
            ApiScout::addEndpoint($key, $config);

            $this->updateConfigFile($key, $config);

            $this->info("✓ Endpoint '{$key}' added successfully!");

            if ($this->option('test')) {
                $this->testEndpoint($key);
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("✗ Failed to add endpoint: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    protected function buildAuthConfig(string $auth_type): ?array
    {
        switch ($auth_type) {
            case 'token_endpoint':
                return $this->buildTokenEndpointAuth();
            case 'bearer':
                return $this->buildBearerAuth();
            case 'basic':
                return $this->buildBasicAuth();
            case 'api_key':
                return $this->buildApiKeyAuth();
            default:
                $this->error("Unknown authentication type: {$auth_type}");
                return null;
        }
    }

    protected function buildTokenEndpointAuth(): array
    {
        $token_endpoint = $this->option('token-endpoint');
        if (!$token_endpoint) {
            throw new \InvalidArgumentException('Token endpoint URL is required for token_endpoint authentication');
        }

        $credentials = [];
        $grant_type = $this->option('grant-type') ?: 'client_credentials';
        $credentials['grant_type'] = $grant_type;

        if ($grant_type === 'client_credentials') {
            $client_id = $this->option('client-id');
            $client_secret = $this->option('client-secret');
            
            if (!$client_id || !$client_secret) {
                throw new \InvalidArgumentException('Client ID and Client Secret are required for client_credentials grant');
            }
            
            $credentials['client_id'] = $client_id;
            $credentials['client_secret'] = $client_secret;
        } elseif ($grant_type === 'password') {
            $username = $this->option('username');
            $password = $this->option('password');
            $client_id = $this->option('client-id');
            $client_secret = $this->option('client-secret');
            
            if (!$username || !$password || !$client_id || !$client_secret) {
                throw new \InvalidArgumentException('Username, Password, Client ID, and Client Secret are required for password grant');
            }
            
            $credentials['username'] = $username;
            $credentials['password'] = $password;
            $credentials['client_id'] = $client_id;
            $credentials['client_secret'] = $client_secret;
        }

        return [
            'type' => 'token_endpoint',
            'token_endpoint' => $token_endpoint,
            'method' => $this->option('auth-method'),
            'auth_type' => $this->option('auth-type'),
            'credentials' => $credentials,
            'token_path' => $this->option('token-path'),
            'expires_in_path' => $this->option('expires-in-path'),
            'token_type_path' => $this->option('token-type-path'),
            'token_key' => $this->option('token-key'),
        ];
    }

    protected function buildBearerAuth(): array
    {
        $token = $this->option('password');
        if (!$token) {
            throw new \InvalidArgumentException('Bearer token is required (use --password option)');
        }

        return [
            'type' => 'bearer',
            'token' => $token,
        ];
    }

    protected function buildBasicAuth(): array
    {
        $username = $this->option('username');
        $password = $this->option('password');
        
        if (!$username || !$password) {
            throw new \InvalidArgumentException('Username and Password are required for basic authentication');
        }

        return [
            'type' => 'basic',
            'username' => $username,
            'password' => $password,
        ];
    }

    protected function buildApiKeyAuth(): array
    {
        $apiKey = $this->option('api-key');
        if (!$apiKey) {
            throw new \InvalidArgumentException('API key is required for api_key authentication');
        }

        return [
            'type' => 'api_key',
            'key' => $apiKey,
            'header' => $this->option('header'),
        ];
    }

    protected function updateConfigFile(string $key, array $config): void
    {
        $config_path = config_path('api-scout.php');
        $current_config = require $config_path;

        $current_config['endpoints'][$key] = $config;

        $config_content = "<?php\n\nreturn " . var_export($current_config, true) . ";\n";
        file_put_contents($config_path, $config_content);

        $this->info("Configuration updated in: {$config_path}");
    }

    protected function testEndpoint(string $key): void
    {
        $this->newLine();
        $this->info('Testing endpoint...');

        try {
            $result = ApiScout::analyze($key);
            $this->info('✓ Endpoint test successful!');
            $this->line("Found {$result->getTotalRecords()} records with " . count($result->getFields()) . " fields.");
        } catch (\Exception $e) {
            $this->error('✗ Endpoint test failed: ' . $e->getMessage());
        }
    }
}
