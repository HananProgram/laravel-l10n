<?php

return [
    'enabled_locales'   => ['en','ar'],
    'admin_route_prefix'=> 'superadmin',
    'admin_middleware'  => ['web','auth','App\Http\Middleware\superadminMiddleware'],
    'auto_translate'    => env('L10N_AUTO_TRANSLATE', true),
    'default_group'     => 'ui',
    'sync_source_en'    => env('L10N_SYNC_SOURCE_EN', false),
];
