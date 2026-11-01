<?php

return [
    'restrictions' => [
        'login' => [
            'capabilities' => ['authentication'],
            'revoke_sessions' => true,
        ],
        'comment' => [
            'capabilities' => ['comment'],
            'revoke_sessions' => false,
        ],
    ],
    'wallet' => [
        'main_currency' => 'RMB',
        'currencies' => [
            'RMB' => ['precision' => 2],
            'USDT' => ['precision' => 6],
        ],
    ],
];
