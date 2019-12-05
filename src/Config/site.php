<?php

return [
    'version' => env('APP_VERSION'),
    'demo'    => env('IS_DEMO_SITE'),
    'disable_update_auth' => env('DISABLE_UPDATE_AUTH'),
    'use_symlinks' => env('USE_SYMLINKS', false),
];