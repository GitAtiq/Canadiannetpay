<?php
declare(strict_types=1);

/**
 * Canadian employee take-home pay engine.
 *
 * Follows the CRA T4127 annualised method (Option 1) for a salary paid evenly
 * across the year: annual result = what PDOC withholds over the full year.
 * Excludes return-only credits (e.g. Ontario LIFT, Canada Workers Benefit).
 */
final class TaxEngine
{
    public const FREQUENCIES = ['weekly' => 52, 'biweekly' => 26, 'semimonthly' => 24, 'monthly' => 12, 'annual' => 1];

    private array $p;

    public function __construct(int $year)
    {
        $file = __DIR__ . "/params/{$year}.php";
        if (!preg_match('/^20\d\d$/', (string)$year) || !is_file($file)) {
            throw new InvalidArgumentException("Unsupported tax year: {$year}");
        }
        $this->p = require $file;
    }

    public static function years(): array
    {
        $y = array_map(fn($f) => (int)basename($f, '.php'), glob(__DIR__ . '/params/20*.php'));
        sort($y);
        return $y;
    }

    public function params(): array { return $this->p; }

    public function enabledProvinces(): array
    {
        return array_keys(array_filter($this->p['provinces'], fn($pr) => !empty($pr['enabled'])));
    }

    /**
     * @param float  $gross  annual gross employment income
     * @param string $prov   two-letter code
     * @param float  $rrsp   annual RRSP contributions deducted at source
     */
    public function calculate(float $gross, string $prov, string $frequency = 'annual', float $rrsp = 0.0): array
    {
        $prov = strtoupper($prov);
        if (!in_array($prov, $this->enabledProvinces(), true)) {
            throw new InvalidArgumentException("Province not supported yet: {$prov}");
        }
        if (!isset(self::FREQUENCIES[$frequency])) {
            throw new InvalidArgumentException("Unknown pay frequency: {$frequency}");
        }
        $gross = max(0.0, $gross);
        $rrsp  = min(max(0.0, $rrsp), $gross);

        // CPP (base + first additional) and CPP2
        $c = $this->p['cpp'];
        $cpp1 = self::r(min(max(0, min($gross, $c['ympe']) - $c['exemption']) * $c['rate'], $c['max']));
        $cpp2 = self::r(min(max(0, min($gross, $c['yampe']) - $c['ympe']) * $c['cpp2_rate'], $c['cpp2_max']));
        $cppBase     = $cpp1 * $c['base_rate'] / $c['rate'];   // → non-refundable credit
        $cppEnhanced = $cpp1 - $cppBase + $cpp2;               // → deduction from income

        $e  = $this->p['ei'];
        $ei = self::r(min(min($gross, $e['mie']) * $e['rate'], $e['max']));

        $taxable = max(0.0, $gross - $cppEnhanced - $rrsp);

        $federal = $this->federalTax($taxable, $gross, $cppBase, $ei);
        $provincial = $this->provincialTax($prov, $taxable, $cppBase, $ei);

        $deductions = $federal + $provincial['total'] + $cpp1 + $cpp2 + $ei;
        $net = $gross - $deductions - $rrsp;

        $annual = [
            'gross' => self::r($gross), 'rrsp' => self::r($rrsp), 'taxable_income' => self::r($taxable),
            'federal_tax' => $federal,
            'provincial_tax' => $provincial['tax'],
            'health_premium' => $provincial['health_premium'],
            'cpp' => $cpp1, 'cpp2' => $cpp2, 'ei' => $ei,
            'total_deductions' => self::r($deductions),
            'net' => self::r($net),
        ];
        $n = self::FREQUENCIES[$frequency];

        return [
            'year' => $this->p['year'], 'status' => $this->p['status'],
            'province' => $prov, 'frequency' => $frequency,
            'annual' => $annual,
            'per_period' => array_map(fn($v) => self::r($v / $n), $annual),
            'average_rate' => $gross > 0 ? round($deductions / $gross, 4) : 0.0,
            'marginal_rate' => $this->marginalRate($gross, $prov, $rrsp),
            'provincial_detail' => $provincial,
        ];
    }

    private function federalTax(float $taxable, float $gross, float $cppBase, float $ei): float
    {
        $f = $this->p['federal'];
        $bpa = $f['bpa_max'];
        if ($taxable > $f['bpa_phase_start']) {
            $bpa = $taxable >= $f['bpa_phase_end'] ? $f['bpa_min']
                : $f['bpa_max'] - ($taxable - $f['bpa_phase_start'])
                  * ($f['bpa_max'] - $f['bpa_min']) / ($f['bpa_phase_end'] - $f['bpa_phase_start']);
        }
        $credits = $f['lowest_rate'] * ($bpa + $cppBase + $ei + min($f['cea'], $gross));
        return self::r(max(0.0, self::bracketTax($taxable, $f['brackets']) - $credits));
    }

    private function provincialTax(string $code, float $taxable, float $cppBase, float $ei): array
    {
        $pr = $this->p['provinces'][$code];
        $low = $pr['brackets'][0][1];
        $bpa = $pr['bpa'] === 'federal' ? $this->p['federal']['bpa_max'] : $pr['bpa'];
        $basic = max(0.0, self::bracketTax($taxable, $pr['brackets']) - $low * ($bpa + $cppBase + $ei));

        $surtax = 0.0;
        foreach ($pr['surtax'] ?? [] as [$threshold, $rate]) {
            $surtax += max(0.0, $basic - $threshold) * $rate;
        }
        $reduction = 0.0;
        if (isset($pr['reduction_base'])) {
            $reduction = min($basic + $surtax, max(0.0, 2 * $pr['reduction_base'] - ($basic + $surtax)));
        }
        $tax = self::r($basic + $surtax - $reduction);
        $ohp = isset($pr['health_premium']) ? self::healthPremium($taxable, $pr['health_premium']) : 0.0;

        return [
            'basic' => self::r($basic), 'surtax' => self::r($surtax), 'reduction' => self::r($reduction),
            'tax' => $tax, 'health_premium' => $ohp, 'total' => self::r($tax + $ohp),
        ];
    }

    private function marginalRate(float $gross, string $prov, float $rrsp): float
    {
        $a = $this->core($gross, $prov, $rrsp);
        $b = $this->core($gross + 100, $prov, $rrsp);
        return round(($b - $a) / 100, 4);
    }

    /** Total deductions only (no recursion into marginal rate). */
    private function core(float $gross, string $prov, float $rrsp): float
    {
        $c = $this->p['cpp']; $e = $this->p['ei'];
        $cpp1 = min(max(0, min($gross, $c['ympe']) - $c['exemption']) * $c['rate'], $c['max']);
        $cpp2 = min(max(0, min($gross, $c['yampe']) - $c['ympe']) * $c['cpp2_rate'], $c['cpp2_max']);
        $base = $cpp1 * $c['base_rate'] / $c['rate'];
        $ei = min(min($gross, $e['mie']) * $e['rate'], $e['max']);
        $taxable = max(0.0, $gross - ($cpp1 - $base + $cpp2) - $rrsp);
        return $this->federalTax($taxable, $gross, $base, $ei)
            + $this->provincialTax($prov, $taxable, $base, $ei)['total'] + $cpp1 + $cpp2 + $ei;
    }

    private static function bracketTax(float $income, array $brackets): float
    {
        $tax = 0.0;
        $n = count($brackets);
        for ($i = 0; $i < $n; $i++) {
            [$lo, $rate] = $brackets[$i];
            $hi = $brackets[$i + 1][0] ?? INF;
            if ($income <= $lo) break;
            $tax += (min($income, $hi) - $lo) * $rate;
        }
        return $tax;
    }

    private static function healthPremium(float $taxable, array $bands): float
    {
        foreach ($bands as [$lo, $hi, $base, $rate, $cap]) {
            if ($taxable > $lo && $taxable <= $hi) {
                return self::r(min($base + ($taxable - $lo) * $rate, $cap));
            }
        }
        return 0.0;
    }

    private static function r(float $v): float { return round($v, 2); }
}
