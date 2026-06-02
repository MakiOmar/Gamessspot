<?php

return array(
    /*
    |--------------------------------------------------------------------------
    | Gate abilities (permissions) registry
    |--------------------------------------------------------------------------
    |
    | Keys match Laravel Gate names used in routes, middleware, and @can directives.
    |
    */
    'abilities' => array(
        'access-dashboard' => array(
            'label' => 'Access Dashboard',
            'group' => 'Dashboard',
        ),
        'manage-games' => array(
            'label' => 'Manage Games',
            'group' => 'Catalog',
        ),
        'edit-games' => array(
            'label' => 'Edit Games',
            'group' => 'Catalog',
        ),
        'manage-gift-cards' => array(
            'label' => 'Manage Gift Cards',
            'group' => 'Catalog',
        ),
        'manage-categories' => array(
            'label' => 'Manage Categories',
            'group' => 'Catalog',
        ),
        'view-sell-log' => array(
            'label' => 'View Sell Log',
            'group' => 'Orders / Sell Log',
        ),
        'manage-sell-log' => array(
            'label' => 'Manage Sell Log',
            'group' => 'Orders / Sell Log',
        ),
        'undo-orders' => array(
            'label' => 'Undo Orders',
            'group' => 'Orders / Sell Log',
        ),
        'create-order-reports' => array(
            'label' => 'Create Order Reports',
            'group' => 'Orders / Sell Log',
        ),
        'search-customer-orders' => array(
            'label' => 'Search Customer Orders (Navbar & Sell Log)',
            'group' => 'Orders / Sell Log',
        ),
        'view-game-accounts' => array(
            'label' => 'View Game Accounts',
            'group' => 'Accounts & Stores',
        ),
        'manage-accounts' => array(
            'label' => 'Manage Game Accounts',
            'group' => 'Accounts & Stores',
        ),
        'manage-store-profiles' => array(
            'label' => 'Manage Store Profiles',
            'group' => 'Accounts & Stores',
        ),
        'view-reports' => array(
            'label' => 'View Reports',
            'group' => 'Reports',
        ),
        'manage-users' => array(
            'label' => 'Manage Users',
            'group' => 'Users & System',
        ),
        'manage-options' => array(
            'label' => 'Manage Options / Settings',
            'group' => 'Users & System',
        ),
        'manage-device-repairs' => array(
            'label' => 'Manage Device Repairs',
            'group' => 'Device Services',
        ),
        'delete-device-repairs' => array(
            'label' => 'Delete Device Repairs',
            'group' => 'Device Services',
        ),
        'submit-device-request' => array(
            'label' => 'Submit Device Request',
            'group' => 'Device Services',
        ),
        'track-device-status' => array(
            'label' => 'Track Device Status',
            'group' => 'Device Services',
        ),
    ),

    /*
    | Minimum capabilities required for the admin role (cannot be removed via UI).
    */
    'admin_required' => array(
        'access-dashboard',
        'manage-options',
        'manage-users',
    ),

    /*
    | Role names excluded from the Roles & Permissions management UI.
    */
    'excluded_roles' => array(
        'customer',
    ),
);
