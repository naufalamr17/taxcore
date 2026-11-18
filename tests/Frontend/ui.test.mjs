import assert from 'node:assert/strict';
import { after, before, test } from 'node:test';
import { createServer } from 'vite';
import react from '@vitejs/plugin-react';
import { fileURLToPath } from 'node:url';

let server;
let createElement;
let renderToStaticMarkup;
let Workspace;
let AuthForm;
before(async () => {
    server = await createServer({ configFile:false, appType:'custom', plugins:[react()], resolve:{alias:{'@':fileURLToPath(new URL('../../resources/js',import.meta.url))}}, server:{middlewareMode:true,hmr:false,watch:null} });
    ({ createElement } = await import('react'));
    ({ renderToStaticMarkup } = await import('react-dom/server'));
    ({ Workspace } = await server.ssrLoadModule('/resources/js/components/workspace.jsx'));
    ({ AuthForm } = await server.ssrLoadModule('/resources/js/components/auth-form.jsx'));
});
after(async () => { await server?.close(); delete globalThis.localStorage; });

const workspace = {
    title:'Perusahaan', dashboardUrl:'/dashboard', companyId:1,
    companies:[{id:1,name:'Perusahaan Aman',href:'/companies/1/payroll'}], companiesUrl:'/companies',
    navigation:[{label:'Daftar Gaji & PPh 21',href:'/companies/1/payroll',icon:'payroll'}],
    modules:[{label:'Pegawai Tetap Bulanan',module:'monthly',href:'/companies/1/payroll?module=monthly'}],
    system:[{label:'Perusahaan & Cabang',href:'/companies',icon:'company'}],
    user:{name:'Tester',role:'Super Admin'}, logoutUrl:'/logout',csrf:'test-token',
};
test('collapsed navigation retains workspace links and an always available restore button', () => {
    globalThis.localStorage = { getItem:key => key==='taxcore-nav-collapsed' ? 'true' : null };
    const html=renderToStaticMarkup(createElement(Workspace,workspace));
    assert.match(html,/aria-label="Buka sidebar"/);
    assert.match(html,/aria-expanded="false"/);
    assert.match(html,/href="\/companies\/1\/payroll"/);
    assert.match(html,/aria-label="Daftar Gaji &amp; PPh 21"/);
    assert.match(html,/Pegawai Tetap Bulanan/);
    assert.ok(!html.includes('>TER</span>'));
    delete globalThis.localStorage;
});
test('expanded navigation has desktop collapse and mobile open controls', () => {
    const html=renderToStaticMarkup(createElement(Workspace,workspace));
    assert.match(html,/aria-label="Ciutkan sidebar"/);
    assert.match(html,/aria-label="Buka navigasi"/);
    assert.match(html,/Perusahaan Aman/);
    assert.match(html,/aria-label="Pegawai Tetap Bulanan"/);
    assert.match(html,/>TER<\/span>/);
});
test('company management identifies the current page without an active company', () => {
    const html=renderToStaticMarkup(createElement(Workspace,{...workspace,isManagingCompanies:true,companyId:null,navigation:workspace.navigation.map(item=>({...item,href:null,disabled:true})),modules:workspace.modules.map(item=>({...item,href:null,disabled:true}))}));
    assert.match(html,/aria-label="Kelola perusahaan"/);
    assert.ok(!html.includes('Perusahaan Aman'));
    assert.ok(!html.includes('href="/companies/1/payroll"'));
    assert.match(html,/aria-label="Ciutkan sidebar"/);
});

test('workspace has no theme switch even when a dark preference was saved', () => {
    globalThis.localStorage = { getItem:key => key==='taxcore-theme' ? 'dark' : null };
    const html=renderToStaticMarkup(createElement(Workspace,workspace));
    assert.ok(!html.includes('Gunakan tema'));
    assert.match(html,/aria-label="Perusahaan aktif"/);
    assert.match(html,/aria-label="Menu akun"/);
    delete globalThis.localStorage;
});

test('menus requiring a company are disabled without removing them in either sidebar state', () => {
    const data={...workspace,companyId:null,navigation:workspace.navigation.map(item=>({...item,href:null,disabled:true})),modules:workspace.modules.map(item=>({...item,href:null,disabled:true})),system:[...workspace.system,{label:'Log Audit',icon:'audit',href:null,disabled:true}]};
    for (const collapsed of [false,true]) {
        globalThis.localStorage={getItem:key=>key==='taxcore-nav-collapsed' ? String(collapsed) : null};
        const html=renderToStaticMarkup(createElement(Workspace,data));
        for (const label of ['Daftar Gaji &amp; PPh 21','Pegawai Tetap Bulanan','Log Audit']) {
            const button=html.match(new RegExp('<button[^>]*aria-label="'+label+'"[^>]*>'))?.[0];
            assert.ok(button,`${label} remains visible`);
            assert.match(button,/disabled=""/);
            assert.match(button,/aria-disabled="true"/);
            assert.ok(!button.includes('href='));
        }
        assert.match(html,/href="\/companies"/);
        assert.ok(!html.includes('href="/companies/1/payroll"'));
    }
    delete globalThis.localStorage;
});

test('UI never treats user or company text as executable markup', () => {
    const html=renderToStaticMarkup(createElement(Workspace,{...workspace,user:{name:'<script>alert(1)</script>',role:'<img src=x>'},companies:[{id:1,name:'<img src=x onerror=alert(1)>',href:'/companies/1/payroll'}]}));
    assert.ok(!html.includes('<script>alert(1)</script>'));
    assert.ok(!html.includes('<img src=x'));
    assert.match(html,/&lt;img/);
});
test('setup UI submits through the existing CSRF protected action with eight character passwords', () => {
    const html=renderToStaticMarkup(createElement(AuthForm,{mode:'setup',action:'/setup',csrf:'token"<test>',values:{username:'existing'}}));
    assert.match(html,/action="\/setup"/);
    assert.match(html,/name="_token"/);
    assert.match(html,/value="token/);
    assert.ok(!html.includes('<test>'));
});
test('login and setup preserve native password privacy and validation', () => {
    const setup=renderToStaticMarkup(createElement(AuthForm,{mode:'setup',action:'/setup',csrf:'token'}));
    assert.match(setup,/minLength="8"/i);
    assert.match(setup,/name="password_confirmation"/);
    assert.match(setup,/type="password"/);
    const login=renderToStaticMarkup(createElement(AuthForm,{mode:'login',action:'/login',csrf:'token',values:{username:'staff'}}));
    assert.match(login,/action="\/login"/);
    assert.match(login,/autoComplete="current-password"/i);
    assert.ok(!login.includes('password_confirmation'));
});
