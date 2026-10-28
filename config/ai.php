<?php

return [
    'enabled' => env('AI_ENABLED', false),
    'default' => env('AI_PROVIDER', 'openai'),

    'sources' => [
        'timeout' => 15,
        'max_bytes' => 1048576,
        'max_text_length' => 24000,
        'max_urls' => 6,
    ],
    'tasks' => [
        'connection' => env('AI_QUEUE_CONNECTION', 'ai'),
        'queue' => env('AI_QUEUE', 'ai'),
        'cache_store' => env('AI_CACHE_STORE'),
        'timeout' => (int) env('AI_TASK_TIMEOUT', 240),
        'ttl' => (int) env('AI_TASK_TTL', 3600),
        'batch_limit' => 20,
    ],

    'providers' => [
        'openai' => [
            'api_key' => env('OPENAI_API_KEY', ''),
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'text_model' => env('OPENAI_TEXT_MODEL', 'gpt-4.1'),
            'image_model' => env('OPENAI_IMAGE_MODEL', 'gpt-image-1'),
            'moderation_model' => env('OPENAI_MODERATION_MODEL', 'omni-moderation-latest'),
            'timeout' => (int) env('AI_HTTP_TIMEOUT', 90),
            'connect_timeout' => (int) env('AI_CONNECT_TIMEOUT', 10),
            'image_timeout' => (int) env('AI_IMAGE_TIMEOUT', 180),
            'max_output_tokens' => (int) env('AI_MAX_OUTPUT_TOKENS', 16384),
            'max_input_bytes' => 262144,
            'max_image_input_bytes' => 10485760,
            'max_response_bytes' => 33554432,
            'max_images' => 8,
            'max_generated_images' => 4,
        ],
    ],
];
