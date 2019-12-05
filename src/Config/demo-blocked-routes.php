<?php

return [
    //admin
    ['method' => 'POST', 'name' => 'settings'],
    ['method' => 'POST', 'name' => 'admin/appearance'],
    ['method' => 'PUT', 'name' => 'mail-templates/{id}'],
    ['method' => 'POST', 'name' => 'cache/clear'],

    //localizations
    ['method' => 'DELETE', 'name' => 'localizations/{id}'],
    ['method' => 'PUT', 'name' => 'localizations/{id}'],
    ['method' => 'POST', 'name' => 'localizations'],

    //pages
    ['method' => 'DELETE', 'name' => 'pages'],

    //billing plans
    ['method' => 'POST', 'name' => 'billing/plans'],
    ['method' => 'POST', 'name' => 'billing/plans/sync'],
    ['method' => 'PUT', 'name' => 'billing/plans/{id}'],
    ['method' => 'DELETE', 'name' => 'billing/plans'],

    //subscriptions
    ['method' => 'POST', 'origin' => 'admin', 'name' => 'billing/subscriptions'],
    ['method' => 'PUT', 'origin' => 'admin', 'name' => 'billing/subscriptions/{id}'],
    ['method' => 'DELETE', 'origin' => 'admin', 'name' => 'billing/subscriptions/{id}'],

    //users
    ['method' => 'POST', 'name' => 'users/{id}/password/change'],
    ['method' => 'DELETE', 'origin' => 'admin', 'name' => 'users/{id}'],
    ['method' => 'PUT', 'origin' => 'admin', 'name' => 'users/{id}'],
    ['method' => 'POST', 'origin' => 'admin', 'name' => 'users/{id}'],
    ['method' => 'POST', 'origin' => 'admin', 'name' => 'users/{id}/groups/attach'],
    ['method' => 'POST', 'origin' => 'admin', 'name' => 'users/{id}/groups/detach'],
    ['method' => 'DELETE', 'name' => 'users/delete-multiple'],

    //groups
    ['method' => 'DELETE', 'name' => 'groups/{id}'],
    ['method' => 'PUT', 'name' => 'groups/{id}'],
    ['method' => 'POST', 'name' => 'groups'],
    ['method' => 'POST', 'name' => 'groups/{id}/add-users'],
    ['method' => 'POST', 'name' => 'groups/{id}/remove-users'],
];
