import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';
import { test } from 'node:test';

const reference = readFileSync(new URL('../../taxcore_pph_21_enterprise_sistem_payroll_kalkulator_pajak_pp_58_2023 (15) 1.html', import.meta.url), 'utf8');
const application = readFileSync(new URL('../../resources/js/html-workspace.js', import.meta.url), 'utf8');

function engine(source) {
    const context = {};
    runInNewContext(source.slice(0, source.indexOf('        Object.assign(window,')) + `
        globalThis.calculate = record => computeEmployeeTaxRow(normalizeEmployeeRecord(record));
        globalThis.setRecords = records => { employeesData = records.map(normalizeEmployeeRecord); };
    `, context);
    return context;
}

const original = engine(reference.match(/<script>\s*(const STORAGE_KEY_USERS[\s\S]*?)<\/script>/)[1]);
const clone = engine(application);
const subjects = ['pegawai_bulanan', 'pegawai_tahunan', 'pensiunan', 'tidak_tetap', 'bukan_pegawai', 'pesangon_final'];
const plain = value => JSON.parse(JSON.stringify(value));

test('simulator add import and template buttons use private module state for every subject', () => {
    const markup = readFileSync(new URL('../../resources/views/workspace.blade.php', import.meta.url), 'utf8');
    const simulator = markup.slice(markup.indexOf('id="workspace_simulator"'), markup.indexOf('id="workspace_company"'));
    const handlers = ['openAddEmployeeModal', 'openImportModal', 'downloadImportTemplateExcel'].map(name => simulator.match(new RegExp(`onclick="(${name}\\([^"\\n]*\\))"`))?.[1]);
    assert.ok(handlers.every(Boolean));
    const elements = new Map();
    const getElementById = id => {
        if (!elements.has(id)) {
            const classes = new Set(['hidden']);
            elements.set(id, {value:'',dataset:{},classList:{add:name => classes.add(name),remove:name => classes.delete(name),contains:name => classes.has(name)}});
        }
        return elements.get(id);
    };
    const downloads = [];
    const context = {document:{getElementById},setTimeout:() => 0,clearTimeout:() => {},XLSX:{utils:{json_to_sheet:rows => rows,book_new:() => ({}),book_append_sheet:() => {}},writeFile:(_, name) => downloads.push(name)}};
    context.window = context;
    runInNewContext(`{ ${application.slice(0, application.indexOf('        Object.assign(window,'))}
        Object.assign(window, {openAddEmployeeModal, openImportModal, downloadImportTemplateExcel});
        window.selectSubject = subject => { currentWorkspaceMode = 'simulator'; currentSubject = subject; };
    }`, context);
    for (const subject of subjects) {
        context.selectSubject(subject);
        getElementById('simPeriodMonth').value = subject === 'pegawai_tahunan' ? '12' : '11';
        getElementById('simPeriodYear').value = '2026';
        for (const handler of handlers) runInNewContext(handler, context);
        assert.equal(getElementById('manualEmployeeModal').classList.contains('hidden'), false);
        assert.equal(getElementById('modalEmpSubjectType').value, subject);
        assert.equal(getElementById('importExcelModal').classList.contains('hidden'), false);
        assert.equal(getElementById('importTargetModuleSelect').value, subject);
        assert.equal(getElementById('modalEmpYear').value, '2026');
    }
    assert.equal(downloads.length, subjects.length);
    assert.equal(new Set(downloads).size, subjects.length);
});

for (const subjectType of subjects) {
    test(`${subjectType} preserves reference calculations for every tax method and fractional income`, () => {
        for (const taxMethod of ['GROSS', 'GROSS_UP', 'NETT']) {
            for (const salary of [0, 450000, 5400000, 10350000, 28000000.25, 6000000000]) {
                const record = { id:'comparison', subjectType, taxMethod, month:subjectType === 'pegawai_tahunan' ? 12 : 11, year:2026, name:'Uji', nik:'3171000000000002', ptkp:'K/1', gajiPokok:salary, tunjangan:1500000, bonus:250000, thr:0, bpjs:150000, iuranPensiun:100000, iuranJht:200000, workDays:5, workMonths:12 };
                assert.deepEqual(plain(clone.calculate(record)), plain(original.calculate(record)), `${taxMethod}, salary ${salary}`);
            }
        }
    });
}

test('annual worksheet preserves monthly links and all seven reference overrides', () => {
    const monthly = Array.from({ length:11 }, (_, index) => ({ id:`month_${index}`, subjectType:'pegawai_bulanan', month:index + 1, year:2026, name:'Uji', nik:'3171000000000002', ptkp:'K/1', taxMethod:'GROSS', gajiPokok:20000000, bpjs:500000 }));
    original.setRecords(monthly);
    clone.setRecords(monthly);
    const annual = { ...monthly[0], id:'annual', subjectType:'pegawai_tahunan', month:12, a1ManualRegBruto:250000000, a1ManualIrregBruto:50000000, a1ManualBpjsComp:6000000, a1ManualBiayaJabatan:6000000, a1ManualIuranPensiun:1200000, a1ManualIuranJht:2400000, a1ManualPriorTer:10000000 };

    assert.deepEqual(plain(clone.calculate(annual)), plain(original.calculate(annual)));
});
