<div align="center">
  <img src="https://laravel.com/img/logomark.min.svg" alt="Laravel" width="100" height="100">

# API Scout
[![PHP Version](https://img.shields.io/badge/php-8.1%2B-blue.svg)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/laravel-10%2B%20%7C%2011%2B-red.svg)](https://laravel.com)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
A Laravel package for mapping and exploring REST API endpoints with automatic component generation.

</div>

## Features

- **API Endpoint Analysis**: Automatically analyze and map API response structures
- **Multi-Authentication Support**: Bearer tokens, OAuth2, Basic auth, API keys, and custom token endpoints
- **Smart Type Detection**: Detect data types including emails, URLs, UUIDs, dates, JSON, and more
- **Livewire Component Generation**: Auto-generate Livewire components for API data
- **Blade Template Generation**: Create Blade views for your API responses
- **Command-Line Interface**: Powerful CLI tools for setup and management
- **Field Analysis**: Detailed field type analysis with nullable detection
- **Token Caching**: Automatic token caching and refresh for authenticated APIs

## Installation

```bash
composer require jcnghm/api-scout
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=api-scout-config
```

## Quick Start

### 1. Add an API Endpoint

```bash
# Add a simple endpoint
php artisan api-scout:add-endpoint users https://jsonplaceholder.typicode.com/users

# Add with authentication
php artisan api-scout:add-endpoint protected-users https://api.example.com/users \
  --type=token_endpoint \
  --token-endpoint=https://api.example.com/oauth/token \
  --client-id=your-client-id \
  --client-secret=your-client-secret \
  --test
```

### 2. Analyze Your Endpoint

```bash
# Analyze a single endpoint
php artisan api-scout:analyze users

# Analyze all endpoints
php artisan api-scout:analyze --all

# Output as JSON
php artisan api-scout:analyze users --json
```

### 3. Generate Components

```bash
# Generate Livewire and Blade components
php artisan api-scout:generate users

# Generate only Livewire components
php artisan api-scout:generate users --livewire

# Generate for all endpoints
php artisan api-scout:generate --all
```

## Authentication Support

API Scout supports multiple authentication methods:

### Bearer Token

```php
'auth' => [
    'type' => 'bearer',
    'token' => env('API_TOKEN'),
]
```

### OAuth2 Token Endpoint

```php
'auth' => [
    'type' => 'token_endpoint',
    'token_endpoint' => 'https://api.example.com/oauth/token',
    'method' => 'POST',
    'auth_type' => 'form',
    'credentials' => [
        'grant_type' => 'client_credentials',
        'client_id' => env('API_CLIENT_ID'),
        'client_secret' => env('API_CLIENT_SECRET'),
    ],
    'token_path' => 'access_token',
    'expires_in_path' => 'expires_in',
]
```

### Basic Authentication

```php
'auth' => [
    'type' => 'basic',
    'username' => env('API_USERNAME'),
    'password' => env('API_PASSWORD'),
]
```

### API Key

```php
'auth' => [
    'type' => 'api_key',
    'key' => env('API_KEY'),
    'header' => 'X-API-Key',
]
```

## Data Type Detection

API Scout automatically detects and categorizes data types:

- **Primitive Types**: `null`, `boolean`, `integer`, `float`, `string`
- **Specialized Types**: `email`, `url`, `uuid`, `datetime`, `date`
- **Complex Types**: `numeric_string`, `json_string`, `base64`, `array`, `object`

## Configuration

Edit `config/api-scout.php` to customize your settings:

```php
return [
    'timeout' => 30,
    'connect_timeout' => 10,

    'endpoints' => [
        'users' => [
            'url' => 'https://api.example.com/users',
            'method' => 'GET',
            'headers' => [
                'Accept' => 'application/json',
            ],
            'auth' => [
                'type' => 'bearer',
                'token' => env('API_TOKEN'),
            ],
        ],
    ],

    'components' => [
        'generate_livewire' => true,
        'generate_blade' => true,
        'output_path' => 'app/Http/Livewire/ApiScout',
        'view_path' => 'resources/views/api-scout',
        'namespace' => 'App\\Http\\Livewire\\ApiScout',
    ],

    'type_detection' => [
        'sample_size' => 5,
        'strict_types' => false,
    ],
];
```

## Available Commands

| Command                  | Description           |
| ------------------------ | --------------------- |
| `api-scout:analyze`      | Analyze API endpoints |
| `api-scout:add-endpoint` | Add new API endpoints |
| `api-scout:setup-auth`   | Set up authentication |
| `api-scout:generate`     | Generate components   |

## Usage Examples

### Advanced Authentication Setup

```bash
# Interactive setup
php artisan api-scout:setup-auth users --test

# Command-line setup
php artisan api-scout:setup-auth users \
  --type=token_endpoint \
  --token-endpoint=https://api.example.com/oauth/token \
  --client-id=your-client-id \
  --client-secret=your-client-secret \
  --grant-type=client_credentials \
  --test
```

## 🧪 Testing

Run the test suite:

```bash
composer test
```

For detailed testing instructions, see [TESTING.md](TESTING.md).

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## Support

- **Documentation**: [AUTHENTICATION.md](AUTHENTICATION.md) for detailed auth setup
- **Issues**: [GitHub Issues](https://github.com/jcnghm/api-scout/issues)
- **Discussions**: [GitHub Discussions](https://github.com/jcnghm/api-scout/discussions)
