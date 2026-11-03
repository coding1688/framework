<?php

return [
    'cache_store' => env('FIELD_OPTION_CACHE_STORE'),
    'cache_ttl' => (int) env('FIELD_OPTION_CACHE_TTL', 300),
];
