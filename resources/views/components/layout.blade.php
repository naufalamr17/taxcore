@props(['company'=>null,'title'=>'Workspace'])
<!DOCTYPE html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><title>{{ $title }} · TaxCore</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body>
<div class="app-shell">
<aside class="sidebar" id="sidebar">
<a href="{{ route('dashboard') }}" class="brand"><span class="brand-mark">T</span><span>TAXCORE<small>PAYROLL & TAX WORKSPACE</small></span></a>
<button type="button" class="button small mb-4 hidden lg:block" data-sidebar-collapse aria-controls="sidebar" aria-expanded="true" aria-label="Ciutkan / perluas navigasi">&#8644;</button><div class="sidebar-label">WORKSPACE</div>
@if($company)
<a class="nav-link {{ request()->routeIs('payroll.*') ? 'active' : '' }}" href="{{ route('payroll.index',$company) }}">▤ <span>Daftar Gaji & PPh 21</span></a>
<a class="nav-link {{ request()->routeIs('employees.*') ? 'active' : '' }}" href="{{ route('employees.index',$company) }}">◉ <span>Database Penerima</span></a>
<details class="module-navigation" open><summary class="sidebar-label">MODUL PAJAK</summary>
@foreach(config('taxcore.modules') as $key=>$label)
<a class="nav-link {{ request('module')===$key ? 'active' : '' }}" href="{{ route('payroll.index',['company'=>$company,'module'=>$key,'month'=>$key==='annual' ? 12 : now()->month,'year'=>2026]) }}"><span class="nav-dot"></span><span>{{ $label }}</span></a>
@endforeach
<a class="nav-link {{ request()->routeIs('simulator.*') ? 'active' : '' }}" href="{{ route('simulator.index',$company) }}">⌘ <span>Simulator Pajak</span></a>
</details>@endif
<div class="sidebar-label">REFERENSI & SISTEM</div>
@can('payroll.view')<a class="nav-link" href="{{ route('tax.reference') }}">≋ <span>Tabel TER & PTKP</span></a>@endcan
<a class="nav-link {{ request()->routeIs('companies.*') ? 'active' : '' }}" href="{{ route('companies.index') }}">▥ <span>Perusahaan & Cabang</span></a>
@can('access.manage')<a class="nav-link {{ request()->routeIs('access.*') ? 'active' : '' }}" href="{{ route('access.index') }}">◇ <span>Manajemen Akses</span></a>@endcan
@if($company)<a class="nav-link" href="{{ route('audit.index',$company) }}">◷ <span>Log Audit</span></a>@endif
<div class="sidebar-bottom"><div class="flex items-center gap-3"><span class="avatar">{{ mb_substr(auth()->user()->name,0,1) }}</span><div class="min-w-0"><strong class="block truncate text-sm">{{ auth()->user()->name }}</strong><small class="text-slate-400">{{ config('taxcore.roles.'.auth()->user()->role) }}</small></div></div><form method="post" action="{{ route('logout') }}" class="mt-4">@csrf<button class="nav-link w-full justify-center" type="submit">Keluar dari workspace</button></form></div>
</aside><button type="button" class="sidebar-backdrop lg:hidden" data-sidebar-backdrop aria-label="Tutup navigasi"></button>
<div class="main-shell">
<header class="topbar">
<div class="flex min-w-0 items-center gap-3"><button type="button" class="icon-button lg:hidden" data-sidebar-toggle aria-controls="sidebar" aria-expanded="false" aria-label="Buka navigasi">☰</button><div><p class="eyebrow">TAXCORE / ENTERPRISE</p><h1 class="truncate font-semibold">{{ $title }}</h1></div></div>
<div class="flex items-center gap-3"><label class="sr-only" for="company-switch">Perusahaan aktif</label><select id="company-switch" class="field max-w-56" data-company-switch><option value="{{ route('companies.index') }}">Pilih perusahaan</option>@foreach($switchCompanies as $switchCompany)<option value="{{ route('payroll.index',$switchCompany) }}" @selected(($company?->id ?? session('active_company_id'))===$switchCompany->id)>{{ $switchCompany->name }}</option>@endforeach</select><button type="button" class="icon-button" data-theme-toggle aria-label="Ganti tema">◐</button></div>
</header>
<main class="content">
@if(session('status'))<div class="notice success" role="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="notice error" role="alert"><strong>Periksa kembali data berikut:</strong><ul class="mt-2 list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
{{ $slot }}
</main>
<footer class="app-footer"><span>TaxCore · PPh 21 Enterprise</span><span>PP 58/2023 · PMK 168/2023 · Standar WPDN, tanpa fasilitas DTP</span></footer>
</div></div>
</body></html>
