<?php
/**
 * Site configuration. Only real data goes here — the author/reviewer box and
 * schema read from this file and render nothing for empty fields.
 */
return [
    'brand'        => 'TBD',             // pick after domain check
    'domain'       => 'example.ca',      // canonical host, no www
    'default_year' => 2026,
    'author' => [
        'name'       => '',              // founder name
        'credential' => '',              // e.g. "CPA" — confirm provincial body rules first
        'title'      => '',
        'url'        => '',
    ],
    'reviewer' => null,
    'analytics_id' => '',                // GA4, optional
    'adsense_client' => '',              // optional
    'rate_limit' => ['requests' => 60, 'window' => 60], // per IP per window (s)
    // Amount pages: release schedule (3 per weekday), filled in a later PR.
    'release_schedule' => [],
];
