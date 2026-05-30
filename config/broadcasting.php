<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Broadcaster
    |--------------------------------------------------------------------------
    |
    | This option controls the default broadcaster that will be used by the
    | framework when an event needs to be broadcast. You may set this to
    | any of the connections defined in the "connections" array below.
    |
    */
    'default' => env('BROADCAST_DRIVER', 'null'),

    /*
    |--------------------------------------------------------------------------
    | Broadcast Connections
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the broadcast connections that will be used
    | to broadcast events to other systems or over WebSocket connections.
    | This application supports Pusher and Reverb-compatible broadcasting.
    |
    */
    'connections' => [
        'pusher' => [
            'driver' => 'pusher',
            'key' => env('PUSHER_APP_KEY'),
            'secret' => env('PUSHER_APP_SECRET'),
            'app_id' => env('PUSHER_APP_ID'),
            'options' => [
                'cluster' => env('PUSHER_APP_CLUSTER'),
                'useTLS' => env('PUSHER_APP_SCHEME', 'https') === 'https',
                'encrypted' => true,
                'host' => env('PUSHER_HOST', 'api-eu.pusher.com'),
                'port' => env('PUSHER_PORT', 443),
                'scheme' => env('PUSHER_APP_SCHEME', 'https'),
            ],
            'client_options' => [],
        ],

        'reverb' => [
            'driver' => 'reverb',
            'host' => env('REVERB_HOST', '127.0.0.1'),
            'port' => env('REVERB_PORT', 6001),
            'scheme' => env('REVERB_SCHEME', 'https'),
            'key' => env('REVERB_APP_KEY'),
            'secret' => env('REVERB_APP_SECRET'),
            'app_id' => env('REVERB_APP_ID'),
            'options' => [
                'cluster' => env('REVERB_APP_CLUSTER', 'mt1'),
                'useTLS' => env('REVERB_SCHEME', 'https') === 'https',
                'encrypted' => true,
            ],
        ],

        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Broadcast Authorization
    |--------------------------------------------------------------------------
    |
    | This list is used by channel authorization callbacks for admin-only
    | broadcast channels such as the dead-letter queue access channel.
    |
    */
    'admin_emails' => array_filter(array_map('trim', explode(',', env('BROADCAST_ADMIN_EMAILS', '')))),
];
