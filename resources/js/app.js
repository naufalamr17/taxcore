const root = document.documentElement;
try { if (localStorage.getItem('taxcore-theme') === 'dark') root.dataset.theme = 'dark'; } catch {}
document.querySelector('[data-theme-toggle]')?.addEventListener('click', () => {
    root.dataset.theme = root.dataset.theme === 'dark' ? 'light' : 'dark';
    try { localStorage.setItem('taxcore-theme', root.dataset.theme); } catch {}
});
document.querySelector('[data-sidebar-toggle]')?.addEventListener('click', event => {
    const open = document.getElementById('sidebar').classList.toggle('open');
    event.currentTarget.setAttribute('aria-expanded', String(open));
});
document.querySelector('[data-company-switch]')?.addEventListener('change', event => { window.location.assign(event.target.value); });
document.querySelectorAll('[data-password-toggle]').forEach(button => button.addEventListener('click', () => {
    const field = document.getElementById(button.dataset.passwordToggle);
    const visible = field.type === 'password';
    field.type = visible ? 'text' : 'password';
    button.textContent = visible ? 'Sembunyikan' : 'Lihat';
    button.setAttribute('aria-pressed', String(visible));
}));
document.querySelectorAll('[data-confirm]').forEach(form => form.addEventListener('submit', event => {
    if (!window.confirm(form.dataset.confirm)) event.preventDefault();
}));
document.querySelector('[data-print]')?.addEventListener('click', () => window.print());
document.querySelector('[data-spreadsheet-toggle]')?.addEventListener('click', event => {
    const active = document.getElementById('payroll-table').classList.toggle('spreadsheet');
    event.currentTarget.setAttribute('aria-pressed', String(active));
    event.currentTarget.textContent = active ? 'Tutup spreadsheet' : 'Mode spreadsheet';
});
const employeeSearch = document.querySelector('[data-employee-search]');
if (employeeSearch) {
    let timer;
    let controller;
    const results = document.querySelector('[data-employee-results]');
    const idField = document.querySelector('[data-employee-id]');
    employeeSearch.addEventListener('input', () => {
        clearTimeout(timer);
        controller?.abort();
        idField.value = '';
        results.replaceChildren();
        const query = employeeSearch.value.trim();
        if (query.length < 2) return;
        timer = setTimeout(async () => {
            controller = new AbortController();
            const url = new URL(employeeSearch.dataset.employeeSearch, window.location.origin);
            url.searchParams.set('q', query);
            try {
                const response = await fetch(url, { headers: { Accept: 'application/json' }, signal: controller.signal });
                if (!response.ok) throw new Error('Pencarian gagal. Muat ulang halaman atau periksa akses.');
                const employees = await response.json();
                results.replaceChildren();
                if (!employees.length) { results.textContent = 'Tidak ditemukan. Tambahkan melalui Database penerima.'; return; }
                employees.forEach(employee => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'button small w-full text-left';
                    button.textContent = employee.name + ' · ' + employee.nik + ' · ' + employee.position;
                    button.addEventListener('click', () => {
                        idField.value = employee.id;
                        employeeSearch.value = employee.name + ' · ' + employee.nik;
                        document.querySelector('[name="ptkp"]').value = employee.ptkp;
                        results.replaceChildren();
                    });
                    results.append(button);
                });
            } catch (error) { if (error.name !== 'AbortError') results.textContent = error.message; }
        }, 250);
    });
}
document.querySelectorAll('[name="month"],[name="year"],[data-module-select]').forEach(field => field.addEventListener('change', () => {
    const form = field.closest('form');
    const month = Number(form.querySelector('[name="month"]')?.value);
    const year = Number(form.querySelector('[name="year"]')?.value);
    const module = form.querySelector('[data-module-select]');
    if (module && !module.disabled && month === 12 && module.value === 'monthly') module.value = 'annual';
    const date = form.querySelector('[name="withholding_date"]');
    if (date && month >= 1 && month <= 12 && year >= 2024 && year <= 2026) {
        const lastDay = new Date(year, month, 0).getDate();
        date.value = year + '-' + String(month).padStart(2,'0') + '-' + String(lastDay).padStart(2,'0');
    }
}));
