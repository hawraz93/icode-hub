<?php

return [
    // Old plaintext portal codes (e.g. CL-AB12CD) keep working until the admin issues a new code.
    // Set PORTAL_ALLOW_LEGACY_CODES=false once every client has a new code.
    'allow_legacy_codes' => env('PORTAL_ALLOW_LEGACY_CODES', true),

    // Failed login attempts allowed per IP per 15 minutes.
    'max_attempts' => (int) env('PORTAL_MAX_ATTEMPTS', 5),
];
