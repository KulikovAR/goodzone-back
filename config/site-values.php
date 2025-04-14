<?php

return [
    'email_support' => ['email_support' => env('MAIL_FROM_ADDRESS')],

    'prices_ru' => [
        'prices' => [
            'price' => 999,
            'price_vip' => 1499,
            'price_hotel' => 999,
            'value' => '₽',
        ],
    ],
    'prices_us' => [
        'prices' => [
            'price' => 10,
            'price_vip' => 20,
            'price_hotel' => 10,
            'value' => '$',
        ],
    ],
];
