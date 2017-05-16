<?php

return [
    'event_log' => true,
    'hearbeat_log' => false,
    'push_stream' => true,
    'db' => [
        'driver' => 'mysql',
        'host' => 'localhost',
        'schema' => 'sismolink',
        'user' => 'root',
        'pass' => ''
    ],
    'server' => [
        'ip' => '192.168.40.125',
        'event_port' => 5678,
        'heartbeat_port' => 5679,
    ],
    'stream' => [
        'ip' => 'localhost',
        'port' => 5780
    ],
    'push_port' => 8081,
    'webservice_port' => 9000,
];
