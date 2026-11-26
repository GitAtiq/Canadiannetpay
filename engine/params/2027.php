<?php
/**
 * Tax year 2027 — PROVISIONAL.
 * Source: CRA T4127 January 2027 edition, Tables 8.1 / 8.2 (published Nov 2026).
 * Base CPP rate falls from 4.95% to 4.75% (total 5.75%).
 * Federal BPA from T4127 Chapter 2: 16,879 max / 15,214 min.
 * Re-check against the final edition before switching the default year.
 */
$p = require __DIR__ . '/2026.php';

$p['year'] = 2027;
$p['status'] = 'provisional';

$p['cpp'] = [
    'ympe' => 76700, 'yampe' => 87400, 'exemption' => 3500,
    'rate' => 0.0575, 'base_rate' => 0.0475,
    'cpp2_rate' => 0.04,
    'max' => 4209.00, 'cpp2_max' => 428.00,
];
$p['ei'] = ['mie' => 70800, 'rate' => 0.0164, 'max' => 1161.12, 'employer_multiplier' => 1.4];

$p['federal'] = [
    'brackets' => [[0, 0.14], [60044, 0.205], [120088, 0.26], [186157, 0.29], [265203, 0.33]],
    'lowest_rate' => 0.14,
    'bpa_max' => 16879, 'bpa_min' => 15214, 'bpa_phase_start' => 186157, 'bpa_phase_end' => 265203,
    'cea' => 1540,
];

$p['provinces']['ON'] = array_merge($p['provinces']['ON'], [
    'brackets' => [[0, 0.0505], [55023, 0.0915], [110049, 0.1116], [150000, 0.1216], [220000, 0.1316]],
    'bpa' => 13261,
    'surtax' => [[5940, 0.20], [7603, 0.36]],
    'reduction_base' => 306,
]);

// Other provinces: 2027 brackets not loaded yet (disabled anyway).
return $p;
