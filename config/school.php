<?php

return [
    // Admin account created by DatabaseSeeder. Set these in .env, never commit real values.
    'seed_admin' => [
        'email' => env('SEED_ADMIN_EMAIL'),
        'password' => env('SEED_ADMIN_PASSWORD'),
    ],

    // Disk for uploaded exam materials. Empty = FILESYSTEM_DISK (local by default, "s3" in production).
    'materials_disk' => env('MATERIALS_DISK'),

    // Max upload size for exam materials, in kilobytes.
    'materials_max_kb' => 10240,

    'materials_extensions' => ['pdf', 'docx', 'pptx', 'xlsx', 'txt', 'png', 'jpg', 'jpeg'],

    // Seconds between polls for new channel messages (no WebSockets yet).
    'channel_poll_seconds' => (int) env('CHANNEL_POLL_SECONDS', 7),
];
