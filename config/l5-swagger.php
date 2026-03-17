<?php

return [

    'default' => 'default',

    'documentations' => [
        'default' => [
            'api' => [
                'title' => 'Mini ERP API Docs',
            ],

            'routes' => [
                'api' => 'api/documentation',
            ],

            'paths' => [
                'use_absolute_path' => env('L5_SWAGGER_USE_ABSOLUTE_PATH', true),
                'swagger_ui_assets_path' => 'vendor/swagger-api/swagger-ui/dist/',

                // 🔥 IMPORTANT
                'docs_json' => 'openapi.json',
                'docs_yaml' => 'openapi.yaml',
                'format_to_use_for_docs' => 'yaml',

                // ❌ NO ANNOTATIONS
                'annotations' => [],
            ],
        ],
    ],

    'defaults' => [

        'routes' => [
            'docs' => 'docs',
            'oauth2_callback' => 'api/oauth2-callback',
            'middleware' => [
                'api' => [],
                'asset' => [],
                'docs' => [],
                'oauth2_callback' => [],
            ],
            'group_options' => [],
        ],

        'paths' => [
            'docs' => storage_path('api-docs'),

            'views' => base_path('resources/views/vendor/l5-swagger'),
            'base' => null,
            'excludes' => [],
        ],

        // ❌ disable scanning completely
        'scanOptions' => [
            'analyser' => null,
            'analysis' => null,
            'processors' => [],
            'pattern' => null,
            'exclude' => ['*'], // 🔥 KLJUČNO — prepreči scan
        ],

        'securityDefinitions' => [
            'securitySchemes' => [],
            'security' => [],
        ],

        // 🔥 MUST BE TRUE (dev mode)
        'generate_always' => true,

        'generate_yaml_copy' => false,
        'proxy' => false,
        'additional_config_url' => null,
        'operations_sort' => null,
        'validator_url' => null,

        'ui' => [
            'display' => [
                'dark_mode' => false,
                'doc_expansion' => 'none',
                'filter' => true,
            ],
            'authorization' => [
                'persist_authorization' => true,
                'oauth2' => [
                    'use_pkce_with_authorization_code_grant' => false,
                ],
            ],
        ],

        'constants' => [
            'L5_SWAGGER_CONST_HOST' => 'http://localhost',
        ],
    ],
];
