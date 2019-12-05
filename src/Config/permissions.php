<?php

return [
    'groups' => [
        'users' => [
            'users.view'  => 1,
            'localizations.show' => 1,
            'pages.view' => 1,
            'uploads.create' => 1,
        ],

        'guests' => [
            'users.view'  => 1,
            'pages.view' => 1,
        ],
    ],
    'all' => [
        //ADMIN
        'admin' => [
            [
                'name' => 'admin.access',
                'description' => 'Required in order to access any admin area page.',
            ],
            [
                'name' => 'permissions.view',
                'description' => 'Allows viewing of permissions list.'
            ],
            [
                'name' => 'appearance.update',
                'description' => 'Allows access to appearance editor.'
            ]
        ],

        //USER GROUPS
        'groups' => [
            'groups.view',
            'groups.create',
            'groups.update',
            'groups.delete',
        ],

        //REPORTS
        'analytics' => [
            'reports.view'
        ],

        //PAGES
        'pages' => [
            'pages.view',
            'pages.create',
            'pages.update',
            'pages.delete',
        ],

        //UPLOADS
        'uploads' => [
            'uploads.view',
            'uploads.create',
            'uploads.delete',
        ],

        //USERS
        'users' => [
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
        ],

        //LOCALIZATIONS
        'localizations' => [
            'localizations.view',
            'localizations.create',
            'localizations.update',
            'localizations.delete',
        ],

        //MAIL TEMPLATES
        'mail_templates' => [
            'mail_templates.view',
            'mail_templates.update',
        ],

        //SETTINGS
        'settings' => [
            'settings.view',
            'settings.update',
        ],
    ]
];
