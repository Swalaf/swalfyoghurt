<?php

return [
    // Current platform version shown in the installer and License & updates.
    'version' => '1.3.0',

    // When true, the installer is skipped. Normally the installer writes
    // storage/app/installed.json instead; this flag exists for tests/CI.
    'installed' => env('STUDIO_INSTALLED', false),

    // Where published apps are served from: {subdomain}.{publish_domain}.
    // When empty, published apps are served from /p/{subdomain} on this host.
    'publish_domain' => env('STUDIO_PUBLISH_DOMAIN', ''),

    // Licensing. Buyers' installs activate against the vendor's licence server at
    // license_url. Set license_server=true only on the vendor's own installation.
    'license_url' => rtrim((string) env('STUDIO_LICENSE_URL', ''), '/'),
    'license_server' => (bool) env('STUDIO_LICENSE_SERVER', false),

    // Storage disk for published app files ("published" = local, or set to s3).
    'publish_disk' => env('STUDIO_PUBLISH_DISK', 'published'),

    // Languages the interface ships in.
    'locales' => ['en' => 'English', 'es' => 'Español', 'fr' => 'Français', 'pt' => 'Português'],

    // Credits charged per 1,000 AI tokens (input + output), minimum 1 per call.
    'credits_per_1k_tokens' => 1,
];
