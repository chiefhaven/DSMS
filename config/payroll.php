<?php

return [
    /*
    |--------------------------------------------------------------------------
    | PAYE Configuration
    |--------------------------------------------------------------------------
    |
    | Used when a staff member has PAYE enabled. "rate" applies a flat
    | percentage to taxable gross (gross salary + allowances). "bands" can be
    | set to progressive annual/monthly-equivalent bands if needed, e.g.:
    | [
    |   ['up_to' => 100000, 'rate' => 0],
    |   ['up_to' => 300000, 'rate' => 0.25],
    |   ['up_to' => null,   'rate' => 0.30],
    | ]
    | When "bands" is non-empty it takes precedence over "rate".
    |
    */
    'paye' => [
        'enabled' => true,
        'rate' => 0.0,
        'bands' => [],
    ],
];
