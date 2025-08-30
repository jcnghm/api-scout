<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default HTTP Client Settings
    |--------------------------------------------------------------------------
    */
    'timeout' => 30,
    'connect_timeout' => 10,
    
    /*
    |--------------------------------------------------------------------------
    | API Endpoints Configuration
    |--------------------------------------------------------------------------
    */
    'endpoints' => [
        // Example configuration for simple bearer token
        // 'users' => [
        //     'url' => 'https://api.example.com/users',
        //     'method' => 'GET',
        //     'headers' => [
        //         'Accept' => 'application/json',
        //         'Content-Type' => 'application/json',
        //     ],
        //     'auth' => [
        //         'type' => 'bearer',
        //         'token' => env('API_TOKEN'),
        //     ]
        // ],

        // Example configuration for token endpoint authentication
        // 'protected_users' => [
        //     'url' => 'https://api.example.com/users',
        //     'method' => 'GET',
        //     'headers' => [
        //         'Accept' => 'application/json',
        //     ],
        //     'auth' => [
        //         'type' => 'token_endpoint',
        //         'token_endpoint' => 'https://api.example.com/oauth/token',
        //         'method' => 'POST',
        //         'auth_type' => 'form', // form, json, or query
        //         'credentials' => [
        //             'grant_type' => 'client_credentials',
        //             'client_id' => env('API_CLIENT_ID'),
        //             'client_secret' => env('API_CLIENT_SECRET'),
        //         ],
        //         'token_path' => 'access_token', // Path to token in response
        //         'expires_in_path' => 'expires_in', // Path to expires_in in response
        //         'token_type_path' => 'token_type', // Path to token_type in response
        //         'token_key' => 'example_api', // Optional: unique key for caching
        //     ]
        // ],

        // Example configuration for OAuth2 password grant
        // 'oauth_users' => [
        //     'url' => 'https://api.example.com/users',
        //     'method' => 'GET',
        //     'auth' => [
        //         'type' => 'token_endpoint',
        //         'token_endpoint' => 'https://api.example.com/oauth/token',
        //         'method' => 'POST',
        //         'auth_type' => 'form',
        //         'credentials' => [
        //             'grant_type' => 'password',
        //             'username' => env('API_USERNAME'),
        //             'password' => env('API_PASSWORD'),
        //             'client_id' => env('API_CLIENT_ID'),
        //             'client_secret' => env('API_CLIENT_SECRET'),
        //         ],
        //         'token_path' => 'access_token',
        //         'expires_in_path' => 'expires_in',
        //     ]
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Component Generation Settings
    |--------------------------------------------------------------------------
    */
    'components' => [
        'generate_livewire' => true,
        'generate_blade' => true,
        'output_path' => 'app/Http/Livewire/ApiScout',
        'view_path' => 'resources/views/api-scout',
        'namespace' => 'App\\Http\\Livewire\\ApiScout',
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Type Detection
    |--------------------------------------------------------------------------
    */
    'type_detection' => [
        'sample_size' => 5,
        'strict_types' => false,
    ]
];