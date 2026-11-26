<?php
declare(strict_types=1);
/**
 * Engine tests. Run: php tests/run-tests.php
 * Expected values come from an independent reference calculation of the
 * T4127 annualised method (Ontario), plus one external cross-check.
 * TODO before launch: add PDOC screenshots/values per province & frequency.
 */
require __DIR__ . '/../engine/TaxEngine.php';

$pass = 0; $fail = 0;
function check(string $name, $got, $want, float $tol = 0.01): void {
    global $pass, $fail;
    if (abs((float)$got - (float)$want) <= $tol) { $pass++; return; }
    $fail++; echo "FAIL {$name}: got {$got}, want {$want}\n";
}

// [year, gross, rrsp, federal, ontario, OHP, CPP, CPP2, EI, net]
$fixtures = [
    [2026, 0, 0, 0, 0.0, 0, 0.0, 0.0, 0.0, 0.0],
    [2026, 15000, 0, 0, 0.0, 0, 684.25, 0.0, 244.5, 14071.25],
    [2026, 25000, 0, 750.44, 442.75, 287.1, 1279.25, 0.0, 407.5, 21832.96],
    [2026, 35000, 0, 2044.32, 988.1, 300.0, 1874.25, 0.0, 570.5, 29222.83],
    [2026, 45000, 0, 3338.2, 1454.82, 450.0, 2469.25, 0.0, 733.5, 36554.23],
    [2026, 50000, 0, 3985.14, 1688.18, 600.0, 2766.75, 0.0, 815.0, 40144.93],
    [2026, 60000, 0, 5338.3, 2382.2, 600.0, 3361.75, 0.0, 978.0, 47339.75],
    [2026, 75000, 0, 8258.6, 3696.06, 750.0, 4230.45, 16.0, 1123.07, 56925.82],
    [2026, 90000, 0, 11251.6, 5031.96, 750.0, 4230.45, 416.0, 1123.07, 67196.92],
    [2026, 100000, 0, 13301.6, 5972.75, 750.0, 4230.45, 416.0, 1123.07, 74206.13],
    [2026, 125000, 0, 18802.14, 9506.05, 750.0, 4230.45, 416.0, 1123.07, 90172.29],
    [2026, 150000, 0, 25302.14, 13858.45, 750.0, 4230.45, 416.0, 1123.07, 104319.89],
    [2026, 200000, 0, 38876.54, 23325.67, 750.0, 4230.45, 416.0, 1123.07, 131278.27],
    [2026, 250000, 0, 53524.01, 33260.89, 900.0, 4230.45, 416.0, 1123.07, 156545.58],
    [2026, 300000, 0, 69667.99, 43525.69, 900.0, 4230.45, 416.0, 1123.07, 180136.8],
    [2027, 50000, 0, 3932.22, 1678.89, 600.0, 2673.75, 0.0, 820.0, 40295.14],
    [2027, 75000, 0, 8108.87, 3641.31, 750.0, 4111.25, 0.0, 1161.12, 57227.45],
    [2027, 100000, 0, 13131.34, 5884.01, 750.0, 4209.0, 428.0, 1161.12, 74436.53],
    [2027, 200000, 0, 38380.59, 23074.01, 750.0, 4209.0, 428.0, 1161.12, 131997.28],
    [2026, 80000, 10000, 7192.6, 3220.26, 600.0, 4230.45, 216.0, 1123.07, 53417.62],
    [2026, 120000, 20000, 13301.6, 5972.75, 750.0, 4230.45, 416.0, 1123.07, 74206.13],
];

foreach ($fixtures as [$y, $g, $r, $fed, $on, $ohp, $cpp, $cpp2, $ei, $net]) {
    $a = (new TaxEngine($y))->calculate($g, 'ON', 'annual', $r)['annual'];
    $k = "{$y} ON \${$g}" . ($r ? " rrsp {$r}" : '');
    check("$k federal", $a['federal_tax'], $fed);
    check("$k ontario", $a['provincial_tax'], $on);
    check("$k OHP", $a['health_premium'], $ohp);
    check("$k CPP", $a['cpp'], $cpp);
    check("$k CPP2", $a['cpp2'], $cpp2);
    check("$k EI", $a['ei'], $ei);
    check("$k net", $a['net'], $net);
}

// External cross-check: worldsalaries.com Ontario 2026, $60,000 → $47,339.75
check('external 60k ON net', (new TaxEngine(2026))->calculate(60000, 'ON')['annual']['net'], 47339.75);

// Invariants
$e = new TaxEngine(2026);
check('CPP max', $e->calculate(500000, 'ON')['annual']['cpp'], 4230.45);
check('CPP2 max', $e->calculate(500000, 'ON')['annual']['cpp2'], 416.00);
check('EI max', $e->calculate(500000, 'ON')['annual']['ei'], 1123.07);
check('OHP max', $e->calculate(500000, 'ON')['annual']['health_premium'], 900);
$r = $e->calculate(78000, 'ON', 'biweekly');
check('biweekly = annual/26', $r['per_period']['net'], round($r['annual']['net'] / 26, 2));
$m = $e->calculate(78000, 'ON', 'monthly');
check('monthly = annual/12', $m['per_period']['net'], round($m['annual']['net'] / 12, 2));
$prev = -1; $mono = true;
for ($g = 0; $g <= 400000; $g += 250) {
    $n = $e->calculate($g, 'ON')['annual']['net'];
    if ($n < $prev) { $mono = false; echo "net decreased at {$g}\n"; }
    $prev = $n;
}
check('net never falls as gross rises', $mono ? 1 : 0, 1);
check('marginal at 100k in [0.2,0.6]', ($mr = $e->calculate(100000, 'ON')['marginal_rate']) >= 0.2 && $mr <= 0.6 ? 1 : 0, 1);

foreach ([['ON', 'fortnightly'], ['XX', 'annual'], ['QC', 'annual']] as [$p, $f]) {
    try { $e->calculate(50000, $p, $f); check("rejects $p/$f", 0, 1); }
    catch (InvalidArgumentException) { check("rejects $p/$f", 1, 1); }
}
try { new TaxEngine(1999); check('rejects year 1999', 0, 1); }
catch (InvalidArgumentException) { check('rejects year 1999', 1, 1); }

echo "\n{$pass} passed, {$fail} failed\n";
exit($fail ? 1 : 0);
