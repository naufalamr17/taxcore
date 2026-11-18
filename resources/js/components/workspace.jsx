import { useLayoutEffect, useRef, useState } from 'react';
import { Building2, Calculator, Check, ChevronDown, ChevronsUpDown, CircleDot, FileClock, FileSpreadsheet, Landmark, LogOut, Menu, Moon, PanelLeft, ShieldCheck, Sun, Users } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Sheet, SheetContent, SheetDescription, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { Separator } from '@/components/ui/separator';
import { cn } from '@/lib/utils';

const icons = { payroll: FileSpreadsheet, people: Users, calculator: Calculator, company: Building2, reference: Landmark, access: ShieldCheck, audit: FileClock };
function preference(key) {
    try { return localStorage.getItem(key); } catch { return null; }
}

function NavigationLink({ item, collapsed, close }) {
    const Icon = icons[item.icon] ?? CircleDot;
    const className = cn('h-9 w-full justify-start gap-3 px-3 font-normal', item.active && 'bg-sidebar-accent font-medium text-sidebar-accent-foreground', collapsed && 'justify-center px-0');
    const content = <><Icon className="size-4 shrink-0" /><span className={collapsed ? 'sr-only' : 'truncate'}>{item.label}</span></>;
    const link = item.disabled
        ? <Button type="button" variant="ghost" className={className} aria-label={item.label} aria-disabled="true" disabled title="Pilih perusahaan terlebih dahulu">{content}</Button>
        : <Button asChild variant="ghost" className={className}><a href={item.href} onClick={close} aria-label={item.label} aria-current={item.active ? 'page' : undefined}>{content}</a></Button>;
    return collapsed ? <Tooltip><TooltipTrigger asChild>{link}</TooltipTrigger><TooltipContent side="right">{item.label}</TooltipContent></Tooltip> : link;
}

function Navigation({ data, collapsed = false, close }) {
    const [modulesOpen, setModulesOpen] = useState(true);
    return <div className="flex h-full min-h-0 flex-col gap-5 p-3">
        <a href={data.dashboardUrl} className={cn('flex h-11 items-center gap-3 px-2', collapsed && 'justify-center px-0')} aria-label="TaxCore workspace">
            <span className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary text-primary-foreground"><Landmark className="size-5" /></span>
            {!collapsed && <span className="min-w-0"><strong className="block text-base font-semibold tracking-tight">TaxCore</strong><span className="block text-[11px] text-muted-foreground">Payroll & Tax Workspace</span></span>}
        </a>
        <div className="min-h-0 flex-1 space-y-5 overflow-y-auto">
            <nav aria-label="Workspace" className="space-y-1">{!collapsed && <p className="px-3 pb-1 text-xs font-medium text-muted-foreground">Workspace</p>}{data.navigation.map(item => <NavigationLink key={item.href ?? item.label} item={item} collapsed={collapsed} close={close} />)}{!data.navigation.length && <NavigationLink item={{ label:'Pilih perusahaan',href:data.companiesUrl,icon:'company' }} collapsed={collapsed} close={close} />}</nav>
            {!collapsed && !data.companyId && data.navigation.length > 0 && <p className="px-3 text-xs leading-5 text-muted-foreground">Pilih perusahaan untuk mengaktifkan menu workspace.</p>}
            {!!data.modules.length && <Collapsible open={collapsed || modulesOpen} onOpenChange={setModulesOpen}>
                {!collapsed && <CollapsibleTrigger className="mb-1 flex w-full items-center justify-between px-3 py-1 text-xs font-medium text-muted-foreground">Modul Pajak<ChevronDown className={cn('size-3.5 transition-transform', !modulesOpen && '-rotate-90')} /></CollapsibleTrigger>}
                <CollapsibleContent className="space-y-1">{data.modules.map(item => <NavigationLink key={item.href ?? item.label} item={item} collapsed={collapsed} close={close} />)}</CollapsibleContent>
            </Collapsible>}
            <nav aria-label="Referensi dan sistem" className="space-y-1">{!collapsed && <p className="px-3 pb-1 text-xs font-medium text-muted-foreground">Referensi & Sistem</p>}{data.system.map(item => <NavigationLink key={item.href ?? item.label} item={item} collapsed={collapsed} close={close} />)}</nav>
        </div>
        <div className="space-y-3"><Separator />{!collapsed && <div className="rounded-lg border bg-background/60 p-3"><div className="mb-1 flex items-center gap-2 text-xs font-medium"><ShieldCheck className="size-3.5" />PPh 21 Enterprise</div><p className="text-[11px] leading-5 text-muted-foreground">PP 58/2023 - PMK 168/2023</p></div>}<div className={cn('flex items-center gap-3 px-2 py-1', collapsed && 'justify-center px-0')}><span className="flex size-8 shrink-0 items-center justify-center rounded-lg border bg-background text-xs font-semibold">{data.user.name.slice(0,1).toUpperCase()}</span>{!collapsed && <div className="min-w-0"><p className="truncate text-sm font-medium">{data.user.name}</p><p className="truncate text-xs text-muted-foreground">{data.user.role}</p></div>}</div></div>
    </div>;
}

export function Workspace({ title, ...data }) {
    const [collapsed, setCollapsed] = useState(() => preference('taxcore-nav-collapsed') === 'true');
    const [mobileOpen, setMobileOpen] = useState(false);
    const [dark, setDark] = useState(() => preference('taxcore-theme') === 'dark');
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
    useLayoutEffect(() => {
        document.documentElement.classList.toggle('dark', dark);
        document.documentElement.dataset.theme = dark ? 'dark' : 'light';
        try { localStorage.setItem('taxcore-theme', dark ? 'dark' : 'light'); } catch {}
    }, [dark]);
    return <TooltipProvider delayDuration={200}>
        <a href="#main-content" className="sr-only fixed left-4 top-4 z-[100] rounded bg-background p-3 focus:not-sr-only">Lewati navigasi</a>
        <aside className="sidebar" aria-label="Navigasi utama"><Navigation data={data} collapsed={collapsed} /></aside>
        <header className="workspace-header">
            <div className="flex min-w-0 items-center gap-3">
                <Button type="button" variant="ghost" size="icon" className="hidden lg:inline-flex" onClick={() => setCollapsed(!collapsed)} aria-label={collapsed ? 'Buka sidebar' : 'Ciutkan sidebar'} aria-expanded={!collapsed}><PanelLeft /></Button>
                <Sheet open={mobileOpen} onOpenChange={setMobileOpen}><SheetTrigger asChild><Button type="button" variant="ghost" size="icon" className="lg:hidden" aria-label="Buka navigasi"><Menu /></Button></SheetTrigger><SheetContent side="left" className="w-72 max-w-[90vw] bg-sidebar p-0"><SheetTitle className="sr-only">Navigasi TaxCore</SheetTitle><SheetDescription className="sr-only">Workspace, modul pajak, dan pengaturan perusahaan</SheetDescription><Navigation data={data} close={() => setMobileOpen(false)} /></SheetContent></Sheet>
                <Separator orientation="vertical" className="hidden h-5 sm:block" /><div className="min-w-0"><p className="hidden text-[11px] text-muted-foreground sm:block">Workspace</p><h1 className="truncate text-sm font-medium">{title}</h1></div>
            </div>
            <div className="flex items-center gap-2">
                <DropdownMenu><DropdownMenuTrigger asChild><Button variant="outline" className="max-w-44 gap-2 sm:max-w-64" aria-label={data.isManagingCompanies ? 'Kelola perusahaan' : 'Perusahaan aktif'}><Building2 /><span className="truncate">{data.isManagingCompanies ? 'Kelola perusahaan' : (selected?.name ?? 'Pilih perusahaan')}</span><ChevronsUpDown className="ml-1 size-3.5 text-muted-foreground" /></Button></DropdownMenuTrigger><DropdownMenuContent align="end" className="w-72"><DropdownMenuLabel>Perusahaan Anda</DropdownMenuLabel><DropdownMenuSeparator />{data.companies.map(company => <DropdownMenuItem key={company.id} onSelect={() => window.location.assign(company.href)}><Building2 className="size-4" /><span className="flex-1 truncate">{company.name}</span>{company.id === data.companyId && <Check className="size-4" />}</DropdownMenuItem>)}<DropdownMenuSeparator /><DropdownMenuItem asChild><a href={data.companiesUrl}><Building2 />Kelola perusahaan</a></DropdownMenuItem></DropdownMenuContent></DropdownMenu>
                <Badge variant="secondary" className="hidden xl:inline-flex">{data.user.role}</Badge>
                <Button type="button" variant="ghost" size="icon" aria-label={dark ? 'Gunakan tema terang' : 'Gunakan tema gelap'} onClick={() => setDark(!dark)}>{dark ? <Sun /> : <Moon />}</Button>
                <DropdownMenu><DropdownMenuTrigger asChild><Button variant="outline" size="icon" className="rounded-full" aria-label="Menu akun">{data.user.name.slice(0,1).toUpperCase()}</Button></DropdownMenuTrigger><DropdownMenuContent align="end" className="w-56"><DropdownMenuLabel><p className="truncate">{data.user.name}</p><p className="mt-1 text-xs font-normal text-muted-foreground">{data.user.role}</p></DropdownMenuLabel><DropdownMenuSeparator /><DropdownMenuItem onSelect={() => logout.current.requestSubmit()}><LogOut />Keluar dari workspace</DropdownMenuItem></DropdownMenuContent></DropdownMenu>
                <form ref={logout} action={data.logoutUrl} method="post" hidden><input type="hidden" name="_token" value={data.csrf} /></form>
            </div>
        </header>
    </TooltipProvider>;
}
