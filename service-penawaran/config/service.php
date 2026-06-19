<?php

return [

    'rabbitmq' => [
        'exchange' => env('RABBITMQ_EXCHANGE', 'iae.central.exchange'),
        'http_url' => env('RABBITMQ_HTTP_URL'),
    ],

];