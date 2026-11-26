<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function out(int $code, array $body): never {
    http_response_code($code);
    echo json_encode($body, JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(405, ['error' => 'POST only']);
if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) out(403, ['error' => 'Invalid token']);

// Rate limit: fixed window per IP, file-based (no DB needed on shared hosting)
$rl = $CONFIG['rate_limit'];
$file = __DIR__ . '/storage/ratelimit/' . hash('sha256', $_SERVER['REMOTE_ADDR'] ?? '') . '.json';
$now = time();
$state = is_file($file) ? json_decode((string)file_get_contents($file), true) : null;
if (!$state || $now - $state['t'] >= $rl['window']) $state = ['t' => $now, 'n' => 0];
if (++$state['n'] > $rl['requests']) out(429, ['error' => 'Too many requests']);
file_put_contents($file, json_encode($state), LOCK_EX);

// Input limits
$gross = filter_var($_POST['gross'] ?? '', FILTER_VALIDATE_FLOAT);
$rrsp  = filter_var($_POST['rrsp'] ?? 0, FILTER_VALIDATE_FLOAT);
$year  = filter_var($_POST['year'] ?? $CONFIG['default_year'], FILTER_VALIDATE_INT);
$prov  = strtoupper(substr((string)($_POST['province'] ?? ''), 0, 2));
$freq  = (string)($_POST['frequency'] ?? 'annual');

if ($gross === false || $gross < 0 || $gross > 10_000_000) out(422, ['error' => 'Gross must be 0–10,000,000']);
if ($rrsp === false || $rrsp < 0 || $rrsp > $gross) out(422, ['error' => 'Invalid RRSP amount']);
if ($year === false || !in_array($year, TaxEngine::years(), true)) out(422, ['error' => 'Unsupported year']);

try {
    out(200, (new TaxEngine($year))->calculate((float)$gross, $prov, $freq, (float)$rrsp));
} catch (InvalidArgumentException $e) {
    out(422, ['error' => $e->getMessage()]);
}
