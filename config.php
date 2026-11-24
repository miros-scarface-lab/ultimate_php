<?php
/*
    Database settings.
    Fill these in with NEW credentials (the old ones were exposed in db.php).
    On the live server you can instead set the environment variables
    DB_HOST, DB_USER, DB_PASS, DB_NAME and leave this file as it is.
*/

return [
    'local' => [
        'host' => 'localhost',
        'user' => 'root',
        'pass' => 'CHANGE_ME',
        'name' => 'campus_lost_found',
    ],
    'live' => [
        'host' => 'CHANGE_ME_HOST',
        'user' => 'CHANGE_ME_USER',
        'pass' => 'CHANGE_ME_PASSWORD',
        'name' => 'CHANGE_ME_DATABASE',
    ],
];
