<?php

return [
    'teams' => [
        'max_members' => env('DOTFORMS_TEAM_MAX_MEMBERS', 10),
        'owner_role' => env('DOTFORMS_TEAM_OWNER_ROLE', 'owner'),
    ],

    'forms' => [
        'upload_disk' => env('DOTFORMS_UPLOAD_DISK', env('FILESYSTEM_DISK', 'public')),
        'min_submit_seconds' => env('DOTFORMS_MIN_SUBMIT_SECONDS', 2),
    ],

    'queues' => [
        'notifications' => env('DOTFORMS_QUEUE_NOTIFICATIONS', 'notifications'),
        'ai' => env('DOTFORMS_QUEUE_AI', 'ai'),
    ],
];