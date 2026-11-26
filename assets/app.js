(() => {
  const form = document.getElementById('calc');
  const box = document.getElementById('result');
  const fmt = n => n.toLocaleString('en-CA', { style: 'currency', currency: 'CAD' });
  const label = { annual: 'a year', monthly: 'a month', semimonthly: 'per pay (semi-monthly)', biweekly: 'every two weeks', weekly: 'a week' };
  const rows = [['Federal tax', 'federal_tax'], ['Provincial tax', 'provincial_tax'], ['Health premium', 'health_premium'],
    ['CPP', 'cpp'], ['CPP2', 'cpp2'], ['EI', 'ei'], ['RRSP', 'rrsp'], ['Take-home', 'net']];

  form.addEventListener('submit', async e => {
    e.preventDefault();
    const res = await fetch('/calculate.php', { method: 'POST', body: new FormData(form), credentials: 'same-origin' });
    const d = await res.json();
    box.replaceChildren();
    if (!res.ok) { box.textContent = d.error || 'Error'; return; }
    const p = d.per_period;
    const head = document.createElement('p');
    head.className = 'net';
    head.textContent = `Take-home: ${fmt(p.net)} ${label[d.frequency]}`;
    const t = document.createElement('table');
    for (const [name, key] of rows) {
      const tr = t.insertRow();
      tr.insertCell().textContent = name;
      tr.insertCell().textContent = fmt(p[key]);
      tr.insertCell().textContent = fmt(d.annual[key]);
    }
    const rates = document.createElement('p');
    rates.textContent = `Average deduction rate ${(d.average_rate * 100).toFixed(1)}% · marginal rate ${(d.marginal_rate * 100).toFixed(1)}%`;
    box.append(head, t, rates);
  });
})();
