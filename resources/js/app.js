import './shadcn.jsx';

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
                        const newRecipient = document.querySelector('[name="new_employee"]');
                        if (newRecipient) newRecipient.checked = false;
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
    const form = field.form;
    if (!form) return;
    const month = Number(form.elements.namedItem('month')?.value);
    const year = Number(form.elements.namedItem('year')?.value);
    const module = form.querySelector('[data-module-select]') ?? form.elements.namedItem('module');
    if (module && !module.disabled && month === 12 && module.value === 'monthly') module.value = 'annual';
    const date = form.elements.namedItem('withholding_date');
    if (date && month >= 1 && month <= 12 && year >= 2024 && year <= 2026) {
        const lastDay = new Date(year, month, 0).getDate();
        date.value = year + '-' + String(month).padStart(2,'0') + '-' + String(lastDay).padStart(2,'0');
    }
}));

document.querySelectorAll('[data-worksheet-reset]').forEach(button => button.addEventListener('click', () => {
    button.closest('details').querySelectorAll('[name^="worksheet["]').forEach(field => { field.value = ''; });
}));

document.querySelector('[name="new_employee"]')?.addEventListener('change', event => {
    if (event.target.checked) {
        document.querySelector('[data-employee-id]').value = '';
        document.querySelector('[data-employee-search]').value = '';
        document.querySelector('[data-employee-results]').replaceChildren();
    }
});
