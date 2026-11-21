<?php

/**
 * Machine / POS integration API (Sanctum phone+password login).
 * Only allowlisted users may mint tokens; abilities are scoped (not "*").
 */
return [

    /*
    | Comma-separated user ids allowed to POST /api/login.
    | Set to the POS integration account (same as ACCOUNTS_AUTH_PHONE user).
    */
    'login_user_ids' => array_values(array_filter(array_map(
        'intval',
        explode(',', (string) env('API_LOGIN_USER_IDS', ''))
    ))),

    /*
    | Optional phone allowlist (any stored format match via exact phone column).
    | Useful when the integration user id is not known yet.
    */
    'login_phones' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('API_LOGIN_PHONES', ''))
    ))),

    /*
    | Token abilities issued on successful /api/login.
    */
    'token_abilities' => [
        'orders:receive',
        'pos:receive-order',
        'customers:write',
        'orders:check-stock',
    ],

    /*
    | Token lifetime in days.
    */
    'token_ttl_days' => (int) env('API_TOKEN_TTL_DAYS', 8),
];
