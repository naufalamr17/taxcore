import './shadcn.jsx';

if (document.body.hasAttribute('data-workspace-frame')) {
    document.querySelectorAll('form').forEach(form => {
        const mode = document.createElement('input');
        mode.type = 'hidden';
        mode.name = '_dialog';
        mode.value = '1';
        form.append(mode);
    });
    document.addEventListener('click', event => {
        const link = event.target.closest('a');
        if (!link || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        if (link.hasAttribute('data-frame-close')) {
            event.preventDefault();
            window.parent.postMessage({ type:'taxcore:dialog-close' }, window.location.origin);
        } else {
            const url = new URL(link.href, window.location.href);
            if (url.origin === window.location.origin && !/\/exports\/|\/imports\/template$/.test(url.pathname)) {
                url.searchParams.set('_dialog', '1');
                link.href = url.href;
            }
        }
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && !event.defaultPrevented && !document.querySelector('[role="dialog"],[role="alertdialog"]')) {
            window.parent.postMessage({ type:'taxcore:dialog-close' }, window.location.origin);
        }
    });
    if (document.body.hasAttribute('data-dialog-updated')) window.parent.postMessage({ type:'taxcore:dialog-updated', complete:!window.location.pathname.endsWith('/imports') }, window.location.origin);
}

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
                        const newRecipient = employeeSearch.form.querySelector('input[type="checkbox"][name="new_employee"]');
                        if (newRecipient) newRecipient.checked = false;
                        employeeSearch.value = employee.name + ' · ' + employee.nik;
                        employeeSearch.form.elements.namedItem('ptkp').value = employee.ptkp;
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

document.querySelector('input[type="checkbox"][name="new_employee"]')?.addEventListener('change', event => {
    if (event.target.checked) {
        event.target.form.querySelector('[data-employee-id]').value = '';
        event.target.form.querySelector('[data-employee-search]').value = '';
        event.target.form.querySelector('[data-employee-results]').replaceChildren();
    }
});

document.querySelectorAll('[data-company-identity]').forEach(form => {
    const preview = form.querySelector('[data-company-nitku]');
    const update = () => {
        const npwp = form.elements.namedItem('npwp').value;
        preview.value = /^\d{16}$/.test(npwp) ? npwp + '000000' : '';
    };
    form.addEventListener('input', update);
    form.addEventListener('reset', () => queueMicrotask(update));
    update();
});
