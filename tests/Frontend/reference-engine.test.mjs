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
