<?php

return [
    // Priority tiers configuration. Adjust thresholds as needed.
    'tiers' => [
        [
            'level' => 1,
            'min_count' => 20,
            'min_total' => 700000, // Rp 700.000
            'discount' => 5,
        ],
        [
            'level' => 2,
            'min_count' => 50,
            'min_total' => 2000000, // Rp 2.000.000
            'discount' => 15,
        ],
        [
            'level' => 3,
            'min_count' => 100,
            'min_total' => 5000000, // Rp 5.000.000
            'discount' => 25,
        ],
    ],

    // Fallback discount mapping by level
    'discounts' => [
        0 => 0,
        1 => 5,
        2 => 15,
        3 => 25,
    ],
];
