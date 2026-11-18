import { useLayoutEffect, useRef, useState } from 'react';
import { BriefcaseBusiness, Building2, Calculator, CalendarCheck, Check, ChevronDown, ChevronsUpDown, CircleDot, Coins, Cpu, FileClock, FileSpreadsheet, HardHat, Landmark, LogOut, Menu, PanelLeft, Receipt, ShieldCheck, Users } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Sheet, SheetContent, SheetDescription, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { cn } from '@/lib/utils';

const icons = { payroll: FileSpreadsheet, people: Users, calculator: Calculator, company: Building2, reference: Landmark, access: ShieldCheck, audit: FileClock };
const moduleStyles = {
    monthly: { icon:CalendarCheck, badge:'TER', color:'text-cyan-300' },
    annual: { icon:Receipt, badge:'Psl 17', color:'text-indigo-300' },
    pension: { icon:Users, badge:'TER', color:'text-teal-300' },
    daily: { icon:HardHat, badge:'Harian', color:'text-amber-300' },
    contractor: { icon:BriefcaseBusiness, badge:'50%', color:'text-purple-300' },
    severance: { icon:Coins, badge:'Final', color:'text-rose-300' },
};
function preference(key) {
    try { return localStorage.getItem(key); } catch { return null; }
}

function NavigationLink({ item, collapsed, close }) {
    const moduleStyle = moduleStyles[item.module];
    const Icon = moduleStyle?.icon ?? icons[item.icon] ?? CircleDot;
    const className = cn('nav-link justify-start hover:bg-sidebar-accent hover:text-slate-50', item.active && 'active', collapsed && 'justify-center px-0');
    const content = <><Icon className={cn('size-4 shrink-0', moduleStyle?.color)} /><span className={collapsed ? 'sr-only' : 'truncate'}>{item.label}</span>{!collapsed && moduleStyle && <span className={cn('ml-auto shrink-0 rounded border border-current/30 bg-current/10 px-1.5 py-0.5 font-mono text-[9px] font-bold', moduleStyle.color)}>{moduleStyle.badge}</span>}</>;
    const link = item.disabled
        ? <Button type="button" variant="ghost" className={className} aria-label={item.label} aria-disabled="true" disabled title="Pilih perusahaan terlebih dahulu">{content}</Button>
        : <Button asChild variant="ghost" className={className}><a href={item.href} onClick={close} aria-label={item.label} aria-current={item.active ? 'page' : undefined}>{content}</a></Button>;
    return collapsed ? <Tooltip><TooltipTrigger asChild>{link}</TooltipTrigger><TooltipContent side="right">{item.label}</TooltipContent></Tooltip> : link;
}

function Navigation({ data, collapsed = false, close }) {
    const [modulesOpen, setModulesOpen] = useState(true);
    return <div className="flex h-full min-h-0 flex-col text-slate-400">
        <a href={data.dashboardUrl} className={cn('flex h-16 shrink-0 items-center gap-3 px-4', collapsed && 'justify-center px-0')} aria-label="TaxCore workspace">
            <span className="brand-mark"><Cpu className="size-4" /></span>
            {!collapsed && <span className="min-w-0"><span className="flex items-center gap-1.5"><strong className="text-sm font-extrabold tracking-tight text-white">TAXCORE</strong><span className="quantum-badge">QUANTUM 21</span></span><span className="mt-1 block truncate text-[10px] font-medium text-slate-400">Coretax DJP · PP 58/2023 Engine</span></span>}
        </a>
        <div className={cn('sidebar-user', collapsed && 'justify-center px-0')}><span className="sidebar-avatar">{data.user.name.slice(0,1).toUpperCase()}</span>{!collapsed && <div className="min-w-0"><p className="flex items-center gap-1.5 text-xs font-bold text-slate-100"><span className="size-1.5 shrink-0 rounded-full bg-emerald-400" /><span className="truncate">{data.user.name}</span></p><p className="mt-0.5 truncate font-mono text-[10px] text-cyan-400">{data.user.role}</p></div>}</div>
        <div className="min-h-0 flex-1 space-y-5 overflow-y-auto px-3 py-4">
            <nav aria-label="Workspace" className="space-y-1">{!collapsed && <p className="sidebar-label">01 // Command Center</p>}{data.navigation.map(item => <NavigationLink key={item.href ?? item.label} item={item} collapsed={collapsed} close={close} />)}{!data.navigation.length && <NavigationLink item={{ label:'Pilih perusahaan',href:data.companiesUrl,icon:'company' }} collapsed={collapsed} close={close} />}</nav>
            {!collapsed && !data.companyId && data.navigation.length > 0 && <p className="px-3 text-[11px] leading-5 text-slate-400">Pilih perusahaan untuk mengaktifkan menu workspace.</p>}
            {!!data.modules.length && <Collapsible open={collapsed || modulesOpen} onOpenChange={setModulesOpen}>
                {!collapsed && <CollapsibleTrigger className="sidebar-label py-1">02 // Modul Pajak<ChevronDown className={cn('size-3 transition-transform', !modulesOpen && '-rotate-90')} /></CollapsibleTrigger>}
                <CollapsibleContent className="space-y-1">{data.modules.map(item => <NavigationLink key={item.href ?? item.label} item={item} collapsed={collapsed} close={close} />)}</CollapsibleContent>
            </Collapsible>}
            <nav aria-label="Referensi dan sistem" className="space-y-1">{!collapsed && <p className="sidebar-label">03 // Sistem & Referensi</p>}{data.system.map(item => <NavigationLink key={item.href ?? item.label} item={item} collapsed={collapsed} close={close} />)}</nav>
        </div>
        <div className="shrink-0 border-t border-slate-800/80 bg-slate-950/60 p-3.5">{collapsed ? <ShieldCheck className="mx-auto size-4 text-cyan-400" /> : <div className="rounded-xl border border-cyan-500/20 bg-cyan-500/5 p-3"><div className="flex items-center gap-2 text-xs font-bold text-cyan-300"><ShieldCheck className="size-3.5" />PPh 21 Enterprise</div><p className="mt-1 font-mono text-[10px] text-slate-400">PP 58/2023 · PMK 168/2023</p></div>}</div>
    </div>;
}

export function Workspace({ title, ...data }) {
    const [collapsed, setCollapsed] = useState(() => preference('taxcore-nav-collapsed') === 'true');
    const [mobileOpen, setMobileOpen] = useState(false);
    const logout = useRef(null);
    const selected = data.companies.find(company => company.id === data.companyId);
    useLayoutEffect(() => {
        document.documentElement.classList.add('ui-ready');
        return () => document.documentElement.classList.remove('ui-ready');
    }, []);
    useLayoutEffect(() => {
        document.documentElement.dataset.navCollapsed = String(collapsed);
        try { localStorage.setItem('taxcore-nav-collapsed', String(collapsed)); } catch {}
    }, [collapsed]);
    return <TooltipProvider delayDuration={200}>
        <a href="#main-content" className="sr-only fixed left-4 top-4 z-[100] rounded bg-background p-3 focus:not-sr-only">Lewati navigasi</a>
        <aside className="sidebar" aria-label="Navigasi utama"><Navigation data={data} collapsed={collapsed} /></aside>
        <header className="workspace-header">
            <div className="flex min-w-0 items-center gap-3">
                <Button type="button" variant="ghost" size="icon" className="hidden lg:inline-flex" onClick={() => setCollapsed(!collapsed)} aria-label={collapsed ? 'Buka sidebar' : 'Ciutkan sidebar'} aria-expanded={!collapsed}><PanelLeft /></Button>
                <Sheet open={mobileOpen} onOpenChange={setMobileOpen}><SheetTrigger asChild><Button type="button" variant="ghost" size="icon" className="lg:hidden" aria-label="Buka navigasi"><Menu /></Button></SheetTrigger><SheetContent side="left" className="mobile-navigation w-[276px] max-w-[90vw] border-cyan-400/20 p-0 text-slate-400"><SheetTitle className="sr-only">Navigasi TaxCore</SheetTitle><SheetDescription className="sr-only">Workspace, modul pajak, dan pengaturan perusahaan</SheetDescription><Navigation data={data} close={() => setMobileOpen(false)} /></SheetContent></Sheet>
                <div className="min-w-0"><div className="flex items-center gap-2.5"><h1 className="truncate text-sm font-extrabold tracking-tight sm:text-base">{title}</h1><Badge variant="outline" className="pill hidden shrink-0 2xl:inline-flex">PP 58/2023 · PMK 168</Badge></div><p className="mt-0.5 hidden truncate text-[11px] text-muted-foreground md:block">Quantum Tax Telemetry · TER Bulanan & Integrasi Coretax DJP</p></div>
            </div>
            <div className="flex items-center gap-2">
                <DropdownMenu><DropdownMenuTrigger asChild><Button variant="outline" className="max-w-28 gap-2 rounded-xl border-indigo-200 bg-indigo-50/60 text-xs sm:max-w-56 dark:border-indigo-400/30 dark:bg-indigo-500/10" aria-label={data.isManagingCompanies ? 'Kelola perusahaan' : 'Perusahaan aktif'}><Building2 className="text-indigo-500" /><span className="truncate">{data.isManagingCompanies ? 'Kelola perusahaan' : (selected?.name ?? 'Pilih perusahaan')}</span><ChevronsUpDown className="ml-1 hidden size-3.5 text-muted-foreground sm:block" /></Button></DropdownMenuTrigger><DropdownMenuContent align="end" className="w-72"><DropdownMenuLabel>Perusahaan Anda</DropdownMenuLabel><DropdownMenuSeparator />{data.companies.map(company => <DropdownMenuItem key={company.id} onSelect={() => window.location.assign(company.href)}><Building2 className="size-4" /><span className="flex-1 truncate">{company.name}</span>{company.id === data.companyId && <Check className="size-4" />}</DropdownMenuItem>)}<DropdownMenuSeparator /><DropdownMenuItem asChild><a href={data.companiesUrl}><Building2 />Kelola perusahaan</a></DropdownMenuItem></DropdownMenuContent></DropdownMenu>
                <DropdownMenu><DropdownMenuTrigger asChild><Button variant="outline" size="icon" className="rounded-full" aria-label="Menu akun">{data.user.name.slice(0,1).toUpperCase()}</Button></DropdownMenuTrigger><DropdownMenuContent align="end" className="w-56"><DropdownMenuLabel><p className="truncate">{data.user.name}</p><p className="mt-1 text-xs font-normal text-muted-foreground">{data.user.role}</p></DropdownMenuLabel><DropdownMenuSeparator /><DropdownMenuItem onSelect={() => logout.current.requestSubmit()}><LogOut />Keluar dari workspace</DropdownMenuItem></DropdownMenuContent></DropdownMenu>
                <form ref={logout} action={data.logoutUrl} method="post" hidden><input type="hidden" name="_token" value={data.csrf} /></form>
            </div>
        </header>
    </TooltipProvider>;
}
