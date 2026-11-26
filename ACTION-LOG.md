# Action log

## 2026-11-26: initial scaffold (branch `setup/engine`)
- Created engine with 2026 (final) and 2027 (provisional) parameter files from CRA T4127 Jan 2027 edition, Tables 8.1/8.2 and 8.22/8.23.
- Implemented federal tax, CPP, CPP2, EI, Ontario tax, surtax, tax reduction and Health Premium.
- Tests: 160 checks; Ontario fixtures from an independent reference calculation; $60k ON 2026 matches a published third-party figure ($47,339.75).
- Added calculate.php (CSRF, limits, file-based rate limit), index.php calculator, .htaccess security headers, config/site.php.
- Next: PDOC validation per frequency, BC/AB/other provinces, brand + domain.
