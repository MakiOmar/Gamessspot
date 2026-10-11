<?php

return [
    /*
     * Comma-separated proxy IPs/CIDRs whose X-Forwarded-* headers are trusted
     * (e.g. Docker networks + Cloudflare ranges behind Traefik). Empty trusts none.
     */
    'proxies' => env('TRUSTED_PROXIES'),
];
