# API Scout Authentication

API Scout supports various authentication methods for APIs that require authentication. This includes both simple authentication methods and complex token-based authentication flows.

## Quick Setup

### Interactive Setup

Use the interactive command to set up authentication for your endpoints:

```bash
php artisan api-scout:setup-auth your-endpoint-key --test
```

### Command-Line Setup

You can also pass authentication information directly via command options:

```bash
# Set up token endpoint authentication
php artisan api-scout:setup-auth users \
  --type=token_endpoint \
  --token-endpoint=https://api.example.com/oauth/token \
  --client-id=your-client-id \
  --client-secret=your-client-secret \
  --grant-type=client_credentials \
  --test

# Set up bearer token authentication
php artisan api-scout:setup-auth users \
  --type=bearer \
  --password=your-bearer-token \
  --test

# Set up basic authentication
php artisan api-scout:setup-auth users \
  --type=basic \
  --username=your-username \
  --password=your-password \
  --test

# Set up API key authentication
php artisan api-scout:setup-auth users \
  --type=api_key \
  --api-key=your-api-key \
  --header=X-API-Key \
  --test
```

### Adding Endpoints with Authentication

You can add new endpoints directly with authentication:

```bash
# Add endpoint with OAuth2 authentication
php artisan api-scout:add-endpoint users https://api.example.com/users \
  --type=token_endpoint \
  --token-endpoint=https://api.example.com/oauth/token \
  --client-id=your-client-id \
  --client-secret=your-client-secret \
  --grant-type=client_credentials \
  --test

# Add endpoint with bearer token
php artisan api-scout:add-endpoint users https://api.example.com/users \
  --type=bearer \
  --password=your-bearer-token \
  --test

# Add endpoint with basic auth
php artisan api-scout:add-endpoint users https://api.example.com/users \
  --type=basic \
  --username=your-username \
  --password=your-password \
  --test
```

## Authentication Types

### 1. Token Endpoint Authentication

For APIs that require getting a token from an authentication endpoint first (OAuth2, JWT, etc.).

#### Configuration Example

```php
'endpoints' => [
    'users' => [
        'url' => 'https://api.example.com/users',
        'method' => 'GET',
        'auth' => [
            'type' => 'token_endpoint',
            'token_endpoint' => 'https://api.example.com/oauth/token',
            'method' => 'POST',
            'auth_type' => 'form', // form, json, or query
            'credentials' => [
                'grant_type' => 'client_credentials',
                'client_id' => env('API_CLIENT_ID'),
                'client_secret' => env('API_CLIENT_SECRET'),
            ],
            'token_path' => 'access_token', // Path to token in response
            'expires_in_path' => 'expires_in', // Path to expires_in in response
            'token_type_path' => 'token_type', // Path to token_type in response
            'token_key' => 'example_api', // Optional: unique key for caching
        ]
    ]
]
```

#### OAuth2 Password Grant Example

```php
'auth' => [
    'type' => 'token_endpoint',
    'token_endpoint' => 'https://api.example.com/oauth/token',
    'method' => 'POST',
    'auth_type' => 'form',
    'credentials' => [
        'grant_type' => 'password',
        'username' => env('API_USERNAME'),
        'password' => env('API_PASSWORD'),
        'client_id' => env('API_CLIENT_ID'),
        'client_secret' => env('API_CLIENT_SECRET'),
    ],
    'token_path' => 'access_token',
    'expires_in_path' => 'expires_in',
]
```

### 2. Bearer Token Authentication

For APIs that use a static bearer token.

#### Configuration Example

```php
'auth' => [
    'type' => 'bearer',
    'token' => env('API_TOKEN'),
]
```

### 3. Basic Authentication

For APIs that use username/password authentication.

#### Configuration Example

```php
'auth' => [
    'type' => 'basic',
    'username' => env('API_USERNAME'),
    'password' => env('API_PASSWORD'),
]
```

### 4. API Key Authentication

For APIs that use an API key in headers.

#### Configuration Example

```php
'auth' => [
    'type' => 'api_key',
    'key' => env('API_KEY'),
    'header' => 'X-API-Key',
]
```

## Command-Line Options Reference

### SetupAuthCommand Options

| Option              | Description                     | Example                                                |
| ------------------- | ------------------------------- | ------------------------------------------------------ |
| `--type`            | Authentication type             | `--type=token_endpoint`                                |
| `--token-endpoint`  | Token endpoint URL              | `--token-endpoint=https://api.example.com/oauth/token` |
| `--client-id`       | OAuth2 client ID                | `--client-id=your-client-id`                           |
| `--client-secret`   | OAuth2 client secret            | `--client-secret=your-client-secret`                   |
| `--username`        | Username for basic auth         | `--username=your-username`                             |
| `--password`        | Password or bearer token        | `--password=your-password`                             |
| `--api-key`         | API key                         | `--api-key=your-api-key`                               |
| `--header`          | Header name for API key         | `--header=X-API-Key`                                   |
| `--grant-type`      | OAuth2 grant type               | `--grant-type=client_credentials`                      |
| `--method`          | HTTP method for token endpoint  | `--method=POST`                                        |
| `--auth-type`       | Credential format               | `--auth-type=form`                                     |
| `--token-path`      | Path to token in response       | `--token-path=access_token`                            |
| `--expires-in-path` | Path to expires_in in response  | `--expires-in-path=expires_in`                         |
| `--token-type-path` | Path to token_type in response  | `--token-type-path=token_type`                         |
| `--token-key`       | Unique key for token caching    | `--token-key=example_api`                              |
| `--test`            | Test authentication after setup | `--test`                                               |
| `--interactive`     | Force interactive mode          | `--interactive`                                        |

### AddEndpointCommand Options

| Option              | Description                    | Example                                                |
| ------------------- | ------------------------------ | ------------------------------------------------------ |
| `--method`          | HTTP method for endpoint       | `--method=GET`                                         |
| `--type`            | Authentication type            | `--type=token_endpoint`                                |
| `--token-endpoint`  | Token endpoint URL             | `--token-endpoint=https://api.example.com/oauth/token` |
| `--client-id`       | OAuth2 client ID               | `--client-id=your-client-id`                           |
| `--client-secret`   | OAuth2 client secret           | `--client-secret=your-client-secret`                   |
| `--username`        | Username for basic auth        | `--username=your-username`                             |
| `--password`        | Password or bearer token       | `--password=your-password`                             |
| `--api-key`         | API key                        | `--api-key=your-api-key`                               |
| `--header`          | Header name for API key        | `--header=X-API-Key`                                   |
| `--grant-type`      | OAuth2 grant type              | `--grant-type=client_credentials`                      |
| `--auth-method`     | HTTP method for token endpoint | `--auth-method=POST`                                   |
| `--auth-type`       | Credential format              | `--auth-type=form`                                     |
| `--token-path`      | Path to token in response      | `--token-path=access_token`                            |
| `--expires-in-path` | Path to expires_in in response | `--expires-in-path=expires_in`                         |
| `--token-type-path` | Path to token_type in response | `--token-type-path=token_type`                         |
| `--token-key`       | Unique key for token caching   | `--token-key=example_api`                              |
| `--test`            | Test endpoint after adding     | `--test`                                               |

## Token Endpoint Configuration Options

### Required Fields

- `token_endpoint`: The URL of the authentication endpoint
- `credentials`: The credentials to send to the token endpoint

### Optional Fields

- `method`: HTTP method for token request (default: POST)
- `auth_type`: How to send credentials (form, json, query) (default: json)
- `headers`: Additional headers for token request
- `token_path`: Path to token in response (default: access_token)
- `expires_in_path`: Path to expires_in in response (default: expires_in)
- `token_type_path`: Path to token_type in response (default: token_type)
- `token_key`: Unique key for caching tokens (default: default)

### Response Path Configuration

The `token_path`, `expires_in_path`, and `token_type_path` fields use dot notation to access nested values in the response.

For example, if your token endpoint returns:

```json
{
  "data": {
    "access_token": "your-token-here",
    "expires_in": 3600,
    "token_type": "Bearer"
  }
}
```

You would configure:

```php
'token_path' => 'data.access_token',
'expires_in_path' => 'data.expires_in',
'token_type_path' => 'data.token_type',
```

## Token Caching

API Scout automatically caches tokens and refreshes them when they expire. Tokens are cached per `token_key` (or 'default' if not specified).

### Multiple APIs

If you're working with multiple APIs that have different token endpoints, use different `token_key` values:

```php
// API 1
'auth' => [
    'type' => 'token_endpoint',
    'token_endpoint' => 'https://api1.example.com/oauth/token',
    'token_key' => 'api1',
    // ... other config
]

// API 2
'auth' => [
    'type' => 'token_endpoint',
    'token_endpoint' => 'https://api2.example.com/oauth/token',
    'token_key' => 'api2',
    // ... other config
]
```

## Environment Variables

Store sensitive credentials in your `.env` file:

```env
# OAuth2 Client Credentials
API_CLIENT_ID=your-client-id
API_CLIENT_SECRET=your-client-secret

# OAuth2 Password Grant
API_USERNAME=your-username
API_PASSWORD=your-password

# Static Bearer Token
API_TOKEN=your-bearer-token

# API Key
API_KEY=your-api-key
```

## Testing Authentication

Test your authentication setup:

```bash
# Test during setup
php artisan api-scout:setup-auth your-endpoint --test

# Test existing configuration
php artisan api-scout:analyze your-endpoint

# Test new endpoint after adding
php artisan api-scout:add-endpoint users https://api.example.com/users --type=bearer --password=token --test
```

## Troubleshooting

### Common Issues

1. **Token not found in response**: Check the `token_path` configuration
2. **Invalid credentials**: Verify your credentials are correct
3. **Token expired**: Tokens are automatically refreshed, but check your `expires_in_path` configuration
4. **Wrong content type**: Ensure `auth_type` matches what the API expects

### Debug Mode

Enable debug mode to see detailed authentication information:

```php
// In your config/api-scout.php
'debug' => true,
```

This will log authentication requests and responses to help troubleshoot issues.

## Security Best Practices

1. **Never commit credentials**: Always use environment variables for sensitive data
2. **Use HTTPS**: Ensure all API endpoints use HTTPS
3. **Rotate tokens**: Regularly rotate your API credentials
4. **Minimize scope**: Use the minimum required permissions for your API credentials
5. **Monitor usage**: Keep track of API usage and token refresh patterns
