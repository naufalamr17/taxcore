import assert from 'node:assert/strict';
import { spawn, spawnSync } from 'node:child_process';
import { randomBytes } from 'node:crypto';
import { existsSync, mkdirSync, mkdtempSync, rmSync, writeFileSync } from 'node:fs';
import { createServer } from 'node:net';
import { tmpdir } from 'node:os';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { setTimeout as delay } from 'node:timers/promises';
import { test } from 'node:test';

const root = fileURLToPath(new URL('../..', import.meta.url));
const chrome = [
    'C:/Program Files/Google/Chrome/Application/chrome.exe',
    'C:/Program Files (x86)/Google/Chrome/Application/chrome.exe',
    join(process.env.LOCALAPPDATA ?? '', 'Google/Chrome/Application/chrome.exe'),
    '/usr/bin/google-chrome', '/usr/bin/chromium',
].find(existsSync);

async function freePort() {
    const server = createServer();
    await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
    const port = server.address().port;
    await new Promise(resolve => server.close(resolve));
    return port;
}

async function waitFor(check, description, timeout = 15000) {
    const deadline = Date.now() + timeout;
    let last;
    while (Date.now() < deadline) {
        try {
            last = await check();
            if (last) return last;
        } catch (error) { last = error.message; }
        await delay(100);
    }
    assert.fail(`Timed out: ${description}. Last result: ${JSON.stringify(last)}`);
}

async function connect(endpoint) {
    const socket = new WebSocket(endpoint);
    await new Promise((resolve, reject) => {
        socket.addEventListener('open', resolve, { once:true });
        socket.addEventListener('error', reject, { once:true });
    });
    let nextId = 0;
    const pending = new Map();
    const errors = [];
    socket.addEventListener('close', () => {
        for (const request of pending.values()) request.reject(new Error('Browser connection closed'));
        pending.clear();
    });
    socket.addEventListener('message', event => {
        const message = JSON.parse(event.data);
        if (message.method === 'Runtime.exceptionThrown') {
            errors.push(message.params.exceptionDetails.exception?.description ?? message.params.exceptionDetails.text);
        }
        const request = pending.get(message.id);
        if (!request) return;
        pending.delete(message.id);
        if (message.error) request.reject(new Error(message.error.message));
        else request.resolve(message.result);
    });
    const send = (method, params = {}) => new Promise((resolve, reject) => {
        const id = ++nextId;
        const timer = setTimeout(() => { pending.delete(id); reject(new Error(`Browser command timed out: ${method}`)); }, 15000);
        pending.set(id, { resolve:value => { clearTimeout(timer); resolve(value); }, reject:error => { clearTimeout(timer); reject(error); } });
        socket.send(JSON.stringify({ id, method, params }));
    });
    const evaluate = async expression => {
        const result = await send('Runtime.evaluate', { expression, returnByValue:true, awaitPromise:true });
        if (result.exceptionDetails) throw new Error(result.exceptionDetails.exception?.description ?? result.exceptionDetails.text);
        return result.result.value;
    };
    await send('Runtime.enable');
    await send('Page.enable');
    return { send, evaluate, errors, close:() => socket.close() };
}

function stopProcess(child) {
    if (!child?.pid || child.exitCode !== null) return;
    child.kill('SIGTERM');
}

test('HTML workspace persists native browser edits and keeps reference dialogs and branch isolation', { skip:!chrome, timeout:180000 }, async t => {
    const temporary = mkdtempSync(join(tmpdir(), 'taxcore-browser-'));
    const database = join(temporary, 'database.sqlite');
    writeFileSync(database, '');
    const appPort = await freePort();
    const debugPort = await freePort();
    const appUrl = `http://127.0.0.1:${appPort}`;
    const environment = {
        ...process.env, APP_ENV:'testing', APP_DEBUG:'true', APP_URL:appUrl,
        APP_KEY:`base64:${randomBytes(32).toString('base64')}`,
        APP_CONFIG_CACHE:join(temporary, 'config.php'), APP_ROUTES_CACHE:join(temporary, 'routes.php'),
        DB_CONNECTION:'sqlite', DB_DATABASE:database, DB_URL:'', CACHE_STORE:'array',
        SESSION_DRIVER:'database', SESSION_COOKIE:'taxcore_browser_test', SESSION_SECURE_COOKIE:'false', LOG_CHANNEL:'stderr',
    };
    let php;
    let browser;
    let cdp;
    let serverPid;
    let processOutput = '';
    t.after(async () => {
        if (cdp) {
            await cdp.send('Browser.close').catch(() => {});
            cdp.close();
        }
        stopProcess(browser);
        if (serverPid) {
            try { process.kill(serverPid, 'SIGTERM'); } catch (error) { if (error.code !== 'ESRCH') throw error; }
        }
        stopProcess(php);
        assert.equal(dirname(resolve(temporary)), resolve(tmpdir()));
        assert.match(resolve(temporary).slice(resolve(tmpdir()).length + 1), /^taxcore-browser-/);
        rmSync(temporary, { recursive:true, force:true, maxRetries:5, retryDelay:100 });
    });
    const phpRoot = root.replaceAll('\\', '/');
    const fixture = `require ${JSON.stringify(`${phpRoot}/vendor/autoload.php`)}; $app = require ${JSON.stringify(`${phpRoot}/bootstrap/app.php`)}; $app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap(); Illuminate\\Support\\Facades\\Artisan::call("migrate", ["--force"=>true,"--no-interaction"=>true]); $user=App\\Models\\User::factory()->create(["name"=>"Browser Administrator","username"=>"browser_admin","role"=>"SUPER_ADMIN","password"=>Illuminate\\Support\\Facades\\Hash::make("browser-test-password")]); $ids=[]; foreach (["Alpha Browser Company","Beta Browser Company"] as $name) { $company=App\\Models\\Company::factory()->create(["name"=>$name]); $branch=App\\Models\\Branch::factory()->for($company)->create(["name"=>"Kantor Pusat","code"=>"000000","nitku"=>$company->npwp."000000"]); $user->companies()->attach($company); $ids[]=["company"=>$company->id,"branch"=>$branch->id]; } echo json_encode($ids);`;
    const seeded = spawnSync('rtk', ['bypass', 'php', '-d', 'extension=pdo_sqlite', '-d', 'extension=sqlite3', '-r', fixture], { cwd:root, env:environment, windowsHide:true, encoding:'utf8', timeout:30000 });
    assert.equal(seeded.status, 0, seeded.stderr || seeded.stdout);
    const companies = JSON.parse(seeded.stdout);
    const launch = (args, options = {}) => {
        const child = spawn('rtk', args, { cwd:root, env:environment, windowsHide:true, stdio:['ignore', 'pipe', 'pipe'], ...options });
        child.stdout.on('data', data => { processOutput = (processOutput + data).slice(-10000); });
        child.stderr.on('data', data => { processOutput = (processOutput + data).slice(-10000); });
        return child;
    };
    const router = join(temporary, 'router.php');
    writeFileSync(router, `<?php if ($_SERVER['REQUEST_URI'] === '/__browser_test_pid') { echo getmypid(); return; } return require ${JSON.stringify(`${phpRoot}/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php`)};`);
    php = launch(['bypass', 'php', '-d', 'extension=pdo_sqlite', '-d', 'extension=sqlite3', '-S', `127.0.0.1:${appPort}`, router], { cwd:join(root, 'public') });
    await waitFor(async () => (await fetch(`${appUrl}/login`)).ok, 'isolated Laravel server');
    serverPid = Number(await (await fetch(`${appUrl}/__browser_test_pid`)).text());
    assert.ok(Number.isSafeInteger(serverPid) && serverPid > 0);
    browser = launch(['bypass', chrome, '--headless=new', '--no-sandbox', '--disable-gpu', '--disable-software-rasterizer', '--no-first-run', '--no-default-browser-check', `--remote-debugging-port=${debugPort}`, `--user-data-dir=${join(temporary, 'chrome')}`, 'about:blank']);
    const target = await waitFor(async () => (await (await fetch(`http://127.0.0.1:${debugPort}/json/list`)).json()).find(page => page.type === 'page'), 'native Chrome DevTools');
    cdp = await connect(target.webSocketDebuggerUrl);
    const evaluate = cdp.evaluate;
    const navigate = async url => {
        await cdp.send('Page.navigate', { url });
        await waitFor(() => evaluate(`document.readyState === 'complete' && location.href.startsWith(${JSON.stringify(url.split('?')[0])})`), `navigation to ${url}`);
    };
    const click = selector => evaluate(`(() => { const element=document.querySelector(${JSON.stringify(selector)}); if (!element) throw new Error('Missing click target: '+${JSON.stringify(selector)}); element.click(); return true; })()`);
    const fill = (id, value) => evaluate(`(() => { const element=document.getElementById(${JSON.stringify(id)}); if (!element) throw new Error('Missing input: '+${JSON.stringify(id)}); element.value=${JSON.stringify(String(value))}; element.dispatchEvent(new Event('input',{bubbles:true})); element.dispatchEvent(new Event('change',{bubbles:true})); return element.value; })()`);
    const visible = id => evaluate(`!!document.getElementById(${JSON.stringify(id)}) && !document.getElementById(${JSON.stringify(id)}).classList.contains('hidden')`);
    const saved = async () => {
        await waitFor(() => evaluate(`!document.body.dataset.saving && !document.body.hasAttribute('aria-busy')`), 'payroll persistence');
        await delay(100);
    };
    const state = () => evaluate(`fetch(${JSON.stringify(`${appUrl}/workspace/state`)},{headers:{Accept:'application/json'}}).then(response=>response.json())`);
    const screenshot = async name => {
        const path = join(root, 'storage/framework/testing', `browser-${name}.png`);
        mkdirSync(dirname(path), { recursive:true });
        const result = await cdp.send('Page.captureScreenshot', { format:'png', captureBeyondViewport:false });
        writeFileSync(path, Buffer.from(result.data, 'base64'));
    };

    await cdp.send('Emulation.setDeviceMetricsOverride', { width:1440, height:1000, deviceScaleFactor:1, mobile:false });
    await navigate(`${appUrl}/login`);
    await screenshot('login');
    await waitFor(() => evaluate(`!!document.querySelector('input[name="username"]')`), 'login form');
    await evaluate(`document.querySelector('input[name="username"]').value='browser_admin'; document.querySelector('input[name="password"]').value='browser-test-password'; document.querySelector('form[action$="/login"]').requestSubmit();`);
    await waitFor(() => evaluate(`typeof window.openAddEmployeeModal === 'function' && !!document.getElementById('taxcore-bootstrap')`), 'authenticated HTML workspace').catch(async error => {
        await screenshot('login-failure');
        throw new Error(`${error.message}; ${JSON.stringify(await evaluate(`({url:location.href,text:document.body.innerText.slice(0,1200)})`))}; ${cdp.errors.join(' ')}; ${processOutput}`);
    });
    assert.equal(await evaluate(`document.getElementById('sidebarUserName').textContent`), 'Browser Administrator');
    await evaluate(`window.toggleLeftSidebar(true)`);
    await waitFor(() => evaluate(`document.getElementById('leftModuleSidebar').getBoundingClientRect().width === 64`), 'collapsed sidebar width');
    await evaluate(`window.toggleLeftSidebar(false)`);
    await waitFor(() => evaluate(`document.getElementById('leftModuleSidebar').getBoundingClientRect().width === 276`), 'restored sidebar width');
    await fill('payrollPeriodMonth', '11');
    await fill('payrollPeriodYear', '2026');
    await click('button[onclick="openAddEmployeeModal()"]');
    assert.equal(await visible('manualEmployeeModal'), true);
    await fill('modalEmpName', 'Browser Payroll One');
    await fill('modalEmpNik', '3171000000000021');
    await fill('modalEmpGajiPokok', '10.000.000');
    await fill('modalEmpTunjangan', '0');
    await screenshot('employee-modal');
    await click('button[onclick="saveManualEmployeeSubmit()"]');
    await saved();
    assert.equal(await visible('manualEmployeeModal'), false);
    let records = (await state()).employees;
    assert.equal(records.length, 1);
    const payrollId = records[0].id;
    assert.equal(records[0].gajiPokok, 10000000);
    await screenshot('payroll-desktop');

    await click('button[onclick^="editEmployee("]');
    await fill('modalEmpGajiPokok', '11.000.000');
    await click('button[onclick="saveManualEmployeeSubmit()"]');
    await saved();
    assert.equal((await state()).employees[0]._version, 2);
    await cdp.send('Page.reload');
    await waitFor(() => evaluate(`typeof window.openAddEmployeeModal === 'function' && document.getElementById('employeeTableBody')?.textContent.includes('Browser Payroll One')`), 'payroll reload');
    assert.match(await evaluate(`document.getElementById('employeeTableBody').textContent`), /11\.000\.000/);
    await evaluate(`window.toggleInlineSpreadsheetMode(); const input=document.querySelector('#employeeTableBody input[onchange*="gajiPokok"]'); input.value='12.000.000'; input.dispatchEvent(new Event('change',{bubbles:true}));`);
    await saved();
    assert.equal((await state()).employees[0].gajiPokok, 12000000);
    assert.equal((await state()).employees[0]._version, 3);
    await evaluate(`window.toggleInlineSpreadsheetMode()`);

    const workbook = await evaluate(`(async () => { const write=XLSX.writeFile; let result; XLSX.writeFile=(book,name)=>{result={sheets:book.SheetNames,name}}; try { await window.exportPayrollCsv(); } finally { XLSX.writeFile=write; } return result; })()`);
    assert.deepEqual(workbook.sheets, ['Coretax_Import']);
    assert.match(workbook.name, /\.xlsx$/);
    const xml = await evaluate(`(async () => { const create=URL.createObjectURL; let blob; URL.createObjectURL=value=>{blob=value;return create(value)}; try { await window.exportCoretaxXml(); } finally { URL.createObjectURL=create; } return blob.text(); })()`);
    assert.match(xml, /<eBupot21Coretax/);
    assert.match(xml, /Browser Payroll One/);

    await evaluate(`window.selectActiveCompany('branch_${companies[1].branch}')`);
    assert.equal(await evaluate(`document.getElementById('employeeTableBody').textContent.includes('Browser Payroll One')`), false);
    await evaluate(`window.selectActiveCompany('branch_${companies[0].branch}')`);
    assert.equal(await evaluate(`document.getElementById('employeeTableBody').textContent.includes('Browser Payroll One')`), true);
    await evaluate(`window.viewEmployeeWorksheet(${JSON.stringify(payrollId)})`);
    assert.equal(await visible('worksheetViewModal'), true);
    assert.match(await evaluate(`document.getElementById('worksheetTitle').textContent`), /Browser Payroll One/);
    await click('#outTabBtn_journal');
    assert.match(await evaluate(`document.getElementById('journalTableBody').textContent`), /Bank Operasional/);
    await screenshot('journal');
    await evaluate(`window.closeWorksheetViewModal(); window.openTerModal();`);
    assert.equal(await visible('terModal'), true);
    await screenshot('ter');
    await evaluate(`window.closeTerModal(); window.openImportModal();`);
    await fill('pasteImportTextarea', 'Masa_Pajak\tTahun_Pajak\tNama_Pegawai\tNIK_NPWP\tJabatan\tStatus_PTKP\tMetode_Pajak\tGaji_Pokok\n11\t2026\tBrowser Imported Two\t3171000000000022\tStaff\tTK/0\tGROSS\t9000000');
    await waitFor(() => visible('importPreviewContainer'), 'paste import preview');
    assert.match(await evaluate(`document.getElementById('importPreviewTableBody').textContent`), /Browser Imported Two/);
    await screenshot('import-preview');
    await click('button[onclick="processImportData()"]');
    await saved();
    records = (await state()).employees;
    assert.equal(records.length, 2);
    assert.equal(records.some(record => record.name === 'Browser Imported Two'), true);

    await click('#periodModeBtn_TAHUNAN');
    await click('button[onclick="openAddEmployeeModal()"]');
    await fill('modalEmpName', 'Browser Annual Three');
    await fill('modalEmpNik', '3171000000000023');
    await fill('modalEmpGajiPokok', '12.000.000');
    await fill('modalEmpTunjangan', '0');
    await click('button[onclick="saveManualEmployeeSubmit()"]');
    await saved();
    const annual = (await state()).employees.find(record => record.name === 'Browser Annual Three');
    assert.equal(annual.subjectType, 'pegawai_tahunan');
    await evaluate(`window.viewEmployeeWorksheet(${JSON.stringify(annual.id)})`);
    assert.match(await evaluate(`document.getElementById('worksheetLegalTag').textContent`), /1721-A1/);
    await evaluate(`window.updateWorksheetA1Field(${JSON.stringify(annual.id)}, 'a1ManualRegBruto', '123.000.000')`);
    await saved();
    assert.equal((await state()).employees.find(record => record.id === annual.id).a1ManualRegBruto, 123000000);
    await screenshot('annual-worksheet');
    await evaluate(`window.closeWorksheetViewModal()`);
    await fill('payrollPeriodMonth', '11');
    await evaluate(`window.openImportModal('pegawai_bulanan')`);
    await fill('importModeSelect', 'REPLACE');
    await fill('pasteImportTextarea', 'Masa_Pajak\tTahun_Pajak\tNama_Pegawai\tNIK_NPWP\tJabatan\tStatus_PTKP\tMetode_Pajak\tGaji_Pokok\n11\t2026\tBrowser Replacement Four\t3171000000000024\tStaff\tTK/0\tGROSS\t8000000');
    await click('button[onclick="processImportData()"]');
    await saved();
    records = (await state()).employees;
    assert.equal(records.length, 2);
    assert.equal(records.some(record => record.id === annual.id), true, 'Replace monthly import preserves annual records');
    assert.equal(records.some(record => record.name === 'Browser Replacement Four'), true);
    await evaluate(`window.closeWorksheetViewModal(); window.switchWorkspaceMode('access');`);
    await waitFor(() => evaluate(`document.getElementById('accessFrame').contentDocument?.body.innerText.includes('Daftar Pengguna Terdaftar')`), 'preserved user management');
    await waitFor(() => evaluate(`document.getElementById('accessFrame').contentDocument?.readyState === 'complete' && document.getElementById('accessFrame').getBoundingClientRect().height > 200`), 'visible user management frame');
    await evaluate(`new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve)))`);
    await screenshot('user-management');
    await evaluate(`window.switchWorkspaceMode('audit');`);
    await waitFor(() => evaluate(`document.getElementById('auditFrame').contentDocument?.body.innerText.includes('payroll.entry.saved')`), 'preserved payroll audit');
    await waitFor(() => evaluate(`document.getElementById('auditFrame').contentDocument?.readyState === 'complete' && document.getElementById('auditFrame').getBoundingClientRect().height > 100`), 'visible audit frame');
    assert.match(await evaluate(`document.getElementById('auditFrame').contentDocument.body.innerText`), /export\.xlsx/);
    assert.match(await evaluate(`document.getElementById('auditFrame').contentDocument.body.innerText`), /export\.xml/);
    await screenshot('audit');
    await evaluate(`window.switchWorkspaceMode('company');`);
    await screenshot('companies');
    await evaluate(`window.switchWorkspaceMode('simulator'); window.switchSubject('bukan_pegawai');`);
    assert.equal(await visible('workspace_simulator'), true);
    await screenshot('simulator');
    await evaluate(`window.generateBulkEmployees(1000)`);
    await waitFor(() => evaluate(`!document.body.dataset.saving`), '1000 generated payroll records', 60000);
    records = (await state()).employees;
    assert.equal(records.length, 1002);
    assert.equal(records.filter(record => record.id.startsWith('emp_bulk_')).length, 1000);
    await evaluate(`window.generateBulkEmployees(100)`);
    await saved();
    records = (await state()).employees;
    assert.equal(records.length, 1102);
    assert.equal(new Set(records.filter(record => record.id.startsWith('emp_bulk_')).map(record => record.nik)).size, 1100);
    await evaluate(`window.resetSampleEmployees()`);
    await waitFor(() => evaluate(`!document.body.dataset.saving`), 'sample reset preserves manual records', 60000);
    assert.equal((await state()).employees.length, 2);
    await evaluate(`window.deleteCompanyRecord('branch_${companies[1].branch}')`);
    assert.equal((await state()).companies.length, 1);
    await evaluate(`window.deleteCompanyRecord('branch_${companies[0].branch}')`);
    assert.equal((await state()).companies.length, 1);
    assert.match(await evaluate(`document.getElementById('toastMessage').textContent`), /Minimal harus ada 1 perusahaan/);
    await evaluate(`window.toggleCyberDarkTheme()`);
    assert.equal(await evaluate(`document.body.classList.contains('cyber-dark')`), true);
    await screenshot('dark-desktop');
    await cdp.send('Emulation.setDeviceMetricsOverride', { width:390, height:844, deviceScaleFactor:1, mobile:true });
    await waitFor(() => evaluate(`document.getElementById('leftModuleSidebar').getBoundingClientRect().right <= 0`), 'hidden mobile sidebar');
    await screenshot('mobile');
    await evaluate(`window.toggleMobileSidebar(true)`);
    assert.equal(await visible('mobileSidebarBackdrop'), true);
    await waitFor(() => evaluate(`document.getElementById('leftModuleSidebar').getBoundingClientRect().left === 0`), 'visible mobile sidebar');
    await screenshot('mobile-navigation');
    assert.deepEqual(cdp.errors, [], `Browser runtime errors: ${cdp.errors.join('\n')}`);
    assert.equal(processOutput.includes('PHP Fatal error'), false, processOutput);
});
