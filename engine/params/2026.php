<?php
/**
 * Tax year 2026 — FINAL.
 * Source: CRA T4127 Payroll Deductions Formulas, Tables 8.22 / 8.23 (2026 rates),
 * as republished in the January 2027 edition. Ontario Health Premium: Ontario.ca.
 * Every figure used on the site is read from here. Do not hard-code figures elsewhere.
 */
return [
    'year'   => 2026,
    'status' => 'final',

    'cpp' => [
        'ympe' => 74600, 'yampe' => 85000, 'exemption' => 3500,
        'rate' => 0.0595, 'base_rate' => 0.0495,  // base portion → credit; rest → deduction
        'cpp2_rate' => 0.04,
        'max' => 4230.45, 'cpp2_max' => 416.00,
    ],
    'ei' => [
        'mie' => 68900, 'rate' => 0.0163, 'max' => 1123.07, 'employer_multiplier' => 1.4,
    ],

    'federal' => [
        'brackets' => [[0, 0.14], [58523, 0.205], [117045, 0.26], [181440, 0.29], [258482, 0.33]],
        'lowest_rate' => 0.14,
        'bpa_max' => 16452, 'bpa_min' => 14829, 'bpa_phase_start' => 181440, 'bpa_phase_end' => 258482,
        'cea' => 1501,
    ],

    'provinces' => [
        'ON' => [
            'name' => 'Ontario', 'enabled' => true,
            'brackets' => [[0, 0.0505], [53891, 0.0915], [107785, 0.1116], [150000, 0.1216], [220000, 0.1316]],
            'bpa' => 12989,
            'surtax' => [[5818, 0.20], [7446, 0.36]],
            'reduction_base' => 300,
            // [lower, upper, base, rate, cap] on taxable income; not indexed
            'health_premium' => [
                [20000, 25000, 0, 0.06, 300], [25000, 36000, 300, 0, 300],
                [36000, 38500, 300, 0.06, 450], [38500, 48000, 450, 0, 450],
                [48000, 48600, 450, 0.25, 600], [48600, 72000, 600, 0, 600],
                [72000, 72600, 600, 0.25, 750], [72600, 200000, 750, 0, 750],
                [200000, 200600, 750, 0.25, 900], [200600, PHP_INT_MAX, 900, 0, 900],
            ],
        ],
        // Brackets/BPA stored from T4127 Table 8.22; province-specific credits and
        // reductions not yet implemented → disabled until tested.
        'BC' => ['name' => 'British Columbia', 'enabled' => false, 'bpa' => 13216,
            'brackets' => [[0, 0.056], [50363, 0.077], [100728, 0.105], [115648, 0.1229], [140430, 0.147], [190405, 0.168], [265545, 0.205]]],
        'AB' => ['name' => 'Alberta', 'enabled' => false, 'bpa' => 22769,
            'brackets' => [[0, 0.08], [61200, 0.10], [154259, 0.12], [185111, 0.13], [246813, 0.14], [370220, 0.15]]],
        'SK' => ['name' => 'Saskatchewan', 'enabled' => false, 'bpa' => 20381,
            'brackets' => [[0, 0.105], [54532, 0.125], [155805, 0.145]]],
        'MB' => ['name' => 'Manitoba', 'enabled' => false, 'bpa' => 15780,
            'brackets' => [[0, 0.108], [47000, 0.1275], [100000, 0.174]]],
        'NB' => ['name' => 'New Brunswick', 'enabled' => false, 'bpa' => 13664,
            'brackets' => [[0, 0.094], [52333, 0.14], [104666, 0.16], [193861, 0.195]]],
        'NS' => ['name' => 'Nova Scotia', 'enabled' => false, 'bpa' => 11932,
            'brackets' => [[0, 0.0879], [30995, 0.1495], [61991, 0.1667], [97417, 0.175], [157124, 0.21]]],
        'PE' => ['name' => 'Prince Edward Island', 'enabled' => false, 'bpa' => 15000,
            'brackets' => [[0, 0.095], [33928, 0.1347], [65820, 0.166], [106890, 0.1762], [142520, 0.19], [200000, 0.20]]],
        'NL' => ['name' => 'Newfoundland and Labrador', 'enabled' => false, 'bpa' => 13094,
            'brackets' => [[0, 0.087], [44678, 0.145], [89354, 0.158], [159528, 0.178], [223340, 0.198], [285319, 0.208], [570638, 0.213], [1141275, 0.218]]],
        'YT' => ['name' => 'Yukon', 'enabled' => false, 'bpa' => 'federal',
            'brackets' => [[0, 0.064], [58523, 0.09], [117045, 0.109], [181440, 0.128], [500000, 0.15]]],
        'NT' => ['name' => 'Northwest Territories', 'enabled' => false, 'bpa' => 18198,
            'brackets' => [[0, 0.059], [53003, 0.086], [106009, 0.122], [172346, 0.1405]]],
        'NU' => ['name' => 'Nunavut', 'enabled' => false, 'bpa' => 19659,
            'brackets' => [[0, 0.04], [55801, 0.07], [111602, 0.09], [181439, 0.115]]],
        'QC' => ['name' => 'Quebec', 'enabled' => false], // separate system: QPP, QPIP, abatement 16.5%
    ],
];
