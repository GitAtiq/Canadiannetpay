# Canadian net pay calculator

Take-home pay calculator for Canadian employees. Same architecture as calcolastipendionetto.com.

## Structure
- `engine/TaxEngine.php`: the single calculation engine (CRA T4127 annualised method)
- `engine/params/<year>.php`: one parameter file per tax year (2026 final, 2027 provisional). **A new tax year means editing one file.**
- `tests/run-tests.php`: engine tests. Run `php tests/run-tests.php` before every merge.
- `calculate.php`: JSON endpoint (POST, CSRF, input limits, rate limit)
- `index.php`, `assets/`: calculator page
- `config/site.php`: brand, domain, author, release schedule (real data only)
- `.htaccess`: canonical host, blocks internal folders, HSTS, CSP

## Coverage
| Jurisdiction | Status |
|---|---|
| Federal, CPP/CPP2, EI | done, tested |
| Ontario (surtax, tax reduction, Health Premium) | done, tested |
| Other provinces and territories | brackets/BPA loaded, provincial credits/reductions to implement, disabled |
| Quebec | separate system (QPP, QPIP, Revenu Québec): later phase |

Not included: return-only credits (Ontario LIFT, CWB), dependants (Ontario Y), TD1 claim codes other than the basic amount.

## Workflow
Branch → PR → backup branch of live code → merge. Log each step in `ACTION-LOG.md`.
Hostinger: hPanel → Git auto-deploy from `main`. Cloudflare: proxied CNAME `www` → apex.
