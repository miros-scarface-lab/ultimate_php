<?php
/*
    Database configuration
    Defaults to local SQLite for instant, zero-setup offline execution.
*/

return [
    'driver' => 'sqlite', // 'sqlite', 'supabase', or 'mysql'
    
    'sqlite' => [
        'path' => __DIR__ . '/campus_lost_found.db',
    ],

    'supabase' => [
        'host' => 'aws-1-ap-south-1.pooler.supabase.com',
        'port' => 6543,
        'user' => 'postgres.bmwtijjrsskkbabueibd',
        'pass' => 'shreyasabiha@gmail.com',
        'name' => 'postgres',
    ],

    'local' => [
        'host' => 'localhost',
        'user' => 'root',
        'pass' => '',
        'name' => 'campus_lost_found',
    ],
];
