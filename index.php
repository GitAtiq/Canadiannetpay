<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

$year = $CONFIG['default_year'];
$engine = new TaxEngine($year);
$example = $engine->calculate(60000, 'ON');   // figures in title/meta come from the engine
$net = number_format($example['annual']['net'], 0);
$title = "Ontario Salary After Tax Calculator {$year} — \$60,000 = \${$net} take-home";
$desc  = "See your {$year} take-home pay in Ontario after federal and provincial tax, CPP, CPP2, EI and the Ontario Health Premium. \$60,000 a year leaves \${$net}.";
$provinces = $engine->params()['provinces'];
?><!doctype html>
<html lang="en-CA">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($title) ?></title>
<meta name="description" content="<?= h($desc) ?>">
<link rel="canonical" href="https://<?= h($CONFIG['domain']) ?>/">
<link rel="stylesheet" href="/assets/app.css">
</head>
<body>
<main>
  <h1>Salary after tax calculator (<?= (int)$year ?>)</h1>
  <form id="calc" autocomplete="off">
    <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
    <input type="hidden" name="year" value="<?= (int)$year ?>">
    <label>Annual salary ($)
      <input name="gross" type="number" min="0" max="10000000" step="100" value="60000" required></label>
    <label>Province
      <select name="province">
        <?php foreach ($provinces as $code => $p): if (!empty($p['enabled'])): ?>
          <option value="<?= h($code) ?>"><?= h($p['name']) ?></option>
        <?php endif; endforeach; ?>
      </select></label>
    <label>Pay frequency
      <select name="frequency">
        <option value="annual">Yearly</option><option value="monthly">Monthly</option>
        <option value="semimonthly">Semi-monthly</option><option value="biweekly">Bi-weekly</option>
        <option value="weekly">Weekly</option>
      </select></label>
    <label>RRSP deducted at source ($/yr)
      <input name="rrsp" type="number" min="0" step="100" value="0"></label>
    <button type="submit">Calculate</button>
  </form>

  <section id="result" aria-live="polite">
    <p class="net">Take-home: <strong>$<?= h(number_format($example['annual']['net'], 2)) ?></strong> a year</p>
  </section>

  <p class="note">Estimate for an employee paid evenly through the year, using CRA T4127 formulas.
  Excludes credits claimed only on your tax return (e.g. Ontario LIFT).</p>
</main>
<script src="/assets/app.js" defer></script>
</body>
</html>
