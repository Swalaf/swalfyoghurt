<?php

return [
    // Current platform version shown in the installer and License & updates.
    'version' => '1.2.0',

    // When true, the installer is skipped. Normally the installer writes
    // storage/app/installed.json instead; this flag exists for tests/CI.
    'installed' => env('STUDIO_INSTALLED', false),

    // Where published apps are served from: {subdomain}.{publish_domain}.
    // When empty, published apps are served from /p/{subdomain} on this host.
    'publish_domain' => env('STUDIO_PUBLISH_DOMAIN', ''),

    // Credits charged per 1,000 AI tokens (input + output), minimum 1 per call.
    'credits_per_1k_tokens' => 1,
];
