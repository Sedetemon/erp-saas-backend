<?php

use Laravel\Sanctum\Sanctum;

return [

    /*
    |--------------------------------------------------------------------------
    | Stateful Domains
    |--------------------------------------------------------------------------
    |
    | Requests from the following domains / hosts will receive stateful API
    | authentication cookies. Typically, these should include your local
    | and production domains which access your API via a frontend SPA.
    |
    */

    'stateful' => explode(',', env(
        'SANCTUM_STATEFUL_DOMAINS',
        sprintf(
            '%s%s',
            'localhost,localhost:3000,localhost:8000,127.0.0.1,127.0.0.1:8000,::1',
            Sanctum::currentApplicationUrlWithPort()
        )
    )),

    /*
    |--------------------------------------------------------------------------
    | Sanctum Guards
    |--------------------------------------------------------------------------
    |
    | This array contains the authentication guards that will be checked when
    | Sanctum is attempting to authenticate a request. If none of these guards
    | are able to authenticate the request, Sanctum will use the bearer token.
    |
    */

    'guard' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Expiration Minutes
    |--------------------------------------------------------------------------
    |
    | This value controls the number of minutes until an issued token will
    | be considered expired. This overrides the token's "expires_at" value
    | but does not affect first-party session authentication.
    |
    */

    'expiration' => null,

    /*
    |--------------------------------------------------------------------------
    | Token Prefix
    |--------------------------------------------------------------------------
    |
    | Sanctum can prefix new tokens in order to help identify tokens in secret
    | scanning tools. The prefix should contain a prefix, such as "sk_".
    |
    */

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

    /*
    |--------------------------------------------------------------------------
    | Personal Access Token Model
    |--------------------------------------------------------------------------
    |
    | IMPORTANT POUR NOTRE ARCHITECTURE MULTI-TENANT :
    |
    | Nous utilisons notre propre modèle PersonalAccessToken afin de forcer
    | Sanctum à utiliser la connexion "tenant".
    |
    | Chaque tenant possède donc sa propre table:
    |
    | personal_access_tokens
    |
    | dans sa propre base de données.
    |
    */

    'personal_access_token_model' => App\Models\PersonalAccessToken::class,

    /*
    |--------------------------------------------------------------------------
    | Sanctum Middleware
    |--------------------------------------------------------------------------
    |
    | When authenticating your first-party SPA with Sanctum, you may need to
    | customize some of the middleware Sanctum uses while processing requests.
    |
    */

    'middleware' => [
        'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
        'validate_csrf_token' => Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ],

];
